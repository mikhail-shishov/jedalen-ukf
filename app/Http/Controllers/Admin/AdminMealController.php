<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AdminMealController extends Controller
{
    public function index(){
        $meals = Meal::all();
        return view('admin.meals', compact('meals'));
    }

    public function store(Request $request, GeminiService $gemini)
    {
        $rawName = $request->input('raw_name');

        $aiData = $gemini->enrichMealData($rawName);

        if (!$aiData) {
            return back()->with('error', 'AI service failed.');
        }

        Meal::create([
            'name_sk'    => $aiData['name_sk'],
            'name_en'    => $aiData['name_en'],
            'name_ua'    => $aiData['name_ua'],
            'name_ru'    => $aiData['name_ru'],
            'allergens'  => $aiData['allergens'],
            'image_path' => $aiData['image_path'], 
            'price'      => $request->input('price'),
            'date'       => $request->input('date'),
        ]);

        return redirect()->route('meals.index');
    }

    public function enrich($id) {}
}
