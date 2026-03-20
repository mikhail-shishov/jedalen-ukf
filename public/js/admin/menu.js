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

    const btnPrevDay = document.getElementById('btn-prev-day');
    const btnNextDay = document.getElementById('btn-next-day');
    const filterDateInput = document.getElementById('menu-filter-date');
    const filterForm = document.getElementById('menu-filter-form');

    function changeDate(days) {
        const current = new Date(filterDateInput.value);
        current.setDate(current.getDate() + days);
        filterDateInput.value = current.toISOString().split('T')[0];
        filterForm.submit();
    }

    if (btnPrevDay) {
        btnPrevDay.addEventListener('click', (e) => {
            e.preventDefault();
            changeDate(-1);
        });
    }
    if (btnNextDay) {
        btnNextDay.addEventListener('click', (e) => {
            e.preventDefault();
            changeDate(1);
        });
    }

    const dupForm = document.getElementById('duplicate-menu-form');
    const dupCanteenId = document.getElementById('dup-canteen-id');
    const dupFromDate = document.getElementById('dup-from-date');
    const dupToDate = document.getElementById('dup-to-date');
    const filterCanteenSelect = document.getElementById('menu-filter-canteen');

    if (filterCanteenSelect) {
        filterCanteenSelect.addEventListener('change', () => {
            if (dupCanteenId) dupCanteenId.value = filterCanteenSelect.value;
        });
    }

    if (filterDateInput) {
        filterDateInput.addEventListener('change', () => {
            if (dupFromDate) dupFromDate.value = filterDateInput.value;
        });
    }

    const daysList = document.getElementById('days-list');
    const daysCount = document.getElementById('days-count');

    async function loadMenuDays() {
        const canteenId = filterCanteenSelect?.value;
        const date = filterDateInput?.value;

        if (!canteenId) {
            if (daysList) daysList.innerHTML = '<div class="text-center text-muted py-4">Vyberte jedáleň</div>';
            return;
        }

        try {
            const response = await fetch(`/admin/menu/days?canteen_id=${canteenId}&date=${date}`);
            const days = await response.json();

            if (days.length === 0) {
                if (daysList) daysList.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-4 d-block mb-2 opacity-25"></i>Žiadne dni s menu</div>';
                if (daysCount) daysCount.textContent = '(0)';
                return;
            }

            if (daysCount) daysCount.textContent = `(${days.length})`;

            const html = days.map(day => {
                const dateObj = new Date(day + 'T00:00:00');
                const formatted = dateObj.toLocaleDateString('sk-SK', {
                    weekday: 'short',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                });
                const isCurrentDate = day === date;

                return `
                    <button type="button" class="list-group-item list-group-item-action text-start border-0 border-bottom ${isCurrentDate ? 'active bg-primary' : ''}"
                        onclick="document.getElementById('menu-filter-date').value='${day}'; document.getElementById('menu-filter-form').submit();">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>${formatted}</span>
                            <span class="badge bg-secondary">Menu</span>
                        </div>
                    </button>
                `;
            }).join('');

            if (daysList) daysList.innerHTML = html;
        } catch (error) {
            console.error('Chyba pri načítaní dní:', error);
            if (daysList) daysList.innerHTML = '<div class="text-center text-danger py-4">Chyba pri načítaní</div>';
        }
    }

    if (filterCanteenSelect) filterCanteenSelect.addEventListener('change', loadMenuDays);
    if (filterDateInput) filterDateInput.addEventListener('change', loadMenuDays);

    if (daysList) loadMenuDays();

    const searchInput = document.getElementById('meal-search');
    const clearBtn = document.getElementById('clear-search');
    const resultsEl = document.getElementById('search-results');
    const emptyEl = document.getElementById('search-empty');
    const loadingEl = document.getElementById('search-loading');

    if (!searchInput) return;

    const contextDate = filterForm?.dataset.date || '';
    const contextCanteen = filterForm?.dataset.canteen || '';
    const csrfToken = filterForm?.dataset.csrf || '';
    const storeUrl = filterForm?.dataset.storeUrl || '/admin/menu';

    let searchDebounce;

    function buildImageBox(url) {
        if (url) {
            return `<img src="${url}" class="rounded flex-shrink-0 shadow-sm search-thumb" loading="lazy">`;
        }
        return `<div class="rounded bg-light d-flex align-items-center justify-content-center flex-shrink-0 search-thumb-placeholder"><i class="bi bi-image text-muted"></i></div>`;
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
        if (loadingEl) loadingEl.classList.remove('d-none');
        if (emptyEl) emptyEl.classList.add('d-none');
        resultsEl.innerHTML = '';
        fetch(`/admin/menu/meals/search?q=${encodeURIComponent(q)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(data => {
                if (loadingEl) loadingEl.classList.add('d-none');
                renderResults(data);
            })
            .catch(() => {
                if (loadingEl) loadingEl.classList.add('d-none');
            });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchDebounce);
        if (clearBtn) clearBtn.classList.toggle('d-none', !this.value);
        searchDebounce = setTimeout(() => doSearch(this.value.trim()), 280);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearBtn.classList.add('d-none');
            doSearch('');
        });
    }

    doSearch('');
})();
