<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $users = [
            [
                'login_id' => '000000',
                'email' => 'admin@ukf.sk',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'role_id' => 4,
                'credit_balance' => 150.00,
            ],
            [
                'login_id' => '100001',
                'email' => 'student1@ukf.sk',
                'first_name' => 'Martin',
                'last_name' => 'Kovač',
                'role_id' => 1,
                'credit_balance' => 35.50,
            ],
            [
                'login_id' => '100002',
                'email' => 'student2@ukf.sk',
                'first_name' => 'Petra',
                'last_name' => 'Novaková',
                'role_id' => 1,
                'credit_balance' => 18.20,
            ],
            [
                'login_id' => '200001',
                'email' => 'worker1@ukf.sk',
                'first_name' => 'Jozef',
                'last_name' => 'Mikula',
                'role_id' => 2,
                'credit_balance' => 61.00,
            ],
            [
                'login_id' => '300001',
                'email' => 'cook1@ukf.sk',
                'first_name' => 'Anna',
                'last_name' => 'Kucharová',
                'role_id' => 3,
                'credit_balance' => 10.00,
            ],
        ];

        foreach ($users as $user) {
            $payload = [
                'login_id' => $user['login_id'],
                'password' => Hash::make('A1bcdefg!'),
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role_id' => $user['role_id'],
                'credit_balance' => $user['credit_balance'],
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('users', 'is_admin')) {
                $payload['is_admin'] = $user['role_id'] === 4;
            }
            if (Schema::hasColumn('users', 'is_first_login')) {
                $payload['is_first_login'] = false;
            }
            if (Schema::hasColumn('users', 'push_enabled')) {
                $payload['push_enabled'] = true;
            }
            if (Schema::hasColumn('users', 'push_locale')) {
                $payload['push_locale'] = 'sk';
            }
            if (Schema::hasColumn('users', 'blocked_allergen_numbers')) {
                $payload['blocked_allergen_numbers'] = json_encode([]);
            }

            DB::table('users')->updateOrInsert(
                ['login_id' => $user['login_id']],
                $payload
            );
        }
    }
}
