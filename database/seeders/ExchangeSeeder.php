<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExchangeSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('exchange') || !Schema::hasTable('orders') || !Schema::hasTable('users')) {
            return;
        }

        $users = DB::table('users')->pluck('id')->values()->all();
        if (!$users) {
            return;
        }

        $orders = DB::table('orders')
            ->where('status', 'in_exchange')
            ->orderBy('id')
            ->get(['id', 'user_id', 'price_paid']);

        foreach ($orders as $index => $order) {
            $isSold = $index % 4 === 0;
            $buyerId = null;

            if ($isSold) {
                $buyerId = collect($users)->first(fn (int $id) => $id !== (int) $order->user_id);
            }

            DB::table('exchange')->updateOrInsert(
                ['order_id' => (int) $order->id],
                [
                    'seller_id' => (int) $order->user_id,
                    'buyer_id' => $isSold ? $buyerId : null,
                    'listing_price' => max(1, round(((float) $order->price_paid) - 0.5, 2)),
                    'status' => $isSold ? 'sold' : 'active',
                ]
            );
        }
    }
}
