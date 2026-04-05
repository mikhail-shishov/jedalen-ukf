<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\Meal;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminMenuController extends Controller
{
    public function index(Request $request)
    {
        $canteens  = Canteen::active()->orderBy('name')->get();
        $date      = $request->get('date', date('Y-m-d'));
        $canteenId = (int) $request->get('canteen_id', optional($canteens->first())->id);

        if ($canteenId && !$canteens->contains('id', $canteenId)) {
            $canteenId = (int) optional($canteens->first())->id;
        }

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
            'canteen_id'  => ['required', Rule::exists('canteens', 'id')->where(fn ($query) => $query->where('is_active', true))],
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
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(50, max(10, (int) $request->get('per_page', 20)));

        $query = Meal::with('allergens')
            ->when($q !== '', function ($query) use ($q) {
                $safe = '%' . addcslashes($q, '%_\\') . '%';
                $query->where('raw_name', 'like', $safe)
                      ->orWhere('name_sk',  'like', $safe)
                      ->orWhere('name_en',  'like', $safe);
            })
            ->orderBy('name_sk');

        $total = (clone $query)->count();

        $meals = $query
            ->forPage($page, $perPage)
            ->get();

        return response()->json([
            'items' => $meals->map(fn ($m) => [
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
            ])->values(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    public function duplicate(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date',
            'canteen_id' => ['required', Rule::exists('canteens', 'id')->where(fn ($query) => $query->where('is_active', true))],
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
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(100, max(10, (int) $request->get('per_page', 30)));

        if (!$canteenId || !Canteen::active()->where('id', $canteenId)->exists()) {
            return response()->json([
                'days' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => false,
                'selected_date' => $date,
            ]);
        }

        $query = MenuItem::where('canteen_id', $canteenId)
            ->whereBetween('date', [date('Y-m-d', strtotime('-30 days')), date('Y-m-d', strtotime('+30 days'))])
            ->select('date')
            ->distinct()
            ->orderBy('date', 'desc');

        $total = (clone $query)->count();

        $daysWithMenu = $query
            ->forPage($page, $perPage)
            ->pluck('date')
            ->toArray();

        return response()->json([
            'days' => $daysWithMenu,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => ($page * $perPage) < $total,
            'selected_date' => $date,
        ]);
    }
}
