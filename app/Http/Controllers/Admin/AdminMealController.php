<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AdminMealController extends Controller
{
    public function index()
    {
        $meals = Meal::all();
        return view('admin.meals', compact('meals'));
    }

    public function store(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'raw_name' => 'required|string|max:255',
            'price'    => 'required|numeric',
            'date'     => 'required|date',
        ]);

        $rawName = $request->input('raw_name');

        set_time_limit(60);

        $aiData = $gemini->enrichMealData($rawName);

        if (!$aiData) {
            return back()->with('error', 'AI service failed. Skúste to znova.');
        }

        Meal::create([
            'name_sk'    => $aiData['name_sk'] ?? $rawName,
            'name_en'    => $aiData['name_en'] ?? null,
            'name_ua'    => $aiData['name_ua'] ?? null,
            'name_ru'    => $aiData['name_ru'] ?? null,
            'allergens'  => $aiData['allergens'] ?? '',
            'image_path' => $aiData['image_path'] ?? '/assets/img/default-meal.jpg',
            'price'      => $request->input('price'),
            'date'       => $request->input('date'),
        ]);

        return redirect()->route('admin.meals.index')->with('success', 'Jedlo bolo úspešne vytvorené.');
    }

    public function enrich($id, GeminiService $gemini)
    {
        $meal = Meal::findOrFail($id);

        $aiData = $gemini->enrichMealData($meal->name_sk);

        if ($aiData) {
            $meal->update([
                'name_en'    => $aiData['name_en'] ?? $meal->name_en,
                'name_ua'    => $aiData['name_ua'] ?? $meal->name_ua,
                'name_ru'    => $aiData['name_ru'] ?? $meal->name_ru,
                'allergens'  => $aiData['allergens'] ?? $meal->allergens,
                'image_path' => $aiData['image_path'] ?? $meal->image_path,
            ]);

            return back()->with('success', "Dáta pre '{$meal->name_sk}' boli aktualizované.");
        }

        return back()->with('error', 'Nepodarilo sa získať dáta z AI.');
    }
}
