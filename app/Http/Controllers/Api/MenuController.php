<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\Meal;
use App\Models\MenuItem;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $from = now()->toDateString();

        $items = MenuItem::with(['meal.allergens'])
            ->where('canteen_id', $canteenId)
            ->whereDate('date', '>=', $from)
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
                    'allergen_numbers' => $meal->allergens
                        ->sortBy(fn ($a) => (int) $a->number)
                        ->pluck('number')
                        ->map(fn ($value) => (int) $value)
                        ->values()
                        ->all(),
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
        $select = ['id', 'name', 'address'];

        if (Schema::hasColumn('canteens', 'timezone')) {
            $select[] = 'timezone';
        }
        if (Schema::hasColumn('canteens', 'notifications_enabled')) {
            $select[] = 'notifications_enabled';
        }

        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        foreach ($days as $day) {
            $openColumn = 'open_time_' . $day;
            $closeColumn = 'close_time_' . $day;

            if (Schema::hasColumn('canteens', $openColumn)) {
                $select[] = $openColumn;
            }
            if (Schema::hasColumn('canteens', $closeColumn)) {
                $select[] = $closeColumn;
            }
        }

        if (Schema::hasColumn('canteens', 'notify_open_offset_min')) {
            $select[] = 'notify_open_offset_min';
        }
        if (Schema::hasColumn('canteens', 'notify_close_offset_min')) {
            $select[] = 'notify_close_offset_min';
        }

        $canteens = Canteen::query()
            ->orderBy('name')
            ->get($select);

        $closuresByCanteen = [];
        if (
            Schema::hasTable('canteen_closures')
            && Schema::hasColumn('canteen_closures', 'canteen_id')
            && Schema::hasColumn('canteen_closures', 'date')
            && Schema::hasColumn('canteen_closures', 'is_closed')
        ) {
            $today = now()->toDateString();
            $until = now()->addMonths(6)->toDateString();

            $rows = DB::table('canteen_closures')
                ->whereBetween('date', [$today, $until])
                ->orderBy('date')
                ->get();

            foreach ($rows as $row) {
                $canteenId = (int) ($row->canteen_id ?? 0);
                if ($canteenId <= 0) {
                    continue;
                }

                if (!array_key_exists($canteenId, $closuresByCanteen)) {
                    $closuresByCanteen[$canteenId] = [];
                }

                $closuresByCanteen[$canteenId][] = [
                    'date' => (string) ($row->date ?? ''),
                    'is_closed' => (bool) ($row->is_closed ?? false),
                    'open_time' => isset($row->open_time) ? (string) $row->open_time : null,
                    'close_time' => isset($row->close_time) ? (string) $row->close_time : null,
                    'reason' => isset($row->reason) ? (string) $row->reason : null,
                ];
            }
        }

        $payload = $canteens->map(function ($canteen) use ($closuresByCanteen, $days) {
            $id = (int) $canteen->id;
            $schedule = [];

            foreach ($days as $day) {
                $openColumn = 'open_time_' . $day;
                $closeColumn = 'close_time_' . $day;
                $schedule[$day] = [
                    'open_time' => isset($canteen->{$openColumn}) ? (string) $canteen->{$openColumn} : null,
                    'close_time' => isset($canteen->{$closeColumn}) ? (string) $canteen->{$closeColumn} : null,
                ];
            }

            return [
                'id' => $id,
                'name' => (string) $canteen->name,
                'address' => (string) ($canteen->address ?? ''),
                'timezone' => isset($canteen->timezone) ? (string) $canteen->timezone : 'Europe/Bratislava',
                'notifications_enabled' => isset($canteen->notifications_enabled) ? (bool) $canteen->notifications_enabled : true,
                'notify_open_offset_min' => isset($canteen->notify_open_offset_min) ? (int) $canteen->notify_open_offset_min : 30,
                'notify_close_offset_min' => isset($canteen->notify_close_offset_min) ? (int) $canteen->notify_close_offset_min : 30,
                'schedule' => $schedule,
                'closures' => $closuresByCanteen[$id] ?? [],
            ];
        })->values();

        return response()->json($payload);
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
