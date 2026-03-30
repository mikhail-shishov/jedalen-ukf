<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $tab = (string) $request->get('tab', 'orders');
        $searchQuery = trim((string) $request->get('q', ''));

        if ($tab === 'exchange') {
            return $this->exchangeTab($request, $searchQuery);
        }

        $ordersQuery = Order::with(['user', 'meal'])->orderBy('created_at', 'desc');

        if ($searchQuery !== '') {
            $safe = '%' . addcslashes($searchQuery, '%_\\') . '%';
            $ordersQuery->where(function ($query) use ($safe, $searchQuery) {
                $query->where('status', 'like', $safe)
                    ->orWhere('price', 'like', $safe)
                    ->orWhereHas('user', function ($userQuery) use ($safe) {
                        $userQuery->where('login_id', 'like', $safe)
                            ->orWhere('email', 'like', $safe)
                            ->orWhere('first_name', 'like', $safe)
                            ->orWhere('last_name', 'like', $safe)
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$safe]);
                    })
                    ->orWhereHas('meal', function ($mealQuery) use ($safe) {
                        $mealQuery->where('raw_name', 'like', $safe)
                            ->orWhere('name_sk', 'like', $safe)
                            ->orWhere('name_en', 'like', $safe)
                            ->orWhere('name_ua', 'like', $safe)
                            ->orWhere('name_ru', 'like', $safe);
                    });

                if (ctype_digit($searchQuery)) {
                    $query->orWhere('id', (int) $searchQuery);
                }
            });
        }

        $orders = $ordersQuery->paginate(50)->withQueryString();

        return view('admin.orders', compact('orders', 'searchQuery', 'tab'));
    }

    private function exchangeTab(Request $request, string $searchQuery)
    {
        $exchangeQuery = DB::table('exchange')
            ->join('orders', 'exchange.order_id', '=', 'orders.id')
            ->join('users as sellers', 'exchange.seller_id', '=', 'sellers.id')
            ->leftJoin('users as buyers', 'exchange.buyer_id', '=', 'buyers.id')
            ->join('menu_items', 'orders.menu_item_id', '=', 'menu_items.id')
            ->leftJoin('meals', 'menu_items.meal_id', '=', 'meals.id')
            ->select(
                'exchange.id',
                'exchange.status',
                'exchange.listing_price',
                'orders.id as order_id',
                'orders.created_at as order_created_at',
                'sellers.login_id as seller_login',
                'sellers.first_name as seller_first',
                'sellers.last_name as seller_last',
                'buyers.login_id as buyer_login',
                'buyers.first_name as buyer_first',
                'buyers.last_name as buyer_last',
                'menu_items.date as meal_date',
                DB::raw('COALESCE(meals.name_sk, "Jedlo") as meal_name'),
                DB::raw('COALESCE(meals.price, exchange.listing_price) as meal_price')
            )
            ->orderBy('menu_items.date', 'desc')
            ->orderBy('exchange.id', 'desc');

        if ($searchQuery !== '') {
            $safe = '%' . addcslashes($searchQuery, '%_\\') . '%';
            $exchangeQuery->where(function ($query) use ($safe) {
                $query->where('sellers.login_id', 'like', $safe)
                    ->orWhere('sellers.first_name', 'like', $safe)
                    ->orWhere('sellers.last_name', 'like', $safe)
                    ->orWhere('buyers.login_id', 'like', $safe)
                    ->orWhere('buyers.first_name', 'like', $safe)
                    ->orWhere('buyers.last_name', 'like', $safe)
                    ->orWhere('meals.name_sk', 'like', $safe)
                    ->orWhere('exchange.status', 'like', $safe);
            });
        }

        $exchanges = $exchangeQuery->paginate(30)->withQueryString();

        return view('admin.orders', compact('exchanges', 'searchQuery', 'tab'));
    }
}