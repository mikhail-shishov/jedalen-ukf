@extends('admin.dashboard')

@section('admin_content')
    <div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between align-items-center">
        <h2>Správa jedální</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCanteenModal">
            <i class="bi bi-plus-lg"></i> Pridať jedáleň
        </button>
    </div>

    <div class="row mb-4">
        @foreach($canteens as $canteen)
            <div class="col-md-3 mb-3">
                <div class="card h-100 shadow-sm border-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title text-primary">{{ $canteen->name }}</h5>
                        <p class="card-text small text-muted">{{ $canteen->address }}</p>
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                data-bs-target="#editModal{{ $canteen->id }}">Upraviť</button>
                            <a href="#" class="btn btn-sm btn-primary">Menu</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editModal{{ $canteen->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('admin.canteens.update', $canteen->id) }}" method="POST" class="modal-content">
                        @csrf @method('PUT')
                        <div class="modal-header">
                            <h5>Upraviť {{ $canteen->name }}</h5>
                        </div>
                        <div class="modal-body">
                            <input type="text" name="name" class="form-control mb-2" value="{{ $canteen->name }}" required>
                            <input type="text" name="address" class="form-control" value="{{ $canteen->address }}" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                            <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="modal fade" id="addCanteenModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.canteens.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5>Nová jedáleň</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Názov</label>
                        <input type="text" name="name" class="form-control" placeholder="napr. ŠD Zobor" required>
                    </div>
                    <div class="mb-3">
                        <label>Adresa</label>
                        <input type="text" name="address" class="form-control" placeholder="Dražovská 2, Nitra" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">Vytvoriť</button>
                </div>
            </form>
        </div>
    </div>
@endsection