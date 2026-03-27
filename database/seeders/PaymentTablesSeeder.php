<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Schema::hasTable('payment_statuses')) {
            $statuses = [
                1 => 'Completed',
                2 => 'Pending',
                3 => 'Failed',
            ];

            foreach ($statuses as $id => $name) {
                DB::table('payment_statuses')->updateOrInsert(
                    ['id' => $id],
                    ['name' => $name]
                );
            }
        }

        if (Schema::hasTable('payment_methods')) {
            $methods = [
                1 => 'Admin Manual',
                2 => 'Credit Card',
                3 => 'Bank Transfer',
            ];

            foreach ($methods as $id => $name) {
                DB::table('payment_methods')->updateOrInsert(
                    ['id' => $id],
                    ['name' => $name]
                );
            }
        }
    }
}
