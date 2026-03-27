<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('ingredients')) {
            return;
        }

        $ingredients = [
            ['name' => 'Kuracie mäso', 'unit' => 'kg', 'stock_quantity' => 45.000, 'min_limit' => 8.000],
            ['name' => 'Ryža', 'unit' => 'kg', 'stock_quantity' => 60.000, 'min_limit' => 10.000],
            ['name' => 'Zemiaky', 'unit' => 'kg', 'stock_quantity' => 120.000, 'min_limit' => 20.000],
            ['name' => 'Mlieko', 'unit' => 'l', 'stock_quantity' => 35.000, 'min_limit' => 5.000],
            ['name' => 'Syr', 'unit' => 'kg', 'stock_quantity' => 22.000, 'min_limit' => 4.000],
            ['name' => 'Paradajky', 'unit' => 'kg', 'stock_quantity' => 40.000, 'min_limit' => 6.000],
            ['name' => 'Šošovica', 'unit' => 'kg', 'stock_quantity' => 30.000, 'min_limit' => 6.000],
            ['name' => 'Vajcia', 'unit' => 'ks', 'stock_quantity' => 300.000, 'min_limit' => 80.000],
            ['name' => 'Cestoviny', 'unit' => 'kg', 'stock_quantity' => 34.000, 'min_limit' => 5.000],
            ['name' => 'Smotana', 'unit' => 'l', 'stock_quantity' => 18.000, 'min_limit' => 3.000],
        ];

        foreach ($ingredients as $ingredient) {
            DB::table('ingredients')->updateOrInsert(
                ['name' => $ingredient['name']],
                [
                    'unit' => $ingredient['unit'],
                    'stock_quantity' => $ingredient['stock_quantity'],
                    'min_limit' => $ingredient['min_limit'],
                ]
            );
        }
    }
}
