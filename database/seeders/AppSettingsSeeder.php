<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        AppSetting::setMany([
            'payments_client_name' => 'Mirko Petrík GASTROMIR',
            'payments_account_name' => 'Mirko Petrík GASTROMIR',
            'payments_bank_account_number' => '51 9273 1010/0900',
            'payments_bank_iban' => 'SK52 0900 0000 0051 9273 1010',
            'payments_bank_name' => 'Slovenskej sporiteľni, a. s.',
            'payments_refund_email' => 'kreditukf@gmail.com',
        ]);
    }
}