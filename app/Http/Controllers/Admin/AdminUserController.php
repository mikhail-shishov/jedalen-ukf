<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();
        $roles = Role::all();
        return view('admin.users', compact('users', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'role_id' => 'required|exists:roles,id',
            'credit_balance' => 'required|numeric',
        ]);

        $oldBalance = (float) $user->credit_balance;
        $newBalance = (float) $data['credit_balance'];

        try {
            DB::transaction(function () use ($user, $data, $oldBalance, $newBalance) {
                $user->fill($data);
                $saved = $user->save();

                if (!$saved) {
                    throw new \Exception("Eloquent save() vrátil false - dáta sa neuložili do DB.");
                }

                if (abs($oldBalance - $newBalance) > 0.01) {
                    Payment::create([
                        'user_id' => $user->id,
                        'status_id' => 1,
                        'method_id' => 1,
                        'amount' => $newBalance - $oldBalance,
                        'balance_before' => $oldBalance,
                        'balance_after' => $newBalance,
                        'external_transaction_id' => 'ADMIN_MOD_' . auth()->id(),
                        'error_message' => 'Manuálna úprava administrátorom.'
                    ]);
                }
            });

            return redirect()->back()->with('success', 'Používateľ bol úspešne aktualizovaný.');
        } catch (\Exception $e) {
            return "Chyba pri ukladaní: " . $e->getMessage();
        }
    }

    public function show($id)
    {
        $user = User::with(['role', 'payments.status', 'payments.method'])->findOrFail($id);
        $payments = $user->payments()->orderBy('created_at', 'desc')->get();

        return view('admin.users_history', compact('user', 'payments'));
    }
}
