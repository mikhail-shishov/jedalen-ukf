@extends('admin.dashboard')

@section('admin_content')
    <div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between">
        <h2>Nový článok</h2>
        <a href="{{ route('admin.articles') }}" class="btn btn-outline-secondary btn-sm align-self-center">Späť</a>
    </div>

    <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label fw-bold">URL slug</label>
                    <div class="input-group">
                        <span class="input-group-text">https://stravovanie.ukf.sk/</span>
                        <input type="text" name="slug" id="slug" class="form-control"
                            value="{{ old('slug', isset($article) ? $article->slug : '') }}" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-end mb-2">
                    <ul class="nav nav-tabs border-bottom-0" id="langTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sk" type="button">Slovenčina</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#en" type="button">Angličtina</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ua" type="button">Ukrajinčina</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ru" type="button">Ruština</button></li>
                    </ul>
                    <button type="button" id="translateBtn" class="btn btn-sm btn-outline-primary mb-1">
                        <i class="bi bi-translate"></i> Preložiť cez AI
                    </button>
                </div>

                <div class="tab-content border p-4 bg-white shadow-sm rounded">
                    @foreach(['sk', 'en', 'ua', 'ru'] as $lang)
                        <div class="tab-pane fade {{ $lang == 'sk' ? 'show active' : '' }}" id="{{ $lang }}">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nadpis ({{ strtoupper($lang) }})</label>
                                <input type="text" name="title_{{$lang}}" class="form-control"
                                    value="{{ old('title_' . $lang) }}" {{ $lang == 'sk' ? 'required' : '' }}>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Obsah ({{ strtoupper($lang) }})</label>
                                <textarea name="content_{{$lang}}" class="editor" id="editor_{{$lang}}">{{ old('content_' . $lang) }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm p-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Priradenie k jedálňam</label>
                        <div class="border p-3 rounded bg-light" style="max-height: 200px; overflow-y: auto;">
                            @foreach($canteens as $canteen)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="canteens[]" value="{{ $canteen->id }}"
                                        id="canteen_{{ $canteen->id }}" {{ (is_array(old('canteens')) && in_array($canteen->id, old('canteens'))) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="canteen_{{ $canteen->id }}">
                                        {{ $canteen->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hlavný obrázok</label>
                        <input type="file" name="image" class="form-control">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_published" class="form-check-input" id="is_published" {{ old('is_published') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Publikovať hneď</label>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-success w-100 py-2">
                        <i class="bi bi-check-lg"></i> Vytvoriť článok
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        const editors = {};

        document.querySelectorAll('.editor').forEach(el => {
            ClassicEditor.create(el)
                .then(editor => {
                    editors[el.id] = editor;
                })
                .catch(error => { console.error(error); });
        });

        const titleInput = document.querySelector('input[name="title_sk"]');
        const slugInput = document.querySelector('input[name="slug"]');

        titleInput.addEventListener('input', function () {
            if (slugInput.dataset.edited !== 'true') {
                slugInput.value = titleInput.value
                    .toLowerCase()
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9 -]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            }
        });

        slugInput.addEventListener('change', function () {
            slugInput.dataset.edited = 'true';
        });

        document.getElementById('translateBtn').addEventListener('click', async function() {
            const btn = this;
            const titleSk = titleInput.value;
            const contentSk = editors['editor_sk'] ? editors['editor_sk'].getData() : '';

            if (!titleSk || !contentSk) {
                alert('Najprv vyplňte slovenský názov a obsah.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Prekladám...';

            try {
                const resTitle = await fetch('{{ route("admin.translate") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ text: titleSk })
                });
                const titles = await resTitle.json();

                const resContent = await fetch('{{ route("admin.translate") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ text: contentSk })
                });
                const contents = await resContent.json();

                ['en', 'ua', 'ru'].forEach(lang => {
                    document.querySelector(`input[name="title_${lang}"]`).value = titles[lang] || '';
                    if (editors[`editor_${lang}`]) {
                        editors[`editor_${lang}`].setData(contents[lang] || '');
                    }
                });

            } catch (e) {
                console.error(e);
                alert('Chyba pri preklade.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-translate"></i> Preložiť cez AI';
            }
        });
    </script>
@endsection