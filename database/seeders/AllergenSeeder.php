<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AllergenSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('allergens')) {
            return;
        }

        $allergens = [
            ['number' => '0', 'name' => 'Mäso'],
            ['number' => '1', 'name' => 'Obilniny obsahujúce lepok'],
            ['number' => '2', 'name' => 'Kôrovce a výrobky z nich'],
            ['number' => '3', 'name' => 'Vajcia a výrobky z nich'],
            ['number' => '4', 'name' => 'Ryby a výrobky z nich'],
            ['number' => '5', 'name' => 'Arašidy a výrobky z nich'],
            ['number' => '6', 'name' => 'Sójové zrná a výrobky z nich'],
            ['number' => '7', 'name' => 'Mlieko a výrobky z neho'],
            ['number' => '8', 'name' => 'Orechy a výrobky z nich'],
            ['number' => '9', 'name' => 'Zeler a výrobky z neho'],
            ['number' => '10', 'name' => 'Horčica a výrobky z nej'],
            ['number' => '11', 'name' => 'Sezamové semená a výrobky z nich'],
            ['number' => '12', 'name' => 'Oxid siričitý a siričitany'],
            ['number' => '13', 'name' => 'Vlčí bob a výrobky z neho'],
            ['number' => '14', 'name' => 'Mäkkýše a výrobky z nich'],
        ];

        foreach ($allergens as $allergen) {
            DB::table('allergens')->updateOrInsert(
                ['number' => $allergen['number']],
                ['name' => $allergen['name']]
            );
        }
    }
}
