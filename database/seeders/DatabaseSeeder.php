<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PaymentTablesSeeder::class);
        $this->call(AppSettingsSeeder::class);
        $this->call(DemoUsersSeeder::class);

        $this->call(CanteenSeeder::class);
        $this->call(AllergenSeeder::class);
        $this->call(MealSeeder::class);
        $this->call(MealAllergenSeeder::class);

        $this->call(MenuItemSeeder::class);
        $this->call(OrdersSeeder::class);
        $this->call(ExchangeSeeder::class);

        $this->call(PaymentsSeeder::class);

        $this->call(IngredientSeeder::class);
        $this->call(MealIngredientSeeder::class);

        $this->call(ArticleSeeder::class);
        $this->call(ArticleRevisionSeeder::class);
    }
}
