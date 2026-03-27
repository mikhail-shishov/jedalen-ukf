<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('users') || !Schema::hasTable('menu_items')) {
            return;
        }

        $users = DB::table('users')
            ->where('role_id', '!=', 4)
            ->pluck('id')
            ->values()
            ->all();

        $menuItems = DB::table('menu_items')
            ->join('meals', 'menu_items.meal_id', '=', 'meals.id')
            ->select('menu_items.id as menu_item_id', 'menu_items.date', 'meals.price')
            ->orderBy('menu_items.date')
            ->orderBy('menu_items.id')
            ->get();

        if (!$users || $menuItems->isEmpty()) {
            return;
        }

        $today = Carbon::today()->toDateString();

        foreach ($users as $userIndex => $userId) {
            $slice = $menuItems->slice($userIndex * 6, 16)->values();
            if ($slice->isEmpty()) {
                $slice = $menuItems->take(12);
            }

            foreach ($slice as $i => $item) {
                $status = 'ordered';
                if ($item->date < $today) {
                    $status = $i % 5 === 0 ? 'cancelled' : 'collected';
                } elseif ($i % 7 === 0) {
                    $status = 'in_exchange';
                }

                $createdAt = Carbon::parse($item->date)->setTime(9 + ($i % 5), 10, 0);

                DB::table('orders')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'menu_item_id' => (int) $item->menu_item_id,
                    ],
                    [
                        'price_paid' => $item->price ?? 4.50,
                        'status' => $status,
                        'created_at' => $createdAt,
                    ]
                );
            }
        }
    }
}
