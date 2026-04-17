@extends('admin.dashboard')

@section('admin_content')
    <div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between">
        <h2>Upraviť článok: {{ $article->title_sk }}</h2>
        <a href="{{ route('admin.articles') }}" class="btn btn-outline-secondary btn-sm align-self-center">Späť</a>
    </div>

    <form action="{{ route('admin.articles.update', $article->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label fw-bold">URL slug</label>
                    <div class="input-group">
                        <span class="input-group-text">https://stravovanie.ukf.sk/</span>
                        <input type="text" name="slug" id="slug" class="form-control"
                            value="{{ old('slug', isset($article) ? $article->slug : '') }}" required>
                    </div>
                    <small class="text-muted">Použite iba malé písmená, čísla a pomlčky.</small>
                </div>


                <div class="d-flex justify-content-between align-items-end mb-2">
                    <ul class="nav nav-tabs" id="langTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sk" type="button">Slovenčina</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#en" type="button">Angličtina</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ua" type="button">Ukrajinčina</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ru" type="button">Ruština</button></li>
                    </ul>

                    <button type="button" id="translateBtn" class="btn btn-sm btn-outline-primary mb-1">
                        <i class="bi bi-translate"></i> Preložiť cez AI
                    </button>
                </div>

                <div class="tab-content border border-top-0 p-4 bg-white shadow-sm rounded-bottom">
                    @foreach(['sk', 'en', 'ua', 'ru'] as $lang)
                        <div class="tab-pane fade {{ $lang == 'sk' ? 'show active' : '' }}" id="{{ $lang }}">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nadpis ({{ strtoupper($lang) }})</label>
                                <input type="text" name="title_{{$lang}}" class="form-control" 
                                    value="{{ old('title_' . $lang, $article->{'title_'.$lang}) }}"
                                    {{ $lang == 'sk' ? 'required' : '' }}>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Obsah ({{ strtoupper($lang) }})</label>
                                <textarea name="content_{{$lang}}" class="editor" id="editor_{{$lang}}">{{ old('content_' . $lang, $article->{'content_'.$lang}) }}</textarea>
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
                            @php($selectedCanteenIds = old('canteens', $article->canteens->pluck('id')->toArray()))
                            @foreach($canteens as $canteen)
                                @php($isActive = (bool) ($canteen->is_active ?? true))
                                @php($isSelected = in_array($canteen->id, $selectedCanteenIds))
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="canteens[]" value="{{ $canteen->id }}"
                                        id="canteen_{{ $canteen->id }}" {{ $isSelected ? 'checked' : '' }} {{ $isActive ? '' : 'disabled' }}>
                                    <label class="form-check-label {{ $isActive ? '' : 'text-muted' }}" for="canteen_{{ $canteen->id }}">
                                        {{ $canteen->name }}
                                        @if(!$isActive)
                                            <span class="badge bg-secondary ms-2">Archívna</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hlavný obrázok</label>
                        @php
                            $articleImagePath = (string) ($article->image_path ?? '');
                            $articleImageUrl = null;

                            if ($articleImagePath !== '') {
                                if (\Illuminate\Support\Str::startsWith($articleImagePath, ['http://', 'https://'])) {
                                    $articleImageUrl = $articleImagePath;
                                } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($articleImagePath)) {
                                    $articleImageUrl = asset('storage/' . $articleImagePath);
                                }
                            }
                        @endphp
                        @if($articleImageUrl)
                            <div class="mb-2 text-center">
                                <img src="{{ $articleImageUrl }}" class="img-thumbnail" style="max-height: 120px;">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_published" class="form-check-input" id="is_published" {{ old('is_published', $article->is_published) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Publikované</label>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-save"></i> Aktualizovať článok
                    </button>
                </div>
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light fw-bold">História verzií</div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                            @forelse($article->revisions()->orderBy('created_at', 'desc')->get() as $revision)
                                <li class="list-group-item d-flex justify-content-between align-items-center small">
                                    <div>
                                        <strong>{{ $revision->created_at->format('d.m.Y H:i') }}</strong><br>
                                        <span class="text-muted">{{ $revision->user->first_name }}</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            onclick="restoreVersion({{ json_encode($revision) }})">
                                        Obnoviť
                                    </button>
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center">Žiadne predchádzajúce verzie</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        const editors = {};
        const titleInput = document.querySelector('input[name="title_sk"]');
        const uploadUrl = '{{ route("admin.articles.upload") }}';

        class LaravelUploadAdapter {
            constructor(loader) {
                this.loader = loader;
                this.controller = new AbortController();
            }

            upload() {
                return this.loader.file.then(file => new Promise((resolve, reject) => {
                    const formData = new FormData();
                    formData.append('upload', file);

                    fetch(uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData,
                        signal: this.controller.signal,
                    })
                        .then(async response => {
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok || !data.url) {
                                reject(data?.error?.message || 'Upload failed');
                                return;
                            }

                            resolve({ default: data.url });
                        })
                        .catch(error => {
                            reject(error?.message || 'Upload failed');
                        });
                }));
            }

            abort() {
                this.controller.abort();
            }
        }

        function LaravelUploadAdapterPlugin(editor) {
            editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                return new LaravelUploadAdapter(loader);
            };
        }

        document.querySelectorAll('.editor').forEach(el => {
            ClassicEditor.create(el, {
                extraPlugins: [LaravelUploadAdapterPlugin]
            })
                .then(editor => {
                    editors[el.id] = editor;
                    el.ckeditorInstance = editor;
                })
                .catch(error => { console.error(error); });
        });

        function restoreVersion(version) {
            if (!confirm('Naozaj chcete prepísať aktuálny text touto verziou?')) return;

            document.querySelector('input[name="title_sk"]').value = version.title_sk;
            const editorSk = document.querySelector('#editor_sk').ckeditorInstance;
            if (editorSk) editorSk.setData(version.content_sk);

            const payload = version.payload;
            ['en', 'ua', 'ru'].forEach(lang => {
                const titleInput = document.querySelector(`input[name="title_${lang}"]`);
                if (titleInput) titleInput.value = payload[`title_${lang}`] || '';
                
                const editor = document.querySelector(`#editor_${lang}`).ckeditorInstance;
                if (editor) editor.setData(payload[`content_${lang}`] || '');
            });
        }

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