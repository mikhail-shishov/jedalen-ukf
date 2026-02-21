<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function store(Request $request)
    {
        $rawName = $request->input('name');

        $enrichedData = $this->gemini->enrichMealData($rawName);

        if (!$enrichedData) {
            return response()->json(['error' => 'AI failed'], 500);
        }

        $meal = Meal::create([
            'type'       => $request->input('type'),
            'price'      => $request->input('price'),
            'date'       => $request->input('date'),
            'allergens'  => $enrichedData['allergens'],
            'name_sk'    => $enrichedData['name_sk'],
            'name_en'    => $enrichedData['name_en'],
            'name_ua'    => $enrichedData['name_ua'],
            'name_ru'    => $enrichedData['name_ru'],
        ]);

        return response()->json($meal);
    }
}
