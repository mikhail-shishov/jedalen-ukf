<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MealIngredientSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('meal_ingredients') || !Schema::hasTable('meals') || !Schema::hasTable('ingredients')) {
            return;
        }

        $mealIds = DB::table('meals')->pluck('id', 'name_sk')->toArray();
        $ingredientIds = DB::table('ingredients')->pluck('id', 'name')->toArray();

        $links = [
            ['meal' => 'Kurací rezeň s ryžou', 'ingredient' => 'Kuracie mäso', 'amount' => 0.180],
            ['meal' => 'Kurací rezeň s ryžou', 'ingredient' => 'Ryža', 'amount' => 0.120],
            ['meal' => 'Vyprážaný syr s hranolkami', 'ingredient' => 'Syr', 'amount' => 0.150],
            ['meal' => 'Vyprážaný syr s hranolkami', 'ingredient' => 'Zemiaky', 'amount' => 0.200],
            ['meal' => 'Paradajková polievka', 'ingredient' => 'Paradajky', 'amount' => 0.180],
            ['meal' => 'Šošovicový prívarok s vajcom', 'ingredient' => 'Šošovica', 'amount' => 0.140],
            ['meal' => 'Šošovicový prívarok s vajcom', 'ingredient' => 'Vajcia', 'amount' => 1.000],
            ['meal' => 'Cestoviny carbonara', 'ingredient' => 'Cestoviny', 'amount' => 0.130],
            ['meal' => 'Cestoviny carbonara', 'ingredient' => 'Smotana', 'amount' => 0.050],
        ];

        foreach ($links as $link) {
            $mealId = $mealIds[$link['meal']] ?? null;
            $ingredientId = $ingredientIds[$link['ingredient']] ?? null;

            if (!$mealId || !$ingredientId) {
                continue;
            }

            DB::table('meal_ingredients')->updateOrInsert(
                [
                    'meal_id' => $mealId,
                    'ingredient_id' => $ingredientId,
                ],
                [
                    'amount' => $link['amount'],
                ]
            );
        }
    }
}
