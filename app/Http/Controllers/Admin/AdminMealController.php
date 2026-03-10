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
        $meals = Meal::with('menuItems')->orderBy('id', 'desc')->get();
        return view('admin.meals', compact('meals'));
    }

    public function store(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'raw_name' => 'required|string|max:255',
            'price'    => 'required|numeric',
            'date'     => 'required|date',
            'canteen_id' => 'required|exists:canteens,id'
        ]);

        try {
            return DB::transaction(function () use ($request, $gemini) {
                $rawName = $request->input('raw_name');
                
                $aiData = $gemini->enrichMealData($rawName);
                if (!$aiData) throw new \Exception('AI service fail');

                $meal = Meal::create([
                    'raw_name'   => $rawName,
                    'name_sk'    => $aiData['name_sk'] ?? $rawName,
                    'name_en'    => $aiData['name_en'] ?? null,
                    'name_ua'    => $aiData['name_ua'] ?? null,
                    'name_ru'    => $aiData['name_ru'] ?? null,
                    'image_path' => $aiData['image_path'] ?? '/assets/img/default-meal.jpg',
                    'price'      => $request->input('price'),
                ]);

                if (!empty($aiData['allergens'])) {
                    $allergenIds = Allergen::whereIn('number', (array)$aiData['allergens'])->pluck('id');
                    $meal->allergens()->attach($allergenIds);
                }

                MenuItem::create([
                    'canteen_id' => $request->input('canteen_id'),
                    'meal_id'    => $meal->id,
                    'date'       => $request->input('date'),
                    'stock_total' => 100,
                    'stock_current' => 100,
                ]);

                return redirect()->back()->with('success', 'Jedlo bolo vytvorené a pridané do menu.');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Chyba: ' . $e->getMessage());
        }
    }
}