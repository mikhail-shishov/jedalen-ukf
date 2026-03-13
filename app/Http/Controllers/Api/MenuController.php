<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\Meal;
use App\Models\MenuItem;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function index(Request $request)
    {
        $canteenId = $request->get('canteen_id');

        if (!$canteenId) {
            return response()->json([]);
        }

        $from = now()->startOfWeek()->toDateString();
        $to   = now()->endOfWeek()->toDateString();

        $items = MenuItem::with(['meal.allergens'])
            ->where('canteen_id', $canteenId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $result = [];
        foreach ($items->groupBy('date') as $date => $dayItems) {
            $result[$date] = $dayItems->values()->map(function ($item, $index) {
                $meal        = $item->meal;
                $allergenStr = $meal->allergens
                    ->sortBy(fn ($a) => (int) $a->number)
                    ->pluck('number')
                    ->join(', ');

                $imagePath = $meal->image_path;
                $imageUrl  = null;
                if ($imagePath) {
                    $imageUrl = str_starts_with($imagePath, 'http')
                        ? $imagePath
                        : asset('storage/' . $imagePath);
                }

                return [
                    'id'        => $item->id,
                    'meal_id'   => $meal->id,
                    'badge'     => (string) ($index + 1),
                    'allergens' => $allergenStr,
                    'price'     => number_format((float) $meal->price, 2),
                    'name_sk'   => $meal->name_sk,
                    'name_en'   => $meal->name_en ?? $meal->name_sk,
                    'name_ua'   => $meal->name_ua ?? $meal->name_sk,
                    'name_ru'   => $meal->name_ru ?? $meal->name_sk,
                    'image_url' => $imageUrl,
                ];
            });
        }

        return response()->json($result);
    }

    public function canteens()
    {
        return response()->json(
            Canteen::orderBy('name')->get(['id', 'name', 'address'])
        );
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
