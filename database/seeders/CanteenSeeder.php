<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CanteenSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('canteens')) {
            return;
        }

        $canteens = [
            ['name' => 'Tr. A. Hlinku', 'address' => 'Tr. A. Hlinku 1, 94974 Nitra'],
            ['name' => 'Chrenova', 'address' => 'Chrenovska 30, 94901 Nitra'],
            ['name' => 'Dražovce', 'address' => 'Dražovska 4, 94901 Nitra'],
        ];

        foreach ($canteens as $canteen) {
            $payload = $canteen;
            if (Schema::hasColumn('canteens', 'timezone')) {
                $payload['timezone'] = 'Europe/Bratislava';
            }
            if (Schema::hasColumn('canteens', 'notifications_enabled')) {
                $payload['notifications_enabled'] = true;
            }
            if (Schema::hasColumn('canteens', 'notify_open_offset_min')) {
                $payload['notify_open_offset_min'] = 30;
            }
            if (Schema::hasColumn('canteens', 'notify_close_offset_min')) {
                $payload['notify_close_offset_min'] = 45;
            }

            DB::table('canteens')->updateOrInsert(
                ['name' => $canteen['name']],
                $payload
            );
        }
    }
}
