<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MealSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('meals')) {
            return;
        }

        $meals = [
            ['raw_name' => 'Kurací rezeň, ryža', 'name_sk' => 'Kurací rezeň s ryžou', 'name_en' => 'Chicken schnitzel with rice', 'name_ua' => 'Курячий шніцель з рисом', 'name_ru' => 'Куриный шницель с рисом', 'price' => 4.80],
            ['raw_name' => 'Bryndzové halušky', 'name_sk' => 'Bryndzové halušky', 'name_en' => 'Potato dumplings with sheep cheese', 'name_ua' => 'Картопляні галушки з бринзою', 'name_ru' => 'Картофельные галушки с брынзой', 'price' => 5.10],
            ['raw_name' => 'Sviečková na smotane', 'name_sk' => 'Sviečková na smotane', 'name_en' => 'Cream sirloin', 'name_ua' => 'Яловичина у вершковому соусі', 'name_ru' => 'Говядина в сливочном соусе', 'price' => 5.90],
            ['raw_name' => 'Paradajková polievka', 'name_sk' => 'Paradajková polievka', 'name_en' => 'Tomato soup', 'name_ua' => 'Томатний суп', 'name_ru' => 'Томатный суп', 'price' => 2.20],
            ['raw_name' => 'Hovädzí guláš', 'name_sk' => 'Hovädzí guláš', 'name_en' => 'Beef goulash', 'name_ua' => 'Яловичий гуляш', 'name_ru' => 'Говяжий гуляш', 'price' => 5.60],
            ['raw_name' => 'Vyprážaný syr', 'name_sk' => 'Vyprážaný syr s hranolkami', 'name_en' => 'Fried cheese with fries', 'name_ua' => 'Смажений сир з картоплею фрі', 'name_ru' => 'Жареный сыр с картофелем фри', 'price' => 4.90],
            ['raw_name' => 'Šošovicový prívarok', 'name_sk' => 'Šošovicový prívarok s vajcom', 'name_en' => 'Lentil stew with egg', 'name_ua' => 'Сочевичне рагу з яйцем', 'name_ru' => 'Чечевичное рагу с яйцом', 'price' => 4.40],
            ['raw_name' => 'Cestoviny carbonara', 'name_sk' => 'Cestoviny carbonara', 'name_en' => 'Pasta carbonara', 'name_ua' => 'Паста карбонара', 'name_ru' => 'Паста карбонара', 'price' => 5.20],
            ['raw_name' => 'Kurací vývar', 'name_sk' => 'Kurací vývar s rezancami', 'name_en' => 'Chicken broth with noodles', 'name_ua' => 'Курячий бульйон з локшиною', 'name_ru' => 'Куриный бульон с лапшой', 'price' => 2.50],
            ['raw_name' => 'Rizoto zeleninové', 'name_sk' => 'Zeleninové rizoto', 'name_en' => 'Vegetable risotto', 'name_ua' => 'Овочеве різото', 'name_ru' => 'Овощное ризотто', 'price' => 4.60],
            ['raw_name' => 'Pečené kura', 'name_sk' => 'Pečené kura so zemiakmi', 'name_en' => 'Roast chicken with potatoes', 'name_ua' => 'Запечена курка з картоплею', 'name_ru' => 'Запеченная курица с картофелем', 'price' => 5.30],
            ['raw_name' => 'Segedínsky guláš', 'name_sk' => 'Segedínsky guláš s knedľou', 'name_en' => 'Szeged goulash with dumpling', 'name_ua' => 'Сегедінський гуляш з кнедлем', 'name_ru' => 'Сегединский гуляш с кнедлем', 'price' => 5.70],
        ];

        foreach ($meals as $meal) {
            DB::table('meals')->updateOrInsert(
                ['name_sk' => $meal['name_sk']],
                [
                    'raw_name' => $meal['raw_name'],
                    'name_en' => $meal['name_en'],
                    'name_ua' => $meal['name_ua'],
                    'name_ru' => $meal['name_ru'],
                    'price' => $meal['price'],
                ]
            );
        }
    }
}
