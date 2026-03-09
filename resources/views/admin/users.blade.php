@extends('admin.dashboard')

@section('admin_content')
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Správa používateľov</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
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
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td><code>{{ $user->login_id }}</code></td>
                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $user->role->name }}</span>
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
                @endforeach
            </tbody>
        </table>
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
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
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