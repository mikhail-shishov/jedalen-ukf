@extends('admin.dashboard')

@section('admin_content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Správa jedál</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMealModal">
        <i class="bi bi-plus-circle"></i> Pridať nové jedlo
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Obrázok</th>
                    <th>Pôvodný názov</th>
                    <th>Slovenský (AI)</th>
                    <th>Cena</th>
                    <th class="text-end">Akcie</th>
                </tr>
            </thead>
            <tbody>
                @foreach($meals as $meal)
                <tr>
                    <td>
                        <img src="{{ $meal->image_path ? asset('storage/' . $meal->image_path) : 'https://via.placeholder.com/50' }}" 
                             alt="" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                    </td>
                    <td>{{ $meal->raw_name }}</td>
                    <td>{{ $meal->name_sk }}</td>
                    <td>{{ number_format($meal->price, 2) }} €</td>
                    <td class="text-end">
                        <form action="{{ route('admin.meals.destroy', $meal->id) }}" method="POST" onsubmit="return confirm('Naozaj vymazať?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addMealModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.meals.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Pridať nové jedlo (AI asistované)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Názov jedla (napr. "Vyprážaný syr s hranolkami")</label>
                    <input type="text" name="raw_name" class="form-control" required>
                    <div class="form-text">AI automaticky vygeneruje preklady do EN, UA a RU.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cena (€)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Obrázok jedla</label>
                    <input type="file" name="image" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                <button type="submit" class="btn btn-success">Spracovať a uložiť</button>
            </div>
        </form>
    </div>
</div>
@endsection