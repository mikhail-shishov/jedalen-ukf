<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\MenuItem;
use App\Models\Allergen;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminMealController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = trim((string) $request->get('q', ''));

        $mealsQuery = Meal::with(['menuItems', 'allergens', 'canteens'])
            ->orderBy('id', 'desc');

        if ($searchQuery !== '') {
            $safe = '%' . addcslashes($searchQuery, '%_\\') . '%';
            $mealsQuery->where(function ($query) use ($safe) {
                $query->where('raw_name', 'like', $safe)
                    ->orWhere('name_sk', 'like', $safe)
                    ->orWhere('name_en', 'like', $safe)
                    ->orWhere('name_ua', 'like', $safe)
                    ->orWhere('name_ru', 'like', $safe)
                    ->orWhere('price', 'like', $safe)
                    ->orWhereHas('allergens', function ($allergenQuery) use ($safe) {
                        $allergenQuery->where('number', 'like', $safe)
                            ->orWhere('name', 'like', $safe);
                    });
            });
        }

        $meals = $mealsQuery->paginate(25)->withQueryString();
        $canteens = \App\Models\Canteen::all();
        $allergens = \App\Models\Allergen::orderByRaw('CAST(number AS UNSIGNED) ASC')->get();

        return view('admin.meals', compact('meals', 'canteens', 'allergens', 'searchQuery'));
    }

    public function store(Request $request, AiService $gemini)
    {
        $request->validate([
            'raw_name'      => 'required|string|max:255',
            'price'         => 'required|numeric',
            'allergen_ids'  => 'nullable|array',
            'custom_image'  => 'nullable|file|mimes:jpg,jpeg,png,gif,avif,svg,webp|max:5120',
            'skip_ai_image' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($request, $gemini) {
            $meal = Meal::where('raw_name', $request->raw_name)->first();

            if (!$meal) {
                $hasCustomImage = $request->hasFile('custom_image');
                $skipAiImage = (bool) $request->boolean('skip_ai_image');

                $aiData = $gemini->enrichMealData($request->raw_name, !$hasCustomImage && !$skipAiImage);
                if (!$aiData) throw new \Exception('AI enrichment failed.');

                $imagePath = null;
                if ($hasCustomImage) {
                    $imagePath = $request->file('custom_image')->store('meals', 'public');
                } else {
                    $imagePath = $aiData['image_path'] ?? null;
                }

                $meal = Meal::create([
                    'raw_name' => $request->raw_name,
                    'name_sk'  => $aiData['name_sk'] ?? $request->raw_name,
                    'name_en'  => $aiData['name_en'] ?? null,
                    'name_ua'  => $aiData['name_ua'] ?? null,
                    'name_ru'  => $aiData['name_ru'] ?? null,
                    'price'    => $request->price,
                    'image_path' => $imagePath,
                ]);

                $allergenIds = $request->input('allergen_ids', []);
                if (!empty($aiData['allergens'])) {
                    $aiFound = \App\Models\Allergen::whereIn('number', (array)$aiData['allergens'])->pluck('id')->toArray();
                    $allergenIds = array_unique(array_merge($allergenIds, $aiFound));
                }
                $meal->allergens()->sync($allergenIds);
            }

            return redirect()->back()->with('success', 'Jedlo bolo pridané do katalógu.');
        });
    }

    public function update(Request $request, $id)
    {
        $meal = Meal::findOrFail($id);

        $request->validate([
            'raw_name' => 'required|string|max:255',
            'name_sk' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_ua' => 'nullable|string|max:255',
            'name_ru' => 'nullable|string|max:255',
            'price'   => 'required|numeric',
            'allergen_ids' => 'nullable|array',
            'custom_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,avif,svg,webp|max:5120',
        ]);

        $update = $request->only(['raw_name', 'name_sk', 'name_en', 'name_ua', 'name_ru', 'price']);

        if ($request->hasFile('custom_image')) {
            $newPath = $request->file('custom_image')->store('meals', 'public');
            $oldPath = $meal->image_path;
            $update['image_path'] = $newPath;

            if ($oldPath && !str_starts_with($oldPath, 'http') && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $meal->update($update);
        $meal->allergens()->sync($request->input('allergen_ids', []));

        return redirect()->back()->with('success', 'Karta jedla bola úspešne aktualizovaná.');
    }

    public function generateImage($id, AiService $gemini)
    {
        try {
            set_time_limit(60);

            $meal = Meal::findOrFail($id);

            if (empty($meal->name_en)) {
                return response()->json(['success' => false, 'message' => 'Pole name_en je prázdne.'], 422);
            }

            $imagePath = $gemini->generateImage($meal->name_en);

            if ($imagePath === '') {
                return response()->json(['success' => false, 'message' => 'Obrázok sa nepodarilo vygenerovať. Skontrolujte POLLINATIONS_API_KEY a laravel.log.'], 502);
            }

            $meal->update(['image_path' => $imagePath]);

            return response()->json([
                'success'   => true,
                'image_url' => asset('storage/' . $imagePath),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Interná chyba pri generovaní obrázka.',
            ], 500);
        }
    }

    public function suggestAllergens(Request $request, AiService $gemini)
    {
        $request->validate([
            'raw_name' => 'required|string|max:255',
        ]);

        try {
            $suggestedNumbers = $gemini->suggestAllergens($request->raw_name);
            if (empty($suggestedNumbers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI nenašla žiadne jasné návrhy alergénov.',
                    'allergens' => [],
                ]);
            }

            $allergens = Allergen::whereIn('number', $suggestedNumbers)
                ->get(['id', 'number', 'name'])
                ->map(fn ($allergen) => [
                    'id' => $allergen->id,
                    'number' => (string) $allergen->number,
                    'name' => $allergen->name,
                ])
                ->values();

            return response()->json([
                'success' => true,
                'allergens' => $allergens,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Nepodarilo sa získať návrhy alergénov.',
                'allergens' => [],
            ], 500);
        }
    }

    public function suggestTranslations(Request $request, AiService $gemini)
    {
        $request->validate([
            'raw_name' => 'required|string|max:255',
        ]);

        try {
            $aiData = $gemini->enrichMealData($request->raw_name, false);

            if (!$aiData) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI nevrátila preklady.',
                ], 502);
            }

            return response()->json([
                'success' => true,
                'translations' => [
                    'name_sk' => $aiData['name_sk'] ?? $request->raw_name,
                    'name_en' => $aiData['name_en'] ?? null,
                    'name_ua' => $aiData['name_ua'] ?? null,
                    'name_ru' => $aiData['name_ru'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Nepodarilo sa získať AI preklady.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $meal = Meal::findOrFail($id);

        $meal->menuItems()->delete();
        $meal->allergens()->detach();
        $meal->delete();

        return redirect()->back()->with('success', 'Jedlo bolo úspešne odstránené.');
    }
}
