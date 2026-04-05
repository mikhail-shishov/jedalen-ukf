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
            ['id' => 1, 'name' => 'Tr. A. Hlinku', 'address' => 'Tr. A. Hlinku 1, 94974 Nitra'],
            ['id' => 2, 'name' => 'Štefánikova', 'address' => 'Tr. A. Hlinku 1, 94974 Nitra'],
            ['id' => 3, 'name' => 'Kraskova', 'address' => 'Tr. A. Hlinku 1, 94974 Nitra'],
            ['id' => 4, 'name' => 'Internát Zobor', 'address' => 'Nitra 94901'],
            ['id' => 6, 'name' => 'Chrenova', 'address' => 'Chrenovska 30, 94901 Nitra'],
            ['id' => 7, 'name' => 'Dražovce', 'address' => 'Dražovska 4, 94901 Nitra'],
        ];

        $schedule = [
            'open_time_mon' => '11:00',
            'close_time_mon' => '13:30',
            'open_time_tue' => '11:00',
            'close_time_tue' => '13:30',
            'open_time_wed' => '11:00',
            'close_time_wed' => '13:30',
            'open_time_thu' => '11:00',
            'close_time_thu' => '13:30',
            'open_time_fri' => '11:00',
            'close_time_fri' => '13:30',
            'open_time_sat' => null,
            'close_time_sat' => null,
            'open_time_sun' => null,
            'close_time_sun' => null,
        ];

        foreach ($canteens as $canteen) {
            $payload = array_merge($canteen, $schedule);

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
                $payload['notify_close_offset_min'] = 30;
            }
            if (Schema::hasColumn('canteens', 'is_active')) {
                $payload['is_active'] = true;
            }

            DB::table('canteens')->updateOrInsert(
                ['id' => $canteen['id']],
                $payload
            );
        }
    }
}
