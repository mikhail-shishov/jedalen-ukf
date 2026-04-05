<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        $now = now();

        foreach ([
            'payments_client_name' => 'Mirko Petrík GASTROMIR',
            'payments_account_name' => 'Mirko Petrík GASTROMIR',
        ] as $key => $value) {
            if (!DB::table('app_settings')->where('key', $key)->exists()) {
                DB::table('app_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')
            ->whereIn('key', ['payments_client_name', 'payments_account_name'])
            ->delete();
    }
};