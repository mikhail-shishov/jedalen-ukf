<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Allergen;
use App\Models\Canteen;
use App\Models\Meal;
use App\Models\MenuItem;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminImportController extends Controller
{
    private function normalizePrice(string $value): ?float
    {
        $normalized = trim($value);
        $normalized = str_replace(["\xC2\xA0", ' '], '', $normalized);

        // Accept comma decimals from CSV (4,20) as well as dots (4.20)
        $normalized = str_replace(',', '.', $normalized);

        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    public function index()
    {
        $canteens = Canteen::orderBy('name')->get();
        return view('admin.import', compact('canteens'));
    }

    /**
     * Parse & preview CSV — returns JSON so JS can render the preview table.
     */
    public function preview(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $rows  = [];
        $errors = [];
        $handle = fopen($request->file('file')->getRealPath(), 'r');

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return response()->json(['rows' => [], 'errors' => ['CSV súbor je prázdny.']]);
        }

        // Auto-detect delimiter: Excel exports often use ';' with decimal comma.
        $commaCount = substr_count($firstLine, ',');
        $semiCount = substr_count($firstLine, ';');
        $delimiter = $semiCount > $commaCount ? ';' : ',';

        rewind($handle);

        $headerSkipped = false;
        $lineNum = 0;

        while (($cols = fgetcsv($handle, 1000, $delimiter)) !== false) {
            $lineNum++;

            // Skip header row if first column looks like "date" / "datum"
            if (!$headerSkipped) {
                $first = strtolower(trim($cols[0] ?? ''));
                if (in_array($first, ['date', 'datum', 'dátum', 'd'])) {
                    $headerSkipped = true;
                    continue;
                }
                $headerSkipped = true;
            }

            if (count($cols) < 3) {
                $errors[] = "Riadok {$lineNum}: nesprávny počet stĺpcov.";
                continue;
            }

            $rawDate  = trim($cols[0]);
            $rawName  = trim($cols[1]);

            $priceParts = array_values(array_filter(
                array_map(fn ($part) => trim((string) $part), array_slice($cols, 2)),
                fn ($part) => $part !== ''
            ));

            $rawPrice = $priceParts[0] ?? '';

            // If delimiter is comma and decimal comma is not quoted, fgetcsv can split 4,20 into ["4", "20"].
            if (
                $delimiter === ','
                && count($priceParts) > 1
                && preg_match('/^\d+$/', $priceParts[0])
                && preg_match('/^\d{1,2}$/', $priceParts[1])
                && !str_contains($priceParts[0], '.')
                && !str_contains($priceParts[0], ',')
            ) {
                $rawPrice = $priceParts[0] . ',' . $priceParts[1];
            }

            if (empty($rawName)) {
                $errors[] = "Riadok {$lineNum}: prázdny názov.";
                continue;
            }

            $date = null;
            foreach (['Y-m-d', 'd.m.Y', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $fmt) {
                $parsed = \DateTime::createFromFormat($fmt, $rawDate);
                if ($parsed && $parsed->format($fmt) === $rawDate) {
                    $date = $parsed->format('Y-m-d');
                    break;
                }
            }

            if ($rawDate && !$date) {
                $errors[] = "Riadok {$lineNum}: nerozpoznaný formát dátumu '{$rawDate}'.";
                continue;
            }

            $price = $this->normalizePrice($rawPrice);
            if ($price === null) {
                $errors[] = "Riadok {$lineNum}: neplatná cena '{$rawPrice}'.";
                continue;
            }

            $rows[] = [
                'date'  => $date,
                'name'  => $rawName,
                'price' => $price,
            ];
        }

        fclose($handle);

        return response()->json(['rows' => $rows, 'errors' => $errors]);
    }

    /**
     * Persist the parsed rows: create meals + optional menu_items.
     * Returns a batch_id (list of newly created meal IDs) for AI enrichment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'rows'          => 'required|array|min:1',
            'rows.*.name'   => 'required|string|max:255',
            'rows.*.price'  => 'required|numeric|min:0',
            'rows.*.date'   => 'nullable|date',
            'canteen_id'    => 'nullable|exists:canteens,id',
        ]);

        $canteenId  = $request->canteen_id;
        $created    = [];
        $skipped    = [];
        $menuItems  = [];

        DB::transaction(function () use ($request, $canteenId, &$created, &$skipped, &$menuItems) {
            foreach ($request->rows as $row) {
                $existing = Meal::where('raw_name', $row['name'])->first();

                if ($existing) {
                    $skipped[] = ['id' => $existing->id, 'name' => $row['name']];
                    $meal = $existing;
                } else {
                    $meal = Meal::create([
                        'raw_name' => $row['name'],
                        'name_sk'  => $row['name'],
                        'price'    => $row['price'],
                    ]);
                    $created[] = ['id' => $meal->id, 'name' => $row['name']];
                }

                // Schedule in menu if date + canteen provided
                if (!empty($row['date']) && $canteenId) {
                    $alreadyScheduled = MenuItem::where('canteen_id', $canteenId)
                        ->where('meal_id', $meal->id)
                        ->where('date', $row['date'])
                        ->exists();

                    if (!$alreadyScheduled) {
                        $item = MenuItem::create([
                            'canteen_id'    => $canteenId,
                            'meal_id'       => $meal->id,
                            'date'          => $row['date'],
                            'stock_total'   => 0,
                            'stock_current' => 0,
                        ]);
                        $menuItems[] = ['meal_id' => $meal->id, 'date' => $row['date'], 'menu_item_id' => $item->id];
                    }
                }
            }
        });

        $batchIds = collect($created)->pluck('id')->values()->all();

        return response()->json([
            'created'    => $created,
            'skipped'    => $skipped,
            'menu_items' => $menuItems,
            'batch_ids'  => $batchIds,
        ]);
    }

    /**
     * Enrich a single meal with AI (called per-meal via AJAX for progress feedback).
     */
    public function enrich(Request $request, GeminiService $gemini)
    {
        try {
            $request->validate([
                'meal_id'      => 'required|exists:meals,id',
                'do_translate' => 'boolean',
                'do_allergens' => 'boolean',
                'do_image'     => 'boolean',
            ]);

            $meal        = Meal::with('allergens')->findOrFail($request->meal_id);
            $doTranslate = (bool) $request->input('do_translate', false);
            $doAllergens = (bool) $request->input('do_allergens', false);
            $doImage     = (bool) $request->input('do_image', false);

            $changes = [];

            if ($doTranslate || $doAllergens) {
                $aiData = $gemini->enrichMealData($meal->raw_name);

                if ($aiData) {
                    $update = [];

                    if ($doTranslate) {
                        $update['name_sk'] = $aiData['name_sk'] ?? $meal->name_sk;
                        $update['name_en'] = $aiData['name_en'] ?? null;
                        $update['name_ua'] = $aiData['name_ua'] ?? null;
                        $update['name_ru'] = $aiData['name_ru'] ?? null;
                        $changes[] = 'translated';
                    }

                    if ($doAllergens && !empty($aiData['allergens'])) {
                        $numbers      = preg_split('/\s*,\s*/', (string) $aiData['allergens']);
                        $allergenIds  = Allergen::whereIn('number', $numbers)->pluck('id')->toArray();
                        $meal->allergens()->sync($allergenIds);
                        $changes[] = 'allergens';
                    }

                    if (!empty($update)) {
                        $meal->update($update);
                    }

                    // Generate image from translated English name if requested
                    if ($doImage && !empty($aiData['name_en'])) {
                        $imagePath = $gemini->generateImage($aiData['name_en']);
                        if ($imagePath) {
                            $meal->update(['image_path' => $imagePath]);
                            $changes[] = 'image';
                        }
                    }
                }
            } elseif ($doImage) {
                // Image only — use existing name_en or raw_name
                $nameForImage = $meal->name_en ?: $meal->raw_name;
                $imagePath    = $gemini->generateImage($nameForImage);
                if ($imagePath) {
                    $meal->update(['image_path' => $imagePath]);
                    $changes[] = 'image';
                }
            }

            return response()->json([
                'meal_id' => $meal->id,
                'changes' => $changes,
                'ok'      => true,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'meal_id' => (int) $request->input('meal_id'),
                'message' => 'AI spracovanie zlyhalo pre túto položku.',
            ], 500);
        }
    }
}
