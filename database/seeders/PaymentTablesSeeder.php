<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\PaymentStatus::create(['id' => 1, 'name' => 'Completed']);
        \App\Models\PaymentMethod::create(['id' => 1, 'name' => 'Admin Manual']);
    }
}
