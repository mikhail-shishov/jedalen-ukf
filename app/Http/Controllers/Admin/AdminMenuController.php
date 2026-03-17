<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\Meal;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class AdminMenuController extends Controller
{
    public function index(Request $request)
    {
        $canteens  = Canteen::orderBy('name')->get();
        $date      = $request->get('date', date('Y-m-d'));
        $canteenId = $request->get('canteen_id', optional($canteens->first())->id);

        $menuItems = $canteenId
            ? MenuItem::with(['meal.allergens'])
                ->where('canteen_id', $canteenId)
                ->where('date', $date)
                ->orderBy('id')
                ->get()
            : collect();

        return view('admin.menu', compact('canteens', 'date', 'canteenId', 'menuItems'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'meal_id'     => 'required|exists:meals,id',
            'canteen_id'  => 'required|exists:canteens,id',
            'date'        => 'required|date',
            'stock_total' => 'nullable|integer|min:1|max:9999',
        ]);

        $already = MenuItem::where('meal_id', $request->meal_id)
            ->where('canteen_id', $request->canteen_id)
            ->where('date', $request->date)
            ->exists();

        if ($already) {
            return redirect()->back()->with('error', 'Toto jedlo je už v menu pre tento deň a jedáleň.');
        }

        $stock = (int) $request->input('stock_total', 100);

        MenuItem::create([
            'meal_id'       => $request->meal_id,
            'canteen_id'    => $request->canteen_id,
            'date'          => $request->date,
            'stock_total'   => $stock,
            'stock_current' => $stock,
        ]);

        return redirect()->back()->with('success', 'Jedlo bolo pridané do denného menu.');
    }

    public function destroy($id)
    {
        MenuItem::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Jedlo bolo odstránené z denného menu.');
    }

    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        $meals = Meal::with('allergens')
            ->when($q !== '', function ($query) use ($q) {
                $safe = '%' . addcslashes($q, '%_\\') . '%';
                $query->where('raw_name', 'like', $safe)
                      ->orWhere('name_sk',  'like', $safe)
                      ->orWhere('name_en',  'like', $safe);
            })
            ->orderBy('name_sk')
            ->limit(40)
            ->get();

        return response()->json($meals->map(fn ($m) => [
            'id'        => $m->id,
            'raw_name'  => $m->raw_name,
            'name_sk'   => $m->name_sk,
            'price'     => number_format((float) $m->price, 2),
            'allergens' => $m->allergens
                ->sortBy(fn ($a) => (int) $a->number)
                ->pluck('number')
                ->values(),
            'image_url' => $m->image_path
                ? (str_starts_with($m->image_path, 'http')
                    ? $m->image_path
                    : asset('storage/' . $m->image_path))
                : null,
        ]));
    }

    public function duplicate(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date',
            'canteen_id' => 'required|exists:canteens,id',
        ]);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $canteenId = $request->input('canteen_id');

        // Проверяем, что есть меню на день-источник
        $sourceMenu = MenuItem::where('canteen_id', $canteenId)
            ->where('date', $fromDate)
            ->get();

        if ($sourceMenu->isEmpty()) {
            return redirect()->back()->with('error', 'Nenájdené menu pre duplikáciu z vybraného dňa.');
        }

        // Удаляем существующее меню на день-назначение
        MenuItem::where('canteen_id', $canteenId)
            ->where('date', $toDate)
            ->delete();

        // Копируем меню
        $createdCount = 0;
        foreach ($sourceMenu as $item) {
            MenuItem::create([
                'meal_id'       => $item->meal_id,
                'canteen_id'    => $canteenId,
                'date'          => $toDate,
                'stock_total'   => $item->stock_total,
                'stock_current' => $item->stock_current,
            ]);
            $createdCount++;
        }

        return redirect()->back()->with('success', "Menu bolo úspešne duplikované! Spolu: $createdCount jedál");
    }

    public function getDays(Request $request)
    {
        $canteenId = $request->get('canteen_id');
        $date = $request->get('date', date('Y-m-d'));

        if (!$canteenId) {
            return response()->json([]);
        }

        // Получаем все дни с меню в этой столовой за последние 60 дней в будущее
        $daysWithMenu = MenuItem::where('canteen_id', $canteenId)
            ->whereBetween('date', [date('Y-m-d', strtotime('-30 days')), date('Y-m-d', strtotime('+30 days'))])
            ->select('date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->toArray();

        return response()->json($daysWithMenu);
    }
}
