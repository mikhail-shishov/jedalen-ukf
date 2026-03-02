@extends('admin.dashboard')

@section('admin_content')
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Správa jedální</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCanteenModal">
            <i class="bi bi-plus-lg"></i> Pridať jedáleň
        </button>
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
                    <th>Názov</th>
                    <th>Adresa</th>
                    <th class="text-end">Akcie</th>
                </tr>
            </thead>
            <tbody>
                @foreach($canteens as $canteen)
                    <tr>
                        <td>{{ $canteen->id }}</td>
                        <td><strong>{{ $canteen->name }}</strong></td>
                        <td>{{ $canteen->address }}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary edit-canteen-btn" data-bs-toggle="modal"
                                data-bs-target="#editCanteenModal" data-id="{{ $canteen->id }}" data-name="{{ $canteen->name }}"
                                data-address="{{ $canteen->address }}">
                                <i class="bi bi-pencil"></i> Upraviť
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteCanteenModal" data-id="{{ $canteen->id }}"
                                data-name="{{ $canteen->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="addCanteenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.canteens.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nová jedáleň</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Názov</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresa</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-primary">Vytvoriť</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editCanteenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editCanteenForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upraviť jedáleň</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Názov</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresa</label>
                            <input type="text" name="address" id="editAddress" class="form-control" required>
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

    <div class="modal fade" id="deleteCanteenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Potvrdiť zmazanie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Naozaj chcete zmazať jedáleň <strong id="delCanteenName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                    <form id="deleteCanteenForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Zmazať</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = document.getElementById('editCanteenModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const address = button.getAttribute('data-address');

                editModal.querySelector('#editCanteenForm').action = `/admin/canteens/${id}`;
                editModal.querySelector('#editName').value = name;
                editModal.querySelector('#editAddress').value = address;
            });

            const deleteModal = document.getElementById('deleteCanteenModal');
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');

                deleteModal.querySelector('#deleteCanteenForm').action = `/admin/canteens/${id}`;
                deleteModal.querySelector('#delCanteenName').textContent = name;
            });
        });
    </script>
@endsection