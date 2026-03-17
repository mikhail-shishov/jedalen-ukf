@extends('admin.dashboard')

@section('admin_content')

    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2><i class="bi bi-calendar3 me-2"></i>Denné menu</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.menu') }}" class="card shadow-sm mb-4 border-0" id="menu-filter-form"
        data-date="{{ $date }}"
        data-canteen="{{ $canteenId }}"
        data-csrf="{{ csrf_token() }}"
        data-store-url="{{ route('admin.menu.store') }}">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase ls-1">Dátum</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary px-3" id="btn-prev-day" title="Predchádzajúci deň">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <input type="date" name="date" class="form-control" value="{{ $date }}" id="menu-filter-date">
                        <button type="button" class="btn btn-outline-secondary px-3" id="btn-next-day" title="Nasledujúci deň">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-muted text-uppercase">Jedáleň</label>
                    <select name="canteen_id" class="form-select" id="menu-filter-canteen">
                        @foreach($canteens as $canteen)
                            <option value="{{ $canteen->id }}" {{ $canteen->id == $canteenId ? 'selected' : '' }}>
                                {{ $canteen->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-search me-1"></i>Zobraziť
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                    <span class="fw-semibold">
                        <i class="bi bi-list-check me-2 text-primary"></i>
                        Menu na {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
                        @if($canteenId)
                            — {{ $canteens->firstWhere('id', $canteenId)?->name }}
                        @endif
                    </span>
                    <span class="badge bg-primary rounded-pill fs-6">{{ $menuItems->count() }}</span>
                </div>

                <div class="card-body p-0">
                    @if($menuItems->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-25"></i>
                            <p class="mb-0">Na tento deň nie sú naplánované žiadne jedlá.</p>
                            <p class="small">Pridajte jedlá pomocou vyhľadávania vpravo.</p>
                        </div>
                    @else
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3"></th>
                                    <th>Jedlo</th>
                                    <th>Alergény</th>
                                    <th class="text-end">Cena</th>
                                    <th class="text-end pe-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($menuItems as $item)
                                    <tr>
                                        <td class="ps-3">
                                            @php
                                                $imgUrl = $item->meal->image_path
                                                    ? (str_starts_with($item->meal->image_path, 'http')
                                                        ? $item->meal->image_path
                                                        : asset('storage/' . $item->meal->image_path))
                                                    : null;
                                            @endphp
                                            @if($imgUrl)
                                                <img src="{{ $imgUrl }}" class="rounded shadow-sm menu-thumb">
                                            @else
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center menu-thumb-placeholder">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $item->meal->name_sk }}</div>
                                            <div class="text-muted small">{{ $item->meal->raw_name }}</div>
                                        </td>
                                        <td>
                                            @foreach($item->meal->allergens->sortBy(fn($a) => (int) $a->number) as $allergen)
                                                <span class="badge bg-light text-dark border"
                                                    title="{{ $allergen->name }}">{{ $allergen->number }}</span>
                                            @endforeach
                                        </td>
                                        <td class="text-end fw-bold text-primary">
                                            {{ number_format($item->meal->price, 2) }}&nbsp;€
                                        </td>
                                        <td class="text-end pe-3">
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Odstrániť z menu"
                                                onclick="confirmRemove({{ $item->id }}, '{{ addslashes($item->meal->name_sk) }}')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                            <form id="remove-form-{{ $item->id }}"
                                                action="{{ route('admin.menu.destroy', $item->id) }}" method="POST" class="d-none">
                                                @csrf @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3 fw-semibold">
                    <i class="bi bi-files me-2 text-info"></i>Duplikovať menu
                </div>
                <div class="card-body">
                    <form id="duplicate-menu-form" method="POST" action="{{ route('admin.menu.duplicate') }}">
                        @csrf
                        <input type="hidden" name="canteen_id" id="dup-canteen-id" value="{{ $canteenId }}">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Od dňa</label>
                            <input type="date" id="dup-from-date" name="from_date" class="form-control" value="{{ $date }}">
                            <small class="text-muted d-block mt-1">Vyberte deň, ktorého menu chcete skopírovať</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Na deň</label>
                            <input type="date" id="dup-to-date" name="to_date" class="form-control">
                            <small class="text-muted d-block mt-1">Existujúce menu na tento deň bude nahradené</small>
                        </div>
                        <button type="submit" class="btn btn-info w-100">
                            <i class="bi bi-files me-1"></i>Duplikovať menu
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-calendar-week me-2 text-warning"></i>Dni s menu</span>
                    <small class="fw-normal text-muted" id="days-count">(0)</small>
                </div>
                <div class="card-body p-0 max-height-300" id="days-list" style="max-height: 300px; overflow-y: auto;">
                    <div class="text-center text-muted py-4">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>Načítavám...
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3 fw-semibold">
                        <i class="bi bi-plus-circle me-2 text-success"></i>Pridať jedlo do menu
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Vyhľadať v katalógu jedál</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="meal-search" class="form-control"
                                    placeholder="Začnite písať názov jedla...">
                                <button class="btn btn-outline-secondary d-none" type="button" id="clear-search" title="Vymazať">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>

                        <div id="search-loading" class="text-center text-muted py-3 d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>Hľadám...
                        </div>
                        <div id="search-empty" class="text-center text-muted py-3 d-none">
                            <i class="bi bi-inbox fs-4 d-block mb-1 opacity-25"></i>Žiadne výsledky
                        </div>
                        <div id="search-results" class="d-flex flex-column gap-2 search-results"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="removeMenuItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-danger text-white border-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center remove-icon-wrap">
                            <i class="bi bi-trash3-fill text-white"></i>
                        </div>
                        <h5 class="modal-title mb-0 fw-semibold">Odstrániť z menu</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-4 text-center">
                    <p class="mb-1 text-secondary">Odstrániť jedlo z denného menu:</p>
                    <p class="fw-bold fs-6 text-dark mb-0" id="remove-item-name"></p>
                </div>
                <div class="modal-footer border-0 bg-light px-4 pb-4 pt-2 gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Zrušiť
                    </button>
                    <button type="button" class="btn btn-danger px-5" id="remove-item-confirm-btn">
                        <i class="bi bi-trash3 me-2"></i>Odstrániť
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            window.confirmRemove = function (itemId, itemName) {
                document.getElementById('remove-item-name').textContent = itemName;
                const btn = document.getElementById('remove-item-confirm-btn');
                const fresh = btn.cloneNode(true);
                btn.parentNode.replaceChild(fresh, btn);
                fresh.addEventListener('click', function () {
                    fresh.disabled = true;
                    fresh.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Odstraňujem...';
                    document.getElementById('remove-form-' + itemId).submit();
                });
                bootstrap.Modal.getOrCreate(document.getElementById('removeMenuItemModal')).show();
            };

            const filterForm = document.getElementById('menu-filter-form');
            const filterDateInput = document.getElementById('menu-filter-date');
            const filterCanteenSelect = document.getElementById('menu-filter-canteen');
            const searchInput = document.getElementById('meal-search');
            const clearBtn = document.getElementById('clear-search');
            const resultsEl = document.getElementById('search-results');
            const emptyEl = document.getElementById('search-empty');
            const loadingEl = document.getElementById('search-loading');

            const contextDate = '{{ $date }}';
            const contextCanteen = '{{ $canteenId }}';
            const csrfToken = '{{ csrf_token() }}';
            const storeUrl = '{{ route('admin.menu.store') }}';

            let searchDebounce;

            function buildImageBox(url) {
                if (url) {
                    return `<img src="${url}" class="rounded flex-shrink-0 shadow-sm search-thumb"
                             loading="lazy">`;
                }
                return `<div class="rounded bg-light d-flex align-items-center justify-content-center flex-shrink-0 search-thumb-placeholder"
                         ><i class="bi bi-image text-muted"></i></div>`;
            }

            function renderResults(meals) {
                resultsEl.innerHTML = '';
                if (!meals.length) { emptyEl.classList.remove('d-none'); return; }
                emptyEl.classList.add('d-none');

                meals.forEach(m => {
                    const allergenBadges = m.allergens.length
                        ? m.allergens.map(n =>
                            `<span class="badge bg-light text-dark border search-result-allergen-badge">${n}</span>`
                        ).join(' ')
                        : '<span class="text-muted search-result-allergens-label">—</span>';

                    const card = document.createElement('div');
                    card.className = 'border rounded p-2 d-flex gap-3 align-items-center bg-white shadow-sm';
                    card.innerHTML = `
                    ${buildImageBox(m.image_url)}
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold search-result-name">${m.name_sk}</div>
                        <div class="text-muted search-result-raw">${m.raw_name}</div>
                        <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
                            <span class="fw-bold text-primary search-result-price">${m.price}&nbsp;€</span>
                            <span class="text-muted ms-1 search-result-allergens-label">Alergény:</span>
                            ${allergenBadges}
                        </div>
                    </div>
                    <form method="POST" action="${storeUrl}" class="flex-shrink-0">
                        <input type="hidden" name="_token"      value="${csrfToken}">
                        <input type="hidden" name="meal_id"     value="${m.id}">
                        <input type="hidden" name="canteen_id"  value="${contextCanteen}">
                        <input type="hidden" name="date"        value="${contextDate}">
                        <button type="submit" class="btn btn-sm btn-success" title="Pridať do menu">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </form>`;
                    resultsEl.appendChild(card);
                });
            }

            function doSearch(q) {
                loadingEl.classList.remove('d-none');
                emptyEl.classList.add('d-none');
                resultsEl.innerHTML = '';
                fetch(`/admin/menu/meals/search?q=${encodeURIComponent(q)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(r => r.json())
                    .then(data => {
                        loadingEl.classList.add('d-none');
                        renderResults(data);
                    })
                    .catch(() => loadingEl.classList.add('d-none'));
            }

            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounce);
                clearBtn.classList.toggle('d-none', !this.value);
                searchDebounce = setTimeout(() => doSearch(this.value.trim()), 280);
            });

            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                clearBtn.classList.add('d-none');
                doSearch('');
            });

            filterDateInput.addEventListener('change', () => filterForm.submit());
            filterCanteenSelect.addEventListener('change', () => filterForm.submit());

            doSearch('');
        })();
    </script>
@endsection