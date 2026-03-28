<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminPaymentSettingsController extends Controller
{
    private const ACCOUNT_NUMBER_KEY = 'payments_bank_account_number';
    private const IBAN_KEY = 'payments_bank_iban';
    private const BANK_NAME_KEY = 'payments_bank_name';
    private const REFUND_EMAIL_KEY = 'payments_refund_email';

    private function defaults(): array
    {
        return [
            'account_number' => '51 9273 1010/0900',
            'iban' => 'SK52 0900 0000 0051 9273 1010',
            'bank_name' => 'Slovenskej sporiteľni, a. s.',
            'refund_email' => 'kreditukf@gmail.com',
        ];
    }

    private function values(): array
    {
        $defaults = $this->defaults();

        if (!Schema::hasTable('app_settings')) {
            return $defaults;
        }

        $stored = AppSetting::getMap([
            self::ACCOUNT_NUMBER_KEY,
            self::IBAN_KEY,
            self::BANK_NAME_KEY,
            self::REFUND_EMAIL_KEY,
        ]);

        return [
            'account_number' => trim((string) ($stored[self::ACCOUNT_NUMBER_KEY] ?? '')) ?: $defaults['account_number'],
            'iban' => trim((string) ($stored[self::IBAN_KEY] ?? '')) ?: $defaults['iban'],
            'bank_name' => trim((string) ($stored[self::BANK_NAME_KEY] ?? '')) ?: $defaults['bank_name'],
            'refund_email' => trim((string) ($stored[self::REFUND_EMAIL_KEY] ?? '')) ?: $defaults['refund_email'],
        ];
    }

    public function index(): View
    {
        return view('admin.payments_settings', [
            'bankDetails' => $this->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_number' => ['required', 'string', 'max:120'],
            'iban' => ['required', 'string', 'max:120'],
            'bank_name' => ['required', 'string', 'max:180'],
            'refund_email' => ['required', 'email', 'max:120'],
        ]);

        if (!Schema::hasTable('app_settings')) {
            return redirect()->back()->with('error', 'Tabuľka app_settings neexistuje. Spustite migrácie.');
        }

        AppSetting::setMany([
            self::ACCOUNT_NUMBER_KEY => trim($validated['account_number']),
            self::IBAN_KEY => trim($validated['iban']),
            self::BANK_NAME_KEY => trim($validated['bank_name']),
            self::REFUND_EMAIL_KEY => trim($validated['refund_email']),
        ]);

        return redirect()->back()->with('success', 'Platobné údaje boli aktualizované.');
    }
}
