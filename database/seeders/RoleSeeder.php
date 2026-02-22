<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            1 => 'STUDENT',
            2 => 'WORKER',
            3 => 'COOK',
            4 => 'ADMIN',
        ];

        foreach ($roles as $id => $name) {
            DB::table('roles')->updateOrInsert(
                ['id' => $id],
                ['name' => $name]
            );
        }
    }
}