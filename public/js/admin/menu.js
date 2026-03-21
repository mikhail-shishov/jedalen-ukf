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
    const DAYS_PAGE_SIZE = 30;
    let daysPage = 1;
    let daysHasMore = true;
    let daysIsLoading = false;

    function renderDaysLoading() {
        if (!daysList) return;
        daysList.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Načítavam...</div>';
    }

    function formatDay(day) {
        const dateObj = new Date(day + 'T00:00:00');
        return dateObj.toLocaleDateString('sk-SK', {
            weekday: 'short',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    function renderDaysChunk(days, selectedDate) {
        if (!daysList || !days.length) return;

        const html = days.map(day => {
            const isCurrentDate = day === selectedDate;
            return `
                <button type="button" class="list-group-item list-group-item-action text-start border-0 border-bottom ${isCurrentDate ? 'active bg-primary' : ''}" data-menu-day="${day}">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>${formatDay(day)}</span>
                        <span class="badge bg-secondary">Menu</span>
                    </div>
                </button>
            `;
        }).join('');

        daysList.insertAdjacentHTML('beforeend', html);
    }

    function resetDaysState() {
        daysPage = 1;
        daysHasMore = true;
        daysIsLoading = false;
        if (daysList) daysList.innerHTML = '';
        if (daysCount) daysCount.textContent = '(0)';
    }

    async function loadMenuDaysPage(page) {
        const canteenId = filterCanteenSelect?.value;
        const date = filterDateInput?.value;

        if (!daysList) return;
        if (!canteenId) {
            daysList.innerHTML = '<div class="text-center text-muted py-4">Vyberte jedáleň</div>';
            return;
        }
        if (daysIsLoading || (!daysHasMore && page > 1)) return;

        daysIsLoading = true;
        if (page === 1) renderDaysLoading();

        try {
            const params = new URLSearchParams({
                canteen_id: String(canteenId),
                date: String(date || ''),
                page: String(page),
                per_page: String(DAYS_PAGE_SIZE),
            });

            const response = await fetch(`/admin/menu/days?${params.toString()}`);
            const payload = await response.json();
            const days = Array.isArray(payload.days) ? payload.days : [];

            if (page === 1) {
                daysList.innerHTML = '';
            }

            if (days.length === 0 && page === 1) {
                daysList.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-4 d-block mb-2 opacity-25"></i>Žiadne dni s menu</div>';
                if (daysCount) daysCount.textContent = '(0)';
                daysHasMore = false;
                return;
            }

            if (daysCount) daysCount.textContent = `(${payload.total || 0})`;
            renderDaysChunk(days, date);

            daysPage = page;
            daysHasMore = Boolean(payload.has_more);
        } catch (error) {
            console.error('Chyba pri načítaní dní:', error);
            if (page === 1) {
                daysList.innerHTML = '<div class="text-center text-danger py-4">Chyba pri načítaní</div>';
            }
        } finally {
            daysIsLoading = false;
        }
    }

    function initDaysListInfiniteScroll() {
        if (!daysList) return;

        daysList.addEventListener('scroll', () => {
            const threshold = 60;
            const nearBottom = daysList.scrollTop + daysList.clientHeight >= daysList.scrollHeight - threshold;
            if (nearBottom && daysHasMore && !daysIsLoading) {
                void loadMenuDaysPage(daysPage + 1);
            }
        });

        daysList.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;

            const dayButton = target.closest('[data-menu-day]');
            if (!(dayButton instanceof HTMLElement)) return;

            const day = dayButton.dataset.menuDay;
            if (!day || !filterDateInput || !filterForm) return;

            filterDateInput.value = day;
            filterForm.submit();
        });
    }

    function reloadDaysList() {
        resetDaysState();
        void loadMenuDaysPage(1);
    }

    if (filterCanteenSelect) filterCanteenSelect.addEventListener('change', reloadDaysList);
    if (filterDateInput) filterDateInput.addEventListener('change', reloadDaysList);

    initDaysListInfiniteScroll();

    if (daysList) reloadDaysList();

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
    const SEARCH_PAGE_SIZE = 20;
    let searchPage = 1;
    let searchHasMore = true;
    let searchIsLoading = false;
    let searchActiveQuery = '';
    let searchRequestId = 0;

    let searchDebounce;

    function buildImageBox(url) {
        if (url) {
            return `<img src="${url}" class="rounded flex-shrink-0 shadow-sm search-thumb" loading="lazy">`;
        }
        return `<div class="rounded bg-light d-flex align-items-center justify-content-center flex-shrink-0 search-thumb-placeholder"><i class="bi bi-image text-muted"></i></div>`;
    }

    function renderResultsChunk(meals, append) {
        if (!append) {
            resultsEl.innerHTML = '';
        }

        if (!meals.length && !append) {
            emptyEl.classList.remove('d-none');
            return;
        }

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

    async function doSearch(q, page) {
        if (searchIsLoading) return;
        if (!searchHasMore && page > 1) return;

        searchIsLoading = true;
        if (page === 1) {
            searchPage = 1;
            searchHasMore = true;
            if (loadingEl) loadingEl.classList.remove('d-none');
            if (emptyEl) emptyEl.classList.add('d-none');
            resultsEl.innerHTML = '';
            if (resultsEl) resultsEl.scrollTop = 0;
        }

        const requestId = ++searchRequestId;

        try {
            const params = new URLSearchParams({
                q: q,
                page: String(page),
                per_page: String(SEARCH_PAGE_SIZE),
            });

            const response = await fetch(`/admin/menu/meals/search?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json();

            if (requestId !== searchRequestId) {
                return;
            }

            const meals = Array.isArray(payload.items) ? payload.items : [];
            renderResultsChunk(meals, page > 1);

            searchPage = page;
            searchHasMore = Boolean(payload.has_more);
        } catch {
            if (page === 1 && emptyEl) {
                emptyEl.classList.remove('d-none');
            }
        } finally {
            if (requestId === searchRequestId) {
                searchIsLoading = false;
                if (loadingEl) loadingEl.classList.add('d-none');
            }
        }
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchDebounce);
        if (clearBtn) clearBtn.classList.toggle('d-none', !this.value);
        searchDebounce = setTimeout(() => {
            searchActiveQuery = this.value.trim();
            searchHasMore = true;
            void doSearch(searchActiveQuery, 1);
        }, 280);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearBtn.classList.add('d-none');
            searchActiveQuery = '';
            searchHasMore = true;
            void doSearch('', 1);
        });
    }

    resultsEl.addEventListener('scroll', () => {
        const threshold = 100;
        const nearBottom = resultsEl.scrollTop + resultsEl.clientHeight >= resultsEl.scrollHeight - threshold;

        if (nearBottom && searchHasMore && !searchIsLoading) {
            void doSearch(searchActiveQuery, searchPage + 1);
        }
    });

    void doSearch('', 1);
})();
