<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminCookController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();

        $filters = [
            'status' => (string) $request->query('status', ''),
            'date_from' => (string) $request->query('date_from', $today),
            'date_to' => (string) $request->query('date_to', ''),
            'canteen_id' => (string) $request->query('canteen_id', ''),
        ];

        $statusLabels = [
            'ordered' => 'Objednané',
            'collected' => 'Vydané',
            'cancelled' => 'Zrušené',
            'in_exchange' => 'V burze',
        ];

        $statusBadges = [
            'ordered' => 'bg-primary',
            'collected' => 'bg-success',
            'cancelled' => 'bg-danger',
            'in_exchange' => 'bg-warning text-dark',
        ];

        $hasIngredientsTable = Schema::hasTable('ingredients');
        $hasMealIngredientsTable = Schema::hasTable('meal_ingredients');

        $canteens = DB::table('canteens')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $ordersQuery = DB::table('orders')
            ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
            ->leftJoin('meals', 'menu_items.meal_id', '=', 'meals.id')
            ->leftJoin('canteens', 'menu_items.canteen_id', '=', 'canteens.id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->selectRaw('orders.id')
            ->selectRaw('orders.status')
            ->selectRaw('menu_items.date as meal_date')
            ->selectRaw('COALESCE(meals.name_sk, "-") as meal_name')
            ->selectRaw('COALESCE(canteens.name, "-") as canteen_name')
            ->selectRaw('COALESCE(users.first_name, "") as first_name')
            ->selectRaw('COALESCE(users.last_name, "") as last_name');

        if ($filters['date_from'] !== '') {
            $ordersQuery->whereDate('menu_items.date', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== '') {
            $ordersQuery->whereDate('menu_items.date', '<=', $filters['date_to']);
        }
        if ($filters['status'] !== '') {
            $ordersQuery->where('orders.status', $filters['status']);
        }
        if ($filters['canteen_id'] !== '') {
            $ordersQuery->where('menu_items.canteen_id', (int) $filters['canteen_id']);
        }

        $incomingOrders = $ordersQuery
            ->orderBy('menu_items.date')
            ->orderByDesc('orders.created_at')
            ->limit(150)
            ->get();

        $stockQuery = DB::table('menu_items')
            ->leftJoin('meals', 'menu_items.meal_id', '=', 'meals.id')
            ->leftJoin('canteens', 'menu_items.canteen_id', '=', 'canteens.id')
            ->selectRaw('menu_items.id')
            ->selectRaw('menu_items.date')
            ->selectRaw('menu_items.stock_total')
            ->selectRaw('menu_items.stock_current')
            ->selectRaw('COALESCE(meals.name_sk, "-") as meal_name')
            ->selectRaw('COALESCE(canteens.name, "-") as canteen_name');

        if ($filters['date_from'] !== '') {
            $stockQuery->whereDate('menu_items.date', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== '') {
            $stockQuery->whereDate('menu_items.date', '<=', $filters['date_to']);
        }
        if ($filters['canteen_id'] !== '') {
            $stockQuery->where('menu_items.canteen_id', (int) $filters['canteen_id']);
        }

        $stockItems = $stockQuery
            ->orderBy('menu_items.date')
            ->orderBy('menu_items.stock_current')
            ->limit(150)
            ->get();

        $ingredients = collect();
        $meals = collect();
        $mealIngredientRows = collect();

        if ($hasIngredientsTable) {
            $ingredients = DB::table('ingredients')
                ->select('id', 'name', 'unit', 'stock_quantity', 'min_limit', 'updated_at')
                ->orderBy('name')
                ->get();
        }

        if ($hasMealIngredientsTable && $hasIngredientsTable) {
            $meals = DB::table('meals')
                ->select('id', DB::raw('COALESCE(name_sk, raw_name, CONCAT("Jedlo #", id)) as label'))
                ->orderBy('label')
                ->get();

            $mealIngredientRows = DB::table('meal_ingredients as mi')
                ->join('meals as m', 'mi.meal_id', '=', 'm.id')
                ->join('ingredients as i', 'mi.ingredient_id', '=', 'i.id')
                ->selectRaw('mi.meal_id')
                ->selectRaw('mi.ingredient_id')
                ->selectRaw('mi.amount')
                ->selectRaw('COALESCE(m.name_sk, m.raw_name, CONCAT("Jedlo #", m.id)) as meal_name')
                ->selectRaw('i.name as ingredient_name')
                ->selectRaw('i.unit as ingredient_unit')
                ->orderBy('meal_name')
                ->orderBy('ingredient_name')
                ->get();
        }

        return view('admin.cook', [
            'incomingOrders' => $incomingOrders,
            'stockItems' => $stockItems,
            'filters' => $filters,
            'statusLabels' => $statusLabels,
            'statusBadges' => $statusBadges,
            'canteens' => $canteens,
            'hasIngredientsTable' => $hasIngredientsTable,
            'hasMealIngredientsTable' => $hasMealIngredientsTable,
            'ingredients' => $ingredients,
            'meals' => $meals,
            'mealIngredientRows' => $mealIngredientRows,
        ]);
    }

    public function storeIngredient(Request $request)
    {
        if (!Schema::hasTable('ingredients')) {
            return redirect()->route('admin.cook')->withErrors([
                'ingredients' => 'Tabuľka ingredients neexistuje v databáze.',
            ]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name',
            'unit' => 'required|in:kg,g,l,ml,ks',
            'stock_quantity' => 'required|numeric|min:0',
            'min_limit' => 'required|numeric|min:0',
        ], [
            'name.required' => 'Názov suroviny je povinný.',
            'name.unique' => 'Surovina s týmto názvom už existuje.',
            'unit.required' => 'Jednotka je povinná.',
            'unit.in' => 'Neplatná jednotka.',
            'stock_quantity.required' => 'Skladové množstvo je povinné.',
            'stock_quantity.numeric' => 'Skladové množstvo musí byť číslo.',
            'stock_quantity.min' => 'Skladové množstvo nemôže byť záporné.',
            'min_limit.required' => 'Minimálny limit je povinný.',
            'min_limit.numeric' => 'Minimálny limit musí byť číslo.',
            'min_limit.min' => 'Minimálny limit nemôže byť záporný.',
        ]);

        DB::table('ingredients')->insert([
            'name' => $data['name'],
            'unit' => $data['unit'],
            'stock_quantity' => $data['stock_quantity'],
            'min_limit' => $data['min_limit'],
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.cook')->with('success', 'Surovina bola pridaná.');
    }

    public function updateIngredient(Request $request, int $id)
    {
        if (!Schema::hasTable('ingredients')) {
            return redirect()->route('admin.cook')->withErrors([
                'ingredients' => 'Tabuľka ingredients neexistuje v databáze.',
            ]);
        }

        $data = $request->validate([
            'stock_quantity' => 'required|numeric|min:0',
            'min_limit' => 'required|numeric|min:0',
        ], [
            'stock_quantity.required' => 'Skladové množstvo je povinné.',
            'stock_quantity.numeric' => 'Skladové množstvo musí byť číslo.',
            'stock_quantity.min' => 'Skladové množstvo nemôže byť záporné.',
            'min_limit.required' => 'Minimálny limit je povinný.',
            'min_limit.numeric' => 'Minimálny limit musí byť číslo.',
            'min_limit.min' => 'Minimálny limit nemôže byť záporný.',
        ]);

        DB::table('ingredients')
            ->where('id', $id)
            ->update([
                'stock_quantity' => $data['stock_quantity'],
                'min_limit' => $data['min_limit'],
                'updated_at' => now(),
            ]);

        return redirect()->route('admin.cook')->with('success', 'Sklad suroviny bol aktualizovaný.');
    }

    public function upsertMealIngredient(Request $request)
    {
        if (!Schema::hasTable('meal_ingredients')) {
            return redirect()->route('admin.cook')->withErrors([
                'ingredients' => 'Tabuľka meal_ingredients neexistuje v databáze.',
            ]);
        }

        $data = $request->validate([
            'meal_id' => 'required|exists:meals,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'amount' => 'required|numeric|min:0.001',
        ], [
            'meal_id.required' => 'Jedlo je povinné.',
            'meal_id.exists' => 'Vybrané jedlo neexistuje.',
            'ingredient_id.required' => 'Surovina je povinná.',
            'ingredient_id.exists' => 'Vybraná surovina neexistuje.',
            'amount.required' => 'Množstvo na porciu je povinné.',
            'amount.numeric' => 'Množstvo musí byť číslo.',
            'amount.min' => 'Množstvo musí byť väčšie ako 0.',
        ]);

        DB::table('meal_ingredients')->updateOrInsert(
            [
                'meal_id' => $data['meal_id'],
                'ingredient_id' => $data['ingredient_id'],
            ],
            [
                'amount' => $data['amount'],
            ]
        );

        return redirect()->route('admin.cook')->with('success', 'Norma suroviny pre jedlo bola uložená.');
    }
}
