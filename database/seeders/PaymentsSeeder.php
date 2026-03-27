<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentsSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('payments') || !Schema::hasTable('users') || !Schema::hasTable('payment_statuses') || !Schema::hasTable('payment_methods')) {
            return;
        }

        $statusId = DB::table('payment_statuses')->pluck('id', 'name');
        $methodId = DB::table('payment_methods')->pluck('id', 'name');

        $completed = (int) ($statusId['Completed'] ?? 1);
        $pending = (int) ($statusId['Pending'] ?? 2);
        $failed = (int) ($statusId['Failed'] ?? 3);

        $adminManual = (int) ($methodId['Admin Manual'] ?? 1);
        $creditCard = (int) ($methodId['Credit Card'] ?? 2);
        $bankTransfer = (int) ($methodId['Bank Transfer'] ?? 3);

        $users = DB::table('users')->orderBy('id')->get(['id', 'credit_balance']);

        foreach ($users as $index => $user) {
            $base = max(0, (float) $user->credit_balance - 10);
            $amount = 10 + ($index % 4) * 5;

            $rows = [
                [
                    'external_transaction_id' => 'seed-completed-' . $user->id,
                    'status_id' => $completed,
                    'method_id' => $creditCard,
                    'amount' => $amount,
                    'balance_before' => $base,
                    'balance_after' => $base + $amount,
                    'error_message' => null,
                ],
                [
                    'external_transaction_id' => 'seed-pending-' . $user->id,
                    'status_id' => $pending,
                    'method_id' => $bankTransfer,
                    'amount' => 15.00,
                    'balance_before' => $base + $amount,
                    'balance_after' => $base + $amount,
                    'error_message' => null,
                ],
                [
                    'external_transaction_id' => 'seed-failed-' . $user->id,
                    'status_id' => $failed,
                    'method_id' => $adminManual,
                    'amount' => 8.00,
                    'balance_before' => $base + $amount,
                    'balance_after' => $base + $amount,
                    'error_message' => 'Seed test error',
                ],
            ];

            foreach ($rows as $rIndex => $row) {
                $createdAt = Carbon::now()->subDays(6 - $rIndex)->setTime(11 + $rIndex, 15, 0);

                DB::table('payments')->updateOrInsert(
                    ['external_transaction_id' => $row['external_transaction_id']],
                    [
                        'user_id' => (int) $user->id,
                        'status_id' => $row['status_id'],
                        'method_id' => $row['method_id'],
                        'amount' => $row['amount'],
                        'balance_before' => $row['balance_before'],
                        'balance_after' => $row['balance_after'],
                        'error_message' => $row['error_message'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]
                );
            }
        }
    }
}
