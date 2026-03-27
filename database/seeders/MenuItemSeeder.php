<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('menu_items') || !Schema::hasTable('canteens') || !Schema::hasTable('meals')) {
            return;
        }

        $canteenIds = DB::table('canteens')->pluck('id')->values()->all();
        $mealIds = DB::table('meals')->pluck('id')->values()->all();

        if (!$canteenIds || !$mealIds) {
            return;
        }

        $start = Carbon::today()->subDays(7);
        $days = 21;

        for ($d = 0; $d < $days; $d++) {
            $date = $start->copy()->addDays($d)->toDateString();

            foreach ($canteenIds as $canteenIndex => $canteenId) {
                $dailyCount = min(6, count($mealIds));

                for ($i = 0; $i < $dailyCount; $i++) {
                    $mealIdx = ($d * 3 + $i + $canteenIndex) % count($mealIds);
                    $mealId = $mealIds[$mealIdx];

                    DB::table('menu_items')->updateOrInsert(
                        [
                            'canteen_id' => $canteenId,
                            'meal_id' => $mealId,
                            'date' => $date,
                        ],
                        [
                            'stock_total' => 120,
                            'stock_current' => max(0, 120 - (($d + $i + $canteenIndex) % 70)),
                        ]
                    );
                }
            }
        }
    }
}
