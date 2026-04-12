@extends('admin.dashboard')

@section('admin_content')

    <div class="import-section">
        <div class="d-flex align-items-center gap-2 pt-3 pb-2 mb-4 border-bottom">
            <h1 class="h2 mb-0">Import jedál z CSV</h1>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="step-badge bg-primary text-white">1</span>
                    <strong>Nahrajte CSV súbor</strong>
                </div>

                <p class="text-muted small mb-3">
                    Súbor musí mať 3 stĺpce: <code>dátum (vo formate yyyy-mm-dd), názov jedla, cena</code><br>
                    Podporovaný oddeľovač stĺpcov je <code>,</code> aj <code>;</code> (auto-detekcia).<br>
                    Cena podporuje formát <code>4.20</code> aj <code>4,20</code>.<br>
                    Príklady: <code>2026-03-17,Svíčková na smotane,4.20</code> alebo
                    <code>2026-03-17;Svíčková na smotane;4,20</code><br>
                    Dátum môže byť prázdny — vtedy jedlo skončí len v katalógu bez zaradenia do menu.
                    <br><a href="{{ asset('import_meals_demo.csv') }}" download>Stiahnuť vzorový CSV súbor</a>
                </p>

                <div class="import-drop-zone" id="dropZone">
                    <input type="file" id="csvFile" accept=".csv,.txt">
                    <i class="bi bi-cloud-upload fs-1 text-secondary d-block mb-2"></i>
                    <div id="dropLabel">Presuňte CSV sem, alebo <a href="#"
                            onclick="document.getElementById('csvFile').click();return false;">vyberte súbor</a></div>
                    <div id="dropFileName" class="text-primary fw-semibold mt-1 d-none"></div>
                </div>

                <div id="parseErrors" class="alert alert-warning mt-2 d-none"></div>
            </div>
        </div>

        <div id="stepPreview" class="d-none">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="step-badge bg-primary text-white">2</span>
                        <strong>Náhľad a nastavenie</strong>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Priradiť do jedálne (pre zaradenie do denného menu)</label>
                            <select id="selectCanteen" class="form-select">
                                <option value="">— Iba uložiť do katalógu —</option>
                                @foreach($canteens as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Ak vyberiete jedáleň, riadky s dátumom sa automaticky pridajú do
                                denného menu.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Nastavenie obrázkov pre tento import</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="skipImportImageGeneration" checked>
                                <label class="form-check-label" for="skipImportImageGeneration">
                                    Negenerovať AI obrázky po importe
                                </label>
                            </div>
                            <small class="text-muted">Odporúčané pri veľkých batchoch alebo keď chcete nahrať vlastné fotky
                                ručne.</small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped preview-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Dátum</th>
                                    <th>Názov jedla</th>
                                    <th>Cena (€)</th>
                                </tr>
                            </thead>
                            <tbody id="previewBody"></tbody>
                        </table>
                    </div>
                    <div id="previewCount" class="text-muted small"></div>

                    <button id="btnImport" class="btn btn-success mt-3">
                        <i class="bi bi-cloud-download me-1"></i> Importovať <span id="btnImportCount"></span> jedál
                    </button>
                </div>
            </div>
        </div>

        <div id="stepEnrich" class="d-none">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="step-badge bg-success text-white">3</span>
                        <strong>Import dokončený — AI obohacovanie (voliteľné)</strong>
                    </div>

                    <div id="importSummary" class="alert alert-success mb-3"></div>

                    <p class="mb-2">Chcete spustiť AI spracovanie pre novo importované jedlá?</p>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkTranslate" checked>
                            <label class="form-check-label" for="chkTranslate">Preložiť názvy (SK/EN/UA/RU)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkAllergens" checked>
                            <label class="form-check-label" for="chkAllergens">Navrhnúť alergény</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkImage">
                            <label class="form-check-label" for="chkImage">Generovať obrázky</label>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <button id="btnEnrich" class="btn btn-primary">
                            <i class="bi bi-stars me-1"></i> Spustiť AI
                        </button>
                        <a href="{{ route('admin.meals') }}" class="btn btn-outline-secondary">Preskočiť</a>
                    </div>

                    <div id="enrichProgress" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Spracováva sa...</small>
                            <small id="enrichCountLabel" class="text-muted"></small>
                        </div>
                        <div class="progress mb-2" style="height:8px">
                            <div id="enrichBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                style="width:0%"></div>
                        </div>
                        <div id="enrichLog" class="progress-list border rounded p-2"></div>
                    </div>

                    <div id="enrichDone" class="alert alert-success d-none mt-3">
                        <i class="bi bi-check-circle me-1"></i> AI spracovanie dokončené.
                        <a href="{{ route('admin.meals') }}" class="alert-link ms-2">Otvoriť katalóg jedál →</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        (function () {
            const previewUrl = '{{ route('admin.import.preview') }}';
            const storeUrl = '{{ route('admin.import.store') }}';
            const enrichUrl = '{{ route('admin.import.enrich') }}';
            const csrfToken = '{{ csrf_token() }}';

            let parsedRows = [];
            let batchIds = [];

            const dropZone = document.getElementById('dropZone');
            const csvFile = document.getElementById('csvFile');

            ['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.add('drag-over'); }));
            ['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.remove('drag-over'); }));
            dropZone.addEventListener('drop', ev => handleFile(ev.dataTransfer.files[0]));
            csvFile.addEventListener('change', () => handleFile(csvFile.files[0]));

            function handleFile(file) {
                if (!file) return;
                document.getElementById('dropFileName').textContent = file.name;
                document.getElementById('dropFileName').classList.remove('d-none');
                document.getElementById('dropLabel').classList.add('d-none');
                parseCSV(file);
            }

            function parseCSV(file) {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('_token', csrfToken);

                fetch(previewUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        const errBox = document.getElementById('parseErrors');
                        if (data.errors && data.errors.length) {
                            errBox.innerHTML = '<strong>Upozornenia:</strong><br>' + data.errors.join('<br>');
                            errBox.classList.remove('d-none');
                        } else {
                            errBox.classList.add('d-none');
                        }

                        parsedRows = data.rows || [];
                        renderPreview(parsedRows);
                        document.getElementById('stepPreview').classList.remove('d-none');
                        document.getElementById('stepEnrich').classList.add('d-none');
                    })
                    .catch(err => alert('Chyba pri parsovaní CSV: ' + err));
            }

            function renderPreview(rows) {
                const tbody = document.getElementById('previewBody');
                tbody.innerHTML = '';

                rows.forEach((row, i) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td class="text-muted">${i + 1}</td>
                    <td>${row.date ? `<span class="badge badge-date">${row.date}</span>` : '<span class="text-muted">—</span>'}</td>
                    <td>${escHtml(row.name)}</td>
                    <td>${parseFloat(row.price).toFixed(2)} €</td>
                `;
                    tbody.appendChild(tr);
                });

                document.getElementById('previewCount').textContent = `Celkom: ${rows.length} riadkov`;
                document.getElementById('btnImportCount').textContent = `(${rows.length})`;
            }

            document.getElementById('btnImport').addEventListener('click', () => {
                const canteenId = document.getElementById('selectCanteen').value;
                const btn = document.getElementById('btnImport');

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importujem...';

                fetch(storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ rows: parsedRows, canteen_id: canteenId || null }),
                })
                    .then(r => r.json())
                    .then(data => {
                        batchIds = data.batch_ids || [];

                        const skipImportImage = document.getElementById('skipImportImageGeneration').checked;
                        const chkImage = document.getElementById('chkImage');
                        if (skipImportImage) {
                            chkImage.checked = false;
                            chkImage.disabled = true;
                        } else {
                            chkImage.disabled = false;
                        }

                        const summaryParts = [];
                        if (data.created.length) summaryParts.push(`<strong>${data.created.length}</strong> nových jedál vytvorených`);
                        if (data.skipped.length) summaryParts.push(`<strong>${data.skipped.length}</strong> preskočených (duplikáty)`);
                        if (data.menu_items.length) summaryParts.push(`<strong>${data.menu_items.length}</strong> záznamov pridaných do denného menu`);

                        document.getElementById('importSummary').innerHTML = summaryParts.join(' · ');
                        document.getElementById('stepEnrich').classList.remove('d-none');
                        document.getElementById('stepPreview').classList.add('d-none');

                        if (batchIds.length === 0) {
                            document.getElementById('btnEnrich').disabled = true;
                            document.getElementById('btnEnrich').title = 'Žiadne nové jedlá na obohatenie';
                        }
                    })
                    .catch(err => {
                        alert('Chyba pri importe: ' + err);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-cloud-download me-1"></i> Importovať';
                    });
            });

            document.getElementById('btnEnrich').addEventListener('click', async () => {
                const doTranslate = document.getElementById('chkTranslate').checked;
                const doAllergens = document.getElementById('chkAllergens').checked;
                const doImage = document.getElementById('chkImage').checked;

                if (!doTranslate && !doAllergens && !doImage) {
                    alert('Vyberte aspoň jednu možnosť AI spracovania.');
                    return;
                }

                document.getElementById('btnEnrich').disabled = true;
                document.getElementById('enrichProgress').classList.remove('d-none');
                document.getElementById('enrichDone').classList.add('d-none');

                const log = document.getElementById('enrichLog');
                const bar = document.getElementById('enrichBar');
                const lbl = document.getElementById('enrichCountLabel');
                const total = batchIds.length;
                log.innerHTML = '';

                for (let i = 0; i < batchIds.length; i++) {
                    const mealId = batchIds[i];
                    const pct = Math.round(((i + 1) / total) * 100);
                    const logItem = document.createElement('div');
                    logItem.className = 'item-spin';
                    logItem.id = `log-${mealId}`;
                    logItem.innerHTML = `<i class="bi bi-arrow-clockwise"></i> Jedlo #${mealId}...`;
                    log.appendChild(logItem);
                    log.scrollTop = log.scrollHeight;

                    try {
                        const resp = await fetch(enrichUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ meal_id: mealId, do_translate: doTranslate, do_allergens: doAllergens, do_image: doImage }),
                        });

                        const contentType = (resp.headers.get('content-type') || '').toLowerCase();
                        let result = null;

                        if (contentType.includes('application/json')) {
                            result = await resp.json();
                        } else {
                            const text = await resp.text();
                            throw new Error(`HTTP ${resp.status}: server returned non-JSON response` + (text ? ` (${text.slice(0, 80)})` : ''));
                        }

                        if (!resp.ok || !result.ok) {
                            throw new Error(result.message || `HTTP ${resp.status}`);
                        }

                        logItem.className = 'item-ok';
                        const done = result.changes && result.changes.length ? result.changes.join(', ') : 'bez zmien';
                        logItem.innerHTML = `<i class="bi bi-check-circle"></i> Jedlo #${mealId}: ${done}`;
                    } catch (e) {
                        logItem.className = 'item-err';
                        const msg = (e && e.message) ? e.message : 'Neznáma chyba';
                        logItem.innerHTML = `<i class="bi bi-x-circle"></i> Jedlo #${mealId}: chyba (${escHtml(msg)})`;
                    }

                    bar.style.width = pct + '%';
                    lbl.textContent = `${i + 1} / ${total}`;
                    log.scrollTop = log.scrollHeight;
                }

                document.getElementById('enrichDone').classList.remove('d-none');
            });

            function escHtml(str) {
                return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
        })();
    </script>
@endsection