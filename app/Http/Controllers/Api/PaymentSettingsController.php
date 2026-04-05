<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

class PaymentSettingsController extends Controller
{
    public function bankDetails()
    {
        $defaults = [
            'client_name' => 'Mirko Petrík GASTROMIR',
            'account_name' => 'Mirko Petrík GASTROMIR',
            'account_number' => '51 9273 1010/0900',
            'iban' => 'SK52 0900 0000 0051 9273 1010',
            'bank_name' => 'Slovenskej sporiteľni, a. s.',
            'refund_email' => 'kreditukf@gmail.com',
        ];

        if (!Schema::hasTable('app_settings')) {
            return response()->json($defaults);
        }

        $stored = AppSetting::getMap([
            'payments_client_name',
            'payments_account_name',
            'payments_bank_account_number',
            'payments_bank_iban',
            'payments_bank_name',
            'payments_refund_email',
        ]);

        return response()->json([
            'client_name' => trim((string) ($stored['payments_client_name'] ?? '')) ?: $defaults['client_name'],
            'account_name' => trim((string) ($stored['payments_account_name'] ?? '')) ?: $defaults['account_name'],
            'account_number' => trim((string) ($stored['payments_bank_account_number'] ?? '')) ?: $defaults['account_number'],
            'iban' => trim((string) ($stored['payments_bank_iban'] ?? '')) ?: $defaults['iban'],
            'bank_name' => trim((string) ($stored['payments_bank_name'] ?? '')) ?: $defaults['bank_name'],
            'refund_email' => trim((string) ($stored['payments_refund_email'] ?? '')) ?: $defaults['refund_email'],
        ]);
    }
}
