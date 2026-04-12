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
         <form method="GET" action="{{ route('admin.meals') }}" class="d-flex justify-content-end mb-3" id="meals-search-form">
            <div class="meal-search-wrap w-100">
               <label for="meals-search-input" class="form-label small text-muted mb-1">Hľadať v katalógu jedál</label>
               <div class="input-group">
                  <input
                     id="meals-search-input"
                     name="q"
                     type="search"
                     class="form-control"
                     value="{{ $searchQuery ?? '' }}"
                     placeholder="Názov, preklad, alergén, cena...">
                  <button type="submit" class="btn btn-outline-primary">Hľadať</button>
               </div>
            </div>
         </form>

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
                  <tr class="meal-row" data-search="{{ \Illuminate\Support\Str::lower(trim(($meal->raw_name ?? '') . ' ' . ($meal->name_sk ?? '') . ' ' . ($meal->name_en ?? '') . ' ' . ($meal->name_ua ?? '') . ' ' . ($meal->name_ru ?? '') . ' ' . $meal->allergens->pluck('number')->implode(' ') . ' ' . number_format((float) $meal->price, 2))) }}">
                     <td>
                        @if ($meal->image_path)
                           @php
                              $mealImageUrl = \Illuminate\Support\Str::startsWith($meal->image_path, ['http://', 'https://'])
                                 ? $meal->image_path
                                 : (\Illuminate\Support\Str::startsWith($meal->image_path, '/')
                                    ? asset(ltrim($meal->image_path, '/'))
                                    : asset('storage/' . $meal->image_path));
                           @endphp
                           <img
                              src="{{ $mealImageUrl }}"
                              alt="" class="rounded shadow-sm meal-list-thumb">
                        @else
                           <span class="text-muted small">Bez obrázka</span>
                        @endif
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
                        <button type="button" class="btn btn-sm btn-outline-danger"
                           onclick="confirmDeleteMeal({{ $meal->id }}, '{{ addslashes($meal->raw_name) }}')"
                        ><i class="bi bi-trash"></i> Zmazať</button>
                        <form id="delete-meal-form-{{ $meal->id }}" action="{{ route('admin.meals.destroy', $meal->id) }}" method="POST" class="d-none">
                           @csrf @method('DELETE')
                        </form>
                     </td>
                  </tr>
               @endforeach
            </tbody>
         </table>

         @if($meals->hasPages())
            <div class="mt-3 d-flex justify-content-end">
               {{ $meals->links() }}
            </div>
         @endif
      </div>
   </div>


@foreach($meals as $meal)
   <div class="modal fade edit-meal-modal" id="editMealModal{{ $meal->id }}" tabindex="-1" aria-hidden="true" data-meal-id="{{ $meal->id }}">
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
                        <input type="text" name="raw_name" id="edit-meal-raw-name-{{ $meal->id }}" class="form-control border-primary shadow-sm"
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

                     <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-info edit-suggest-translations-btn" data-meal-id="{{ $meal->id }}">
                           <i class="bi bi-translate me-1"></i>Doplniť preklady cez AI
                        </button>
                        <div class="form-text mt-2 text-muted edit-suggest-translations-status" data-meal-id="{{ $meal->id }}">
                           Ak chýbajú EN/UA/RU polia, AI ich vie doplniť z pôvodného názvu.
                        </div>
                     </div>

                     <div class="mb-3">
                        <label class="form-label fw-bold">Cena (€)</label>
                        <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ $meal->price }}" required>
                     </div>

                     <div class="mb-3 text-center">
                        @if ($meal->image_path)
                           @php
                              $mealImageUrl = \Illuminate\Support\Str::startsWith($meal->image_path, ['http://', 'https://'])
                                 ? $meal->image_path
                                 : (\Illuminate\Support\Str::startsWith($meal->image_path, '/')
                                    ? asset(ltrim($meal->image_path, '/'))
                                    : asset('storage/' . $meal->image_path));
                           @endphp
                           <img id="meal-img-{{ $meal->id }}"
                                 src="{{ $mealImageUrl }}"
                                 class="img-thumbnail mb-2 meal-edit-preview-img">
                        @else
                           <div id="meal-img-{{ $meal->id }}" class="border rounded bg-light d-flex align-items-center justify-content-center mb-2 text-muted meal-edit-preview-empty">
                              Bez obrázka
                           </div>
                        @endif
                        
                        <button type="button" class="btn btn-sm btn-outline-secondary d-block w-100" 
                                 onclick="generateMealImage({{ $meal->id }})">
                           <i class="bi bi-magic"></i> Generovať obrázok cez AI
                        </button>
                        <div class="mt-2">
                           <label class="form-label fw-bold small">Nahradiť vlastným obrázkom</label>
                           <input type="file" name="custom_image" accept=".jpg,.jpeg,.png,.gif,.avif,.svg,.webp" class="form-control form-control-sm">
                           <div class="form-text">Ak vyberiete súbor, po uložení nahradí aktuálny obrázok.</div>
                        </div>
                        <div id="loader-{{ $meal->id }}" class="spinner-border spinner-border-sm text-primary d-none" role="status"></div>
                     </div>
                  </div>

                  <div class="col-md-5">
                     <div class="card bg-light border-0 h-100">
                        <div class="card-body p-3">
                           <label class="form-label fw-bold small d-block mb-2">Alergény ({{ $allergens->count() }})</label>
                           <div class="mb-3">
                              <button type="button" class="btn btn-sm btn-outline-primary edit-suggest-allergens-btn" data-meal-id="{{ $meal->id }}">
                                 <i class="bi bi-stars me-1"></i>Navrhnúť alergény cez AI
                              </button>
                              <button type="button" class="btn btn-sm btn-outline-success d-none edit-apply-suggested-allergens-btn" data-meal-id="{{ $meal->id }}">
                                 <i class="bi bi-check2-circle me-1"></i>Označiť navrhnuté
                              </button>
                              <div class="form-text mt-2 text-muted edit-suggest-allergens-status" data-meal-id="{{ $meal->id }}">
                                 AI návrhy sú iba pomocné. Finálny výber musí potvrdiť administrátor.
                              </div>
                           </div>
                           <div class="bg-white border rounded p-2 shadow-sm edit-allergens-list">
                              @foreach($allergens->sortBy(fn($a) => (int)$a->number) as $allergen)
                                 <div class="form-check small mb-1 edit-allergen-row" data-allergen-number="{{ trim((string) $allergen->number) }}">
                                    <input class="form-check-input" type="checkbox" name="allergen_ids[]"
                                       value="{{ $allergen->id }}" 
                                       id="edit_all_{{ $meal->id }}_{{ $allergen->id }}" data-allergen-number="{{ trim((string) $allergen->number) }}"
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
   <form id="add-meal-form" action="{{ route('admin.meals.store') }}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg border-0">
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
                     <input type="text" name="raw_name" id="add-meal-raw-name" class="form-control border-primary shadow-sm"
                        placeholder="Napr. Bravčový rezeň, varené zemiaky" required>
                     <div class="form-text small italic text-muted">Môže byť aj v tváre "Hov.pečienka burgundská,knedľa 1,3,7 pol.frankfurtská s párkom 1". AI automaticky vyčistí názov a vytvorí preklady.</div>
                  </div>
                  
                  <div class="mb-3">
                     <label class="form-label fw-bold text-dark">Predajná cena</label>
                     <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">€</span>
                        <input type="number" step="0.01" min="0" name="price" class="form-control border-start-0" placeholder="0.00" required>
                     </div>
                  </div>

                  <div class="mb-3 text-center">
                     <div class="form-text small text-muted">
                        <i class="bi bi-image me-1"></i>Ak nahráte vlastný obrázok, AI obrázok sa negeneruje.<br>
                        <i class="bi bi-info-circle me-1"></i>Dátum a jedáleň sa priradia pri <a href="{{ route('admin.menu') }}">zostavovaní menu</a>.
                     </div>
                  </div>

                  <div class="mb-3">
                     <label class="form-label fw-bold small">Vlastný obrázok (voliteľné)</label>
                     <input type="file" name="custom_image" accept=".jpg,.jpeg,.png,.gif,.avif,.svg,.webp" class="form-control form-control-sm">
                  </div>

                  <div class="form-check mb-3">
                     <input class="form-check-input" type="checkbox" value="1" id="skip_ai_image" name="skip_ai_image">
                     <label class="form-check-label" for="skip_ai_image">
                        Negenerovať AI obrázok
                     </label>
                  </div>
               </div>

               <div class="col-md-5">
                  <div class="card bg-light border-0 h-100">
                     <div class="card-body p-3">
                        <label class="form-label fw-bold small d-block mb-2">Alergény ({{ $allergens->count() }})</label>
                        <div class="mb-3">
                           <button type="button" class="btn btn-sm btn-outline-primary" id="suggest-allergens-btn">
                              <i class="bi bi-stars me-1"></i>Navrhnúť alergény cez AI - stlačte ešte pred vytvorením položky, ale po zadaní názvu.
                           </button>
                           <button type="button" class="btn btn-sm btn-outline-success d-none" id="apply-suggested-allergens-btn">
                              <i class="bi bi-check2-circle me-1"></i>Označiť navrhnuté
                           </button>
                           <div id="suggest-allergens-status" class="form-text mt-2 text-muted">
                              AI návrhy sú iba pomocné. Finálny výber potvrdzuje administrátor manuálne.
                           </div>
                        </div>
                        <div class="bg-white border rounded p-2 shadow-sm add-allergens-list">
                           @foreach($allergens->sortBy(fn($a) => (int)$a->number) as $allergen)
                              <div class="form-check small mb-1 add-allergen-row" data-allergen-number="{{ trim((string) $allergen->number) }}">
                                 <input class="form-check-input" type="checkbox" name="allergen_ids[]"
                                    value="{{ $allergen->id }}" id="add_all_{{ $allergen->id }}" data-allergen-number="{{ trim((string) $allergen->number) }}">
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
            <button type="submit" class="btn btn-success px-5 shadow-sm" id="add-meal-submit-btn">
               <i class="bi bi-cpu me-2"></i>Spracovať cez AI a uložiť
            </button>
         </div>
      </form>
   </div>
</div>

<div class="modal fade" id="deleteMealModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg overflow-hidden">
         <div class="modal-header bg-danger text-white border-0 pb-2">
            <div class="d-flex align-items-center gap-2">
               <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center delete-meal-icon-wrap">
                  <i class="bi bi-trash3-fill text-white fs-5"></i>
               </div>
               <h5 class="modal-title mb-0 fw-semibold">Odstrániť jedlo</h5>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body px-4 py-4 text-center">
            <p class="mb-1 text-secondary">Naozaj chcete natrvalo odstrániť jedlo:</p>
            <p class="fw-bold fs-6 text-dark mb-3" id="delete-meal-name"></p>
            <div class="alert alert-warning text-start d-flex align-items-start gap-2 py-2 px-3 small mb-0">
               <i class="bi bi-exclamation-triangle-fill text-warning mt-1 flex-shrink-0"></i>
               <span>Táto akcia je <strong>nevratná</strong>. Jedlo bude trvalo odstránené z katalógu.</span>
            </div>
         </div>
         <div class="modal-footer border-0 bg-light px-4 pb-4 pt-2 gap-2">
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
               <i class="bi bi-x-lg me-1"></i>Zrušiť
            </button>
            <button type="button" class="btn btn-danger px-5 shadow-sm" id="delete-meal-confirm-btn">
               <i class="bi bi-trash3 me-2"></i>Áno, odstrániť
            </button>
         </div>
      </div>
   </div>
</div>

<script>
function confirmDeleteMeal(mealId, mealName) {
    document.getElementById('delete-meal-name').textContent = mealName;
    const confirmBtn = document.getElementById('delete-meal-confirm-btn');
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
    newBtn.addEventListener('click', function () {
        newBtn.disabled = true;
        newBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Odstraňujem...';
        document.getElementById('delete-meal-form-' + mealId).submit();
    });
    const modal = new bootstrap.Modal(document.getElementById('deleteMealModal'));
    modal.show();
}

function generateMealImage(mealId) {
    const loader = document.getElementById('loader-' + mealId);
    const imgElement = document.getElementById('meal-img-' + mealId);
    
    loader.classList.remove('d-none');

    fetch(`/admin/meals/${mealId}/generate-image`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
         if (imgElement.tagName === 'IMG') {
            imgElement.src = data.image_url;
         } else {
            imgElement.outerHTML = `<img id="meal-img-${mealId}" src="${data.image_url}" class="img-thumbnail mb-2 meal-edit-preview-img">`;
         }
         alert('Obrázok je úspešne vygenerovaný a uložený.');
        } else {
         alert(data.message || 'Chyba počas generacii.');
        }
    })
   .catch(error => {
      console.error('Error:', error);
      alert('Server nevie spracovať generaciu obrázka.');
   })
    .finally(() => {
        loader.classList.add('d-none');
    });
}

(function initAddMealAllergenSuggestions() {
   const suggestBtn = document.getElementById('suggest-allergens-btn');
   const applyBtn = document.getElementById('apply-suggested-allergens-btn');
   const rawNameInput = document.getElementById('add-meal-raw-name');
   const statusEl = document.getElementById('suggest-allergens-status');
   if (!suggestBtn || !applyBtn || !rawNameInput || !statusEl) {
      return;
   }

   const suggestAllergensUrl = '{{ route('admin.meals.suggest-allergens') }}';
   const addModal = document.getElementById('addMealModal');
   let suggestedNumbers = [];

   const getRows = () => Array.from(addModal.querySelectorAll('.add-allergen-row'));
   const getCheckboxes = () => Array.from(addModal.querySelectorAll('input[name="allergen_ids[]"]'));

   function clearHighlights() {
      getRows().forEach((row) => row.classList.remove('ai-suggested'));
   }

   function markHighlights(numbers) {
      clearHighlights();
      getRows().forEach((row) => {
         if (numbers.includes((row.dataset.allergenNumber || '').trim())) {
            row.classList.add('ai-suggested');
         }
      });
   }

   function showStatus(html, isError) {
      statusEl.className = `form-text mt-2 ${isError ? 'text-danger' : 'text-muted'}`;
      statusEl.innerHTML = html;
   }

   suggestBtn.addEventListener('click', function () {
      const rawName = rawNameInput.value.trim();
      if (!rawName) {
         showStatus('Najprv zadajte názov jedla.', true);
         return;
      }

      suggestBtn.disabled = true;
      suggestBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Analyzujem...';
      applyBtn.classList.add('d-none');

      fetch(suggestAllergensUrl, {
         method: 'POST',
         headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
         },
         body: JSON.stringify({ raw_name: rawName })
      })
      .then((response) => response.json())
      .then((data) => {
         if (!data.success || !Array.isArray(data.allergens) || !data.allergens.length) {
            suggestedNumbers = [];
            clearHighlights();
            showStatus(data.message || 'AI nenašla žiadne spoľahlivé návrhy.', true);
            return;
         }

         suggestedNumbers = data.allergens.map((item) => String(item.number).trim());
         markHighlights(suggestedNumbers);
         const badges = data.allergens
            .map((item) => `<span class="badge bg-warning text-dark border me-1">${item.number}</span>`)
            .join(' ');

         showStatus(`AI navrhla: ${badges}<br><small>Skontrolujte a potvrďte výber tlačidlom nižšie.</small>`, false);
         applyBtn.classList.remove('d-none');
      })
      .catch(() => {
         suggestedNumbers = [];
         clearHighlights();
         showStatus('Nepodarilo sa načítať AI návrhy alergénov.', true);
      })
      .finally(() => {
         suggestBtn.disabled = false;
         suggestBtn.innerHTML = '<i class="bi bi-stars me-1"></i>Navrhnúť alergény cez AI';
      });
   });

   applyBtn.addEventListener('click', function () {
      if (!suggestedNumbers.length) {
         return;
      }

      getCheckboxes().forEach((checkbox) => {
         const number = (checkbox.dataset.allergenNumber || '').trim();
         if (suggestedNumbers.includes(number)) {
            checkbox.checked = true;
         }
      });

      showStatus('Navrhnuté alergény boli označené. Pred uložením ich ešte skontrolujte.', false);
   });
})();

(function initEditMealAllergenSuggestions() {
   const suggestAllergensUrl = '{{ route('admin.meals.suggest-allergens') }}';
   const modals = Array.from(document.querySelectorAll('.edit-meal-modal'));

   modals.forEach((modal) => {
      const mealId = modal.dataset.mealId;
      const rawNameInput = document.getElementById(`edit-meal-raw-name-${mealId}`);
      const suggestBtn = modal.querySelector(`.edit-suggest-allergens-btn[data-meal-id="${mealId}"]`);
      const applyBtn = modal.querySelector(`.edit-apply-suggested-allergens-btn[data-meal-id="${mealId}"]`);
      const statusEl = modal.querySelector(`.edit-suggest-allergens-status[data-meal-id="${mealId}"]`);
      if (!rawNameInput || !suggestBtn || !applyBtn || !statusEl) {
         return;
      }

      let suggestedNumbers = [];
      const getRows = () => Array.from(modal.querySelectorAll('.edit-allergen-row'));
      const getCheckboxes = () => Array.from(modal.querySelectorAll('input[name="allergen_ids[]"]'));

      function clearHighlights() {
         getRows().forEach((row) => row.classList.remove('ai-suggested'));
      }

      function markHighlights(numbers) {
         clearHighlights();
         getRows().forEach((row) => {
            if (numbers.includes((row.dataset.allergenNumber || '').trim())) {
               row.classList.add('ai-suggested');
            }
         });
      }

      function showStatus(html, isError) {
         statusEl.className = `form-text mt-2 edit-suggest-allergens-status ${isError ? 'text-danger' : 'text-muted'}`;
         statusEl.innerHTML = html;
      }

      suggestBtn.addEventListener('click', function () {
         const rawName = rawNameInput.value.trim();
         if (!rawName) {
            showStatus('Najprv zadajte názov jedla.', true);
            return;
         }

         suggestBtn.disabled = true;
         suggestBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Analyzujem...';
         applyBtn.classList.add('d-none');

         fetch(suggestAllergensUrl, {
            method: 'POST',
            headers: {
               'X-CSRF-TOKEN': '{{ csrf_token() }}',
               'Content-Type': 'application/json',
               'Accept': 'application/json',
            },
            body: JSON.stringify({ raw_name: rawName })
         })
         .then((response) => response.json())
         .then((data) => {
            if (!data.success || !Array.isArray(data.allergens) || !data.allergens.length) {
               suggestedNumbers = [];
               clearHighlights();
               showStatus(data.message || 'AI nenašla žiadne spoľahlivé návrhy.', true);
               return;
            }

            suggestedNumbers = data.allergens.map((item) => String(item.number).trim());
            markHighlights(suggestedNumbers);
            const badges = data.allergens
               .map((item) => `<span class="badge bg-warning text-dark border me-1">${item.number}</span>`)
               .join(' ');

            showStatus(`AI navrhla: ${badges}<br><small>Skontrolujte a potvrďte výber tlačidlom nižšie.</small>`, false);
            applyBtn.classList.remove('d-none');
         })
         .catch(() => {
            suggestedNumbers = [];
            clearHighlights();
            showStatus('Nepodarilo sa načítať AI návrhy alergénov.', true);
         })
         .finally(() => {
            suggestBtn.disabled = false;
            suggestBtn.innerHTML = '<i class="bi bi-stars me-1"></i>Navrhnúť alergény cez AI';
         });
      });

      applyBtn.addEventListener('click', function () {
         if (!suggestedNumbers.length) {
            return;
         }

         getCheckboxes().forEach((checkbox) => {
            const number = (checkbox.dataset.allergenNumber || '').trim();
            if (suggestedNumbers.includes(number)) {
               checkbox.checked = true;
            }
         });

         showStatus('Navrhnuté alergény boli označené. Pred uložením ich ešte skontrolujte.', false);
      });
   });
})();

(function initEditMealTranslationSuggestions() {
   const suggestTranslationsUrl = '{{ route('admin.meals.suggest-translations') }}';
   const modals = Array.from(document.querySelectorAll('.edit-meal-modal'));

   modals.forEach((modal) => {
      const mealId = modal.dataset.mealId;
      const rawNameInput = document.getElementById(`edit-meal-raw-name-${mealId}`);
      const suggestBtn = modal.querySelector(`.edit-suggest-translations-btn[data-meal-id="${mealId}"]`);
      const statusEl = modal.querySelector(`.edit-suggest-translations-status[data-meal-id="${mealId}"]`);
      const skInput = modal.querySelector('input[name="name_sk"]');
      const enInput = modal.querySelector('input[name="name_en"]');
      const uaInput = modal.querySelector('input[name="name_ua"]');
      const ruInput = modal.querySelector('input[name="name_ru"]');

      if (!rawNameInput || !suggestBtn || !statusEl || !skInput || !enInput || !uaInput || !ruInput) {
         return;
      }

      function showStatus(text, isError) {
         statusEl.className = `form-text mt-2 edit-suggest-translations-status ${isError ? 'text-danger' : 'text-muted'}`;
         statusEl.textContent = text;
      }

      suggestBtn.addEventListener('click', function () {
         const rawName = rawNameInput.value.trim();
         if (!rawName) {
            showStatus('Najprv zadajte pôvodný názov.', true);
            return;
         }

         suggestBtn.disabled = true;
         suggestBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generujem preklady...';

         fetch(suggestTranslationsUrl, {
            method: 'POST',
            headers: {
               'X-CSRF-TOKEN': '{{ csrf_token() }}',
               'Content-Type': 'application/json',
               'Accept': 'application/json',
            },
            body: JSON.stringify({ raw_name: rawName })
         })
         .then((response) => response.json())
         .then((data) => {
            if (!data.success || !data.translations) {
               showStatus(data.message || 'AI nevrátila preklady.', true);
               return;
            }

            const tr = data.translations;
            if (typeof tr.name_sk === 'string' && tr.name_sk.trim()) skInput.value = tr.name_sk.trim();
            if (typeof tr.name_en === 'string') enInput.value = tr.name_en;
            if (typeof tr.name_ua === 'string') uaInput.value = tr.name_ua;
            if (typeof tr.name_ru === 'string') ruInput.value = tr.name_ru;

            showStatus('Preklady boli doplnené. Pred uložením ich skontrolujte.', false);
         })
         .catch(() => {
            showStatus('Nepodarilo sa načítať AI preklady.', true);
         })
         .finally(() => {
            suggestBtn.disabled = false;
            suggestBtn.innerHTML = '<i class="bi bi-translate me-1"></i>Doplniť preklady cez AI';
         });
      });
   });
})();

(function initAddMealSubmitLoading() {
   const addMealForm = document.getElementById('add-meal-form');
   const submitBtn = document.getElementById('add-meal-submit-btn');
   if (!addMealForm || !submitBtn) {
      return;
   }

   addMealForm.addEventListener('submit', function () {
      if (submitBtn.disabled) {
         return;
      }

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Spracovávam...';
   });
})();

(function initMealsCatalogSearch() {
   const searchInput = document.getElementById('meals-search-input');
   const searchForm = document.getElementById('meals-search-form');
   if (!searchInput || !searchForm) return;

   let searchDebounce;
   searchInput.addEventListener('input', function () {
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(() => searchForm.submit(), 350);
   });
})();
</script>
@endsection