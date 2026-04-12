<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = trim((string) $request->get('q', ''));

        $usersQuery = User::with('role')->orderBy('id', 'desc');

        if ($searchQuery !== '') {
            $safe = '%' . addcslashes($searchQuery, '%_\\') . '%';
            $usersQuery->where(function ($query) use ($safe, $searchQuery) {
                $query->where('login_id', 'like', $safe)
                    ->orWhere('email', 'like', $safe)
                    ->orWhere('first_name', 'like', $safe)
                    ->orWhere('last_name', 'like', $safe)
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$safe])
                    ->orWhereHas('role', function ($roleQuery) use ($safe) {
                        $roleQuery->where('name', 'like', $safe);
                    });

                if (is_numeric($searchQuery)) {
                    $query->orWhere('credit_balance', (float) $searchQuery);
                }

                if (ctype_digit($searchQuery)) {
                    $query->orWhere('id', (int) $searchQuery);
                }
            });
        }

        $users = $usersQuery->paginate(50)->withQueryString();
        $roles = Role::all();
        $roleLabels = [
            'STUDENT' => 'Študent',
            'WORKER' => 'Zamestnanec',
            'COOK' => 'Kuchár',
            'ADMIN' => 'Administrátor',
        ];

        return view('admin.users', compact('users', 'roles', 'roleLabels', 'searchQuery'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:100',
                'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role_id' => 'required|exists:roles,id',
            'credit_balance' => 'required|numeric|min:0',
        ], [
            'first_name.max' => 'Meno môže mať najviac :max znakov.',
            'last_name.max' => 'Priezvisko môže mať najviac :max znakov.',
            'email.email' => 'Zadajte platný e-mail.',
            'email.regex' => 'E-mail musí obsahovať doménu (napr. meno@domena.sk).',
            'email.max' => 'E-mail môže mať najviac :max znakov.',
            'email.unique' => 'Tento e-mail už existuje.',
            'role_id.required' => 'Rola je povinná.',
            'role_id.exists' => 'Vybraná rola neexistuje.',
            'credit_balance.required' => 'Kredit je povinný.',
            'credit_balance.numeric' => 'Kredit musí byť číslo.',
        ]);

        try {
            DB::transaction(function () use ($user, $data) {
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                $adminRoleId = Role::where('name', 'ADMIN')->value('id');
                $data['is_admin'] = $adminRoleId !== null && (int) $data['role_id'] === (int) $adminRoleId;
                $oldBalance = (float) $lockedUser->credit_balance;
                $newBalance = (float) $data['credit_balance'];

                $lockedUser->fill($data);
                $saved = $lockedUser->save();

                if (!$saved) {
                    throw new \Exception("Eloquent save() vrátil false - dáta sa neuložili do DB.");
                }

                if (abs($oldBalance - $newBalance) > 0.01) {
                    Payment::create([
                        'user_id' => $lockedUser->id,
                        'status_id' => 1,
                        'method_id' => 1,
                        'amount' => $newBalance - $oldBalance,
                        'balance_before' => $oldBalance,
                        'balance_after' => $newBalance,
                        'external_transaction_id' => 'ADMIN_MOD_' . (Auth::id() ?? 'unknown') . '_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(6)),
                        'error_message' => 'Manuálna úprava administrátorom.'
                    ]);
                }
            });

            return redirect()->back()->with('success', 'Používateľ bol úspešne aktualizovaný.');
        } catch (\Exception $e) {
            return "Chyba pri ukladaní: " . $e->getMessage();
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'login_id' => 'required|string|max:100|unique:users,login_id',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:100',
                'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
                'unique:users,email',
            ],
            'role_id' => 'required|exists:roles,id',
            'credit_balance' => 'nullable|numeric|min:0',
        ], [
            'login_id.required' => 'Prihlasovacie ID je povinné.',
            'login_id.max' => 'Prihlasovacie ID môže mať najviac :max znakov.',
            'login_id.unique' => 'Toto prihlasovacie ID už existuje.',
            'password.required' => 'Heslo je povinné.',
            'password.min' => 'Heslo musí mať aspoň :min znakov.',
            'first_name.required' => 'Meno je povinné.',
            'first_name.max' => 'Meno môže mať najviac :max znakov.',
            'last_name.required' => 'Priezvisko je povinné.',
            'last_name.max' => 'Priezvisko môže mať najviac :max znakov.',
            'email.email' => 'Zadajte platný e-mail.',
            'email.regex' => 'E-mail musí obsahovať doménu (napr. meno@domena.sk).',
            'email.max' => 'E-mail môže mať najviac :max znakov.',
            'email.unique' => 'Tento e-mail už existuje.',
            'role_id.required' => 'Rola je povinná.',
            'role_id.exists' => 'Vybraná rola neexistuje.',
            'credit_balance.numeric' => 'Kredit musí byť číslo.',
        ]);

        $adminRoleId = Role::where('name', 'ADMIN')->value('id');
        $isAdmin = $adminRoleId !== null && (int) $data['role_id'] === (int) $adminRoleId;

        User::create([
            'login_id' => $data['login_id'],
            'password' => Hash::make($data['password']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'role_id' => $data['role_id'],
            'credit_balance' => $data['credit_balance'] ?? 0,
            'is_admin' => $isAdmin,
        ]);

        return redirect()->back()->with('success', 'Používateľ bol úspešne vytvorený.');
    }

    public function show($id)
    {
        $user = User::with(['role', 'payments.status', 'payments.method'])->findOrFail($id);
        $payments = $user->payments()->orderBy('created_at', 'desc')->get();

        return view('admin.users_history', compact('user', 'payments'));
    }
}
