<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class MealController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function index()
    {
        $legacyNames = [
            'Kur.steak s trojfareb. koren.',
            'Šošovicová pol. s párkom 1,7'
        ];

        $enrichedMeals = [];

        foreach ($legacyNames as $rawName) {
            $meal = Meal::where('raw_name', $rawName)->first();

            if (!$meal) {
                $aiData = $this->geminiService->enrichMealData($rawName);

                if ($aiData) {
                    $meal = Meal::create([
                        'raw_name'  => $rawName,
                        'name_sk'   => $aiData['name_sk'],
                        'name_en'   => $aiData['name_en'],
                        'name_ua'   => $aiData['name_ua'],
                        'name_ru'   => $aiData['name_ru'],
                        'allergens' => $aiData['allergens'],
                        'image_path' => "https://image.pollinations.ai/prompt/food-photography-of-" . urlencode($aiData['name_en'])
                    ]);
                }
            }
            $enrichedMeals[] = $meal;
        }

        return response()->json($enrichedMeals);
    }
}