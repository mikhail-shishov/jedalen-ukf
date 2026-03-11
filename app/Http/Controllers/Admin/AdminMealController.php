<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\MenuItem;
use App\Models\Allergen;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMealController extends Controller
{
    public function index()
    {
        $meals = Meal::with(['menuItems', 'allergens', 'canteens'])->orderBy('id', 'desc')->get();
        $canteens = \App\Models\Canteen::all();
        $allergens = \App\Models\Allergen::orderByRaw('CAST(number AS UNSIGNED) ASC')->get();

        return view('admin.meals', compact('meals', 'canteens', 'allergens'));
    }

    public function store(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'raw_name'      => 'required|string|max:255',
            'price'         => 'required|numeric',
            'date'          => 'required|date',
            'canteen_ids'   => 'required|array',
            'canteen_ids.*' => 'exists:canteens,id'
        ]);

        return DB::transaction(function () use ($request, $gemini) {
            $meal = Meal::where('raw_name', $request->raw_name)->first();

            if (!$meal) {
                $aiData = $gemini->enrichMealData($request->raw_name);
                if (!$aiData) throw new \Exception('AI enrichment failed.');

                $meal = Meal::create([
                    'raw_name' => $request->raw_name,
                    'name_sk'  => $aiData['name_sk'] ?? $request->raw_name,
                    'name_en'  => $aiData['name_en'] ?? null,
                    'name_ua'  => $aiData['name_ua'] ?? null,
                    'name_ru'  => $aiData['name_ru'] ?? null,
                    'price'    => $request->price,
                    'image_path' => $aiData['image_path'] ?? null,
                ]);

                $allergenIds = $request->input('allergen_ids', []);
                if (!empty($aiData['allergens'])) {
                    $aiFound = \App\Models\Allergen::whereIn('number', (array)$aiData['allergens'])->pluck('id')->toArray();
                    $allergenIds = array_unique(array_merge($allergenIds, $aiFound));
                }
                $meal->allergens()->sync($allergenIds);
            }

            foreach ($request->canteen_ids as $canteenId) {
                \App\Models\MenuItem::create([
                    'canteen_id' => $canteenId,
                    'meal_id'    => $meal->id,
                    'date'       => $request->date,
                    'stock_total' => 100,
                    'stock_current' => 100,
                ]);
            }

            return redirect()->back()->with('success', 'Jedlo bolo pridané do katalógu a priradené do vybraných jedální.');
        });
    }

    public function update(Request $request, $id)
    {
        $meal = Meal::findOrFail($id);

        $request->validate([
            'name_sk' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_ua' => 'nullable|string|max:255',
            'name_ru' => 'nullable|string|max:255',
            'price'   => 'required|numeric',
            'allergen_ids' => 'nullable|array'
        ]);

        $meal->update($request->only(['name_sk', 'name_en', 'name_ua', 'name_ru', 'price']));
        $meal->allergens()->sync($request->input('allergen_ids', []));

        return redirect()->back()->with('success', 'Karta jedla bola úspešne aktualizovaná.');
    }

    public function generateImage($id, GeminiService $gemini)
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

    public function destroy($id)
    {
        $meal = Meal::findOrFail($id);

        $meal->menuItems()->delete();
        $meal->allergens()->detach();
        $meal->delete();

        return redirect()->back()->with('success', 'Jedlo bolo úspešne odstránené.');
    }
}
