<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RoleSeeder::class);
        DB::table('users')->updateOrInsert(
            ['login_id' => '000000'],
            [
                'login_id' => '000000',
                'password' => Hash::make('A1bcdefg!'),
                'email' => 'admin@ukf.sk',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'role_id' => 4,
                'credit_balance' => 50.00,
                'updated_at' => now()
            ]
        );

        $canteenId = DB::table('canteens')->insertGetId([
            'name' => 'Tr. A. Hlinku',
            'address' => 'Tr. A. Hlinku 1, 94974 Nitra'
        ]);

        $meals = [
            [
                'name_sk' => 'Kurací rezeň s zemiakovou kašou',
                'name_en' => 'Chicken schnitzel with mashed potatoes',
                'price' => 4.20
            ],
            [
                'name_sk' => 'Bryndzové halušky',
                'name_en' => 'Potato dumplings with sheep cheese',
                'price' => 4.20
            ]
        ];

        foreach ($meals as $meal) {
            DB::table('meals')->updateOrInsert(
                ['name_sk' => $meal['name_sk']],
                $meal
            );
        }
    }
}
