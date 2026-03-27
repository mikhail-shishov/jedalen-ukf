<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MealAllergenSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('meals_has_allergens') || !Schema::hasTable('meals') || !Schema::hasTable('allergens')) {
            return;
        }

        $allergenByNumber = DB::table('allergens')->pluck('id', 'number')->toArray();
        $mealByName = DB::table('meals')->pluck('id', 'name_sk')->toArray();

        $mapping = [
            'Kurací rezeň s ryžou' => ['1'],
            'Bryndzové halušky' => ['1', '7'],
            'Sviečková na smotane' => ['1', '7', '9'],
            'Paradajková polievka' => [],
            'Hovädzí guláš' => ['1'],
            'Vyprážaný syr s hranolkami' => ['1', '3', '7'],
            'Šošovicový prívarok s vajcom' => ['3'],
            'Cestoviny carbonara' => ['1', '3', '7'],
            'Kurací vývar s rezancami' => ['1'],
            'Zeleninové rizoto' => [],
            'Pečené kura so zemiakmi' => [],
            'Segedínsky guláš s knedľou' => ['1', '7'],
        ];

        foreach ($mapping as $mealName => $numbers) {
            $mealId = $mealByName[$mealName] ?? null;
            if (!$mealId) {
                continue;
            }

            foreach ($numbers as $number) {
                $allergenId = $allergenByNumber[$number] ?? null;
                if (!$allergenId) {
                    continue;
                }

                DB::table('meals_has_allergens')->updateOrInsert(
                    ['meals_id' => $mealId, 'allergens_id' => $allergenId],
                    ['meals_id' => $mealId, 'allergens_id' => $allergenId]
                );
            }
        }
    }
}
