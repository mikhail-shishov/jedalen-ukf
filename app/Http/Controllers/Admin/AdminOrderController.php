<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = trim((string) $request->get('q', ''));

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

        return view('admin.orders', compact('orders', 'searchQuery'));
    }
}