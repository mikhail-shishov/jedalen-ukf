@extends('admin.dashboard')

@section('admin_content')
    @php
        $roleLabels = $roleLabels ?? [];
    @endphp

    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Správa používateľov</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="bi bi-plus-circle"></i> Pridať používateľa
        </button>
    </div>

    <form method="GET" action="{{ route('admin.users') }}" class="row g-2 mb-3">
        <div class="col-md-6 col-lg-4">
            <input
                type="search"
                name="q"
                class="form-control"
                value="{{ $searchQuery ?? '' }}"
                placeholder="Hľadať podľa loginu, mena, emailu, roly...">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary">Hľadať</button>
        </div>
        @if(!empty($searchQuery))
            <div class="col-auto">
                <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">Vymazať filter</a>
            </div>
        @endif
    </form>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Opravte chyby vo formulári:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Login</th>
                    <th>Meno</th>
                    <th>Email</th>
                    <th>Rola</th>
                    <th>Kredit</th>
                    <th class="text-end">Akcie</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td><code>{{ $user->login_id }}</code></td>
                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $roleLabels[$user->role->name] ?? $user->role->name }}</span>
                        </td>
                        <td>
                            <strong class="{{ $user->credit_balance < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($user->credit_balance, 2) }} €
                            </strong>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-clock-history"></i> História
                            </a>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#editUserModal" data-id="{{ $user->id }}"
                                data-first_name="{{ $user->first_name }}" data-last_name="{{ $user->last_name }}"
                                data-email="{{ $user->email }}" data-role="{{ $user->role_id }}"
                                data-balance="{{ $user->credit_balance }}">
                                <i class="bi bi-pencil"></i> Upraviť / Kredit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Žiadni používatelia pre zadaný filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {{ $users->links() }}
        </div>
    @endif

    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <input type="hidden" name="create_user" value="1">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vytvoriť používateľa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Login ID</label>
                            <input type="text" name="login_id" class="form-control" value="{{ old('login_id') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Heslo</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Meno</label>
                                <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priezvisko</label>
                                <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rola systému</label>
                            <select name="role_id" class="form-control" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected((int) old('role_id', 1) === (int) $role->id)>
                                        {{ $roleLabels[$role->name] ?? $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Počiatočný kredit (€)</label>
                            <input type="number" step="0.01" name="credit_balance" class="form-control"
                                value="{{ old('credit_balance', '0.00') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-primary">Vytvoriť používateľa</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upraviť používateľa a kredit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Meno</label>
                                <input type="text" name="first_name" id="editFirstName" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priezvisko</label>
                                <input type="text" name="last_name" id="editLastName" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rola systému</label>
                            <select name="role_id" id="editRole" class="form-control">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $roleLabels[$role->name] ?? $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 bg-light p-3 rounded border">
                            <label class="form-label font-weight-bold text-primary">Kreditná bilancia (€)</label>
                            <input type="number" step="0.01" name="credit_balance" id="editBalance"
                                class="form-control form-control-lg text-center" required>
                            <small class="text-muted">Zmena kreditu vytvorí záznam v histórii platieb.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if(old('create_user'))
                const createUserModalElement = document.getElementById('createUserModal');
                if (createUserModalElement) {
                    const createUserModal = new bootstrap.Modal(createUserModalElement);
                    createUserModal.show();
                }
            @endif

            const editUserModal = document.getElementById('editUserModal');
            editUserModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                editUserModal.querySelector('#editUserForm').action = `/admin/users/${button.getAttribute('data-id')}`;
                editUserModal.querySelector('#editFirstName').value = button.getAttribute('data-first_name');
                editUserModal.querySelector('#editLastName').value = button.getAttribute('data-last_name');
                editUserModal.querySelector('#editEmail').value = button.getAttribute('data-email');
                editUserModal.querySelector('#editRole').value = button.getAttribute('data-role');
                editUserModal.querySelector('#editBalance').value = button.getAttribute('data-balance');
            });
        });
    </script>
@endsection