@extends('admin.dashboard')

@section('admin_content')
   <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
      <h2>Správa katalógu jedál</h2>
      @if ($errors->any())
         <div class="alert alert-danger w-100">
            <ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
         </div>
      @endif
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMealModal">
         <i class="bi bi-plus-circle"></i> Pridať nové jedlo
      </button>
   </div>

   <div class="card shadow-sm">
      <div class="card-body">
         <table class="table table-hover align-middle">
            <thead>
               <tr>
                  <th>Obrázok</th>
                  <th>Pôvodný / SK názov</th>
                  <th>Preklady</th>
                  <th>Alergény</th>
                  <th>Cena</th>
                  <th class="text-end">Akcie</th>
               </tr>
            </thead>
            <tbody>
               @foreach($meals as $meal)
                  <tr>
                     <td>
                        <img
                           src="{{ $meal->image_path ? asset('storage/' . $meal->image_path) : 'https://via.placeholder.com/50' }}"
                           alt="" style="width: 60px; height: 60px; object-fit: cover;" class="rounded shadow-sm">
                     </td>
                     <td>
                        <div class="fw-bold text-muted small">{{ $meal->raw_name }}</div>
                        <div class="fw-bold">{{ $meal->name_sk }}</div>
                     </td>
                     <td>
                        <div class="small">
                           <div class="text-muted"><span class="badge bg-light text-dark border me-1">EN</span>
                              {{ $meal->name_en ?: '-' }}</div>
                           <div class="text-muted"><span class="badge bg-light text-dark border me-1">UA</span>
                              {{ $meal->name_ua ?: '-' }}</div>
                           <div class="text-muted"><span class="badge bg-light text-dark border me-1">RU</span>
                              {{ $meal->name_ru ?: '-' }}</div>
                        </div>
                     </td>
                     <td>
                        @foreach($meal->allergens->sortBy(fn($a) => (int)$a->number) as $allergen)
                              <span class="badge bg-light text-dark border" title="{{ $allergen->name }}">
                                 {{ $allergen->number }}
                              </span>
                        @endforeach
                     </td>
                     <td class="fw-bold text-primary">{{ number_format($meal->price, 2) }}&nbsp;€</td>
                     <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                           data-bs-target="#editMealModal{{ $meal->id }}">
                           <i class="bi bi-pencil-square"></i> Upraviť
                        </button>
                        <form action="{{ route('admin.meals.destroy', $meal->id) }}" method="POST" class="d-inline"
                           onsubmit="return confirm('Naozaj vymazať z katalógu?')">
                           @csrf @method('DELETE')
                           <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Zmazať</button>
                        </form>
                     </td>
                  </tr>
               @endforeach
            </tbody>
         </table>
      </div>
   </div>


@foreach($meals as $meal)
   <div class="modal fade" id="editMealModal{{ $meal->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <form action="{{ route('admin.meals.update', $meal->id) }}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg">
            @csrf 
            @method('PUT')
            
            <div class="modal-header bg-light border-bottom-0">
               <h5 class="modal-title">
                  <i class="bi bi-pencil-square me-2"></i>Úprava jedla: {{ $meal->raw_name }}
               </h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
               <div class="row">
                  <div class="col-md-7">
                     <div class="mb-3">
                        <label class="form-label fw-bold text-primary">Pôvodný názov (Zdroj)</label>
                        <input type="text" name="raw_name" class="form-control border-primary shadow-sm"
                           value="{{ $meal->raw_name }}" required>
                     </div>

                     <nav>
                        <div class="nav nav-tabs mb-2" id="nav-tab-edit-{{ $meal->id }}" role="tablist">
                           <button class="nav-link active py-1" data-bs-toggle="tab" data-bs-target="#edit-sk-{{ $meal->id }}" type="button">SK</button>
                           <button class="nav-link py-1" data-bs-toggle="tab" data-bs-target="#edit-en-{{ $meal->id }}" type="button">EN</button>
                           <button class="nav-link py-1" data-bs-toggle="tab" data-bs-target="#edit-ua-{{ $meal->id }}" type="button">UA</button>
                           <button class="nav-link py-1" data-bs-toggle="tab" data-bs-target="#edit-ru-{{ $meal->id }}" type="button">RU</button>
                        </div>
                     </nav>
                     <div class="tab-content border p-2 rounded bg-white mb-3">
                        <div class="tab-pane fade show active" id="edit-sk-{{ $meal->id }}">
                           <input type="text" name="name_sk" class="form-control form-control-sm" value="{{ $meal->name_sk }}" required>
                        </div>
                        <div class="tab-pane fade" id="edit-en-{{ $meal->id }}">
                           <input type="text" name="name_en" class="form-control form-control-sm" value="{{ $meal->name_en }}">
                        </div>
                        <div class="tab-pane fade" id="edit-ua-{{ $meal->id }}">
                           <input type="text" name="name_ua" class="form-control form-control-sm" value="{{ $meal->name_ua }}">
                        </div>
                        <div class="tab-pane fade" id="edit-ru-{{ $meal->id }}">
                           <input type="text" name="name_ru" class="form-control form-control-sm" value="{{ $meal->name_ru }}">
                        </div>
                     </div>

                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label fw-bold">Cena (€)</label>
                           <input type="number" step="0.01" name="price" class="form-control" value="{{ $meal->price }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label fw-bold">Dátum</label>
                           <input type="date" name="date" class="form-control" value="{{ $meal->date }}" required>
                        </div>
                     </div>

                     <div class="mb-3">
                        <label class="form-label fw-bold">Priradené jedálne</label>
                        <select name="canteen_ids[]" class="form-select border-danger bg-light-danger" multiple required style="height: 100px;">
                           @foreach($canteens as $canteen)
                              <option value="{{ $canteen->id }}" 
                                 {{ $meal->canteens && $meal->canteens->contains($canteen->id) ? 'selected' : '' }}>
                                 {{ $canteen->name }}
                              </option>
                           @endforeach
                        </select>
                        <div class="form-text small">Ctrl/Cmd pre hromadný výber.</div>
                     </div>
                  </div>

                  <div class="col-md-5">
                     <div class="card bg-light border-0 h-100">
                        <div class="card-body p-3">
                           <label class="form-label fw-bold small d-block mb-2">Alergény ({{ $allergens->count() }})</label>
                           <div style="max-height: 320px; overflow-y: auto;" class="bg-white border rounded p-2 shadow-sm">
                              @foreach($allergens->sortBy(fn($a) => (int)$a->number) as $allergen)
                                 <div class="form-check small mb-1">
                                    <input class="form-check-input" type="checkbox" name="allergen_ids[]"
                                       value="{{ $allergen->id }}" 
                                       id="edit_all_{{ $meal->id }}_{{ $allergen->id }}" 
                                       {{ $meal->allergens->contains($allergen->id) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="edit_all_{{ $meal->id }}_{{ $allergen->id }}">
                                       <strong>{{ $allergen->number }}.</strong> {{ $allergen->name }}
                                    </label>
                                 </div>
                              @endforeach
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>

            <div class="modal-footer bg-light border-top-0 p-3">
               <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Zrušiť</button>
               <button type="submit" class="btn btn-primary px-5 shadow-sm">
                  <i class="bi bi-save me-2"></i>Uložiť zmeny
               </button>
            </div>
         </form>
      </div>
   </div>
@endforeach

<div class="modal fade" id="addMealModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-lg">
      <form action="{{ route('admin.meals.store') }}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg border-0">
         @csrf
         <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Pridať nové jedlo do ponuky</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <div class="row">
               <div class="col-md-7">
                  <div class="mb-3">
                     <label class="form-label fw-bold text-primary">Pôvodný názov</label>
                     <input type="text" name="raw_name" class="form-control border-primary shadow-sm"
                        placeholder="Napr. Bravčový rezeň, varené zemiaky" required>
                     <div class="form-text small italic text-muted">AI automaticky vyčistí názov a vytvorí preklady.</div>
                  </div>
                  
                  <div class="row">
                     <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-dark">Predajná cena</label>
                        <div class="input-group">
                           <span class="input-group-text bg-white border-end-0">€</span>
                           <input type="number" step="0.01" name="price" class="form-control border-start-0" placeholder="0.00" required>
                        </div>
                     </div>
                     <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-dark">Dátum podávania</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                     </div>
                  </div>

                  <div class="mb-3">
                     <label class="form-label fw-bold">Priradiť do jedální</label>
                     <select name="canteen_ids[]" class="form-select border-danger bg-light-danger" multiple required
                        style="height: 120px;">
                        @foreach($canteens as $canteen)
                           <option value="{{ $canteen->id }}">{{ $canteen->name }} ({{ $canteen->address }})</option>
                        @endforeach
                     </select>
                     <div class="form-text small"><i class="bi bi-info-circle me-1"></i>Podržte <strong>Ctrl (Win)</strong> alebo <strong>Cmd (Mac)</strong> pre výber viacerých budov.</div>
                  </div>
               </div>

               <div class="col-md-5">
                  <div class="card bg-light border-0 h-100">
                     <div class="card-body p-3">
                        <label class="form-label fw-bold small d-block mb-2">Manuálne alergény (nepovinné)</label>
                        <div style="max-height: 280px; overflow-y: auto;" class="bg-white border rounded p-2 shadow-sm">
                           @foreach($allergens->sortBy(fn($a) => (int)$a->number) as $allergen)
                              <div class="form-check small mb-1">
                                 <input class="form-check-input" type="checkbox" name="allergen_ids[]"
                                    value="{{ $allergen->id }}" id="add_all_{{ $allergen->id }}">
                                 <label class="form-check-label" for="add_all_{{ $allergen->id }}">
                                    <strong>{{ $allergen->number }}.</strong> {{ $allergen->name }}
                                 </label>
                              </div>
                           @endforeach
                        </div>
                        <div class="form-text small mt-2 text-muted">Ak nevyberiete, AI sa pokúsi doplniť podľa názvu.</div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer border-top-0 p-3">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Zrušiť</button>
            <button type="submit" class="btn btn-success px-5 shadow-sm">
               <i class="bi bi-cpu me-2"></i>Spracovať cez AI a uložiť
            </button>
         </div>
      </form>
   </div>
</div>
@endsection