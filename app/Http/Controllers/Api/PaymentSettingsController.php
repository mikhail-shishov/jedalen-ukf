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
            'account_number' => '51 9273 1010/0900',
            'iban' => 'SK52 0900 0000 0051 9273 1010',
            'bank_name' => 'Slovenskej sporiteľni, a. s.',
            'refund_email' => 'kreditukf@gmail.com',
        ];

        if (!Schema::hasTable('app_settings')) {
            return response()->json($defaults);
        }

        $stored = AppSetting::getMap([
            'payments_bank_account_number',
            'payments_bank_iban',
            'payments_bank_name',
            'payments_refund_email',
        ]);

        return response()->json([
            'account_number' => trim((string) ($stored['payments_bank_account_number'] ?? '')) ?: $defaults['account_number'],
            'iban' => trim((string) ($stored['payments_bank_iban'] ?? '')) ?: $defaults['iban'],
            'bank_name' => trim((string) ($stored['payments_bank_name'] ?? '')) ?: $defaults['bank_name'],
            'refund_email' => trim((string) ($stored['payments_refund_email'] ?? '')) ?: $defaults['refund_email'],
        ]);
    }
}
