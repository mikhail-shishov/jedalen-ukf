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
                <ul class="nav nav-tabs" id="langTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sk" type="button">Slovenčina</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#en" type="button">Angličtina</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ua" type="button">Ukrajinčina</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ru" type="button">Ruština</button></li>
                </ul>

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
                            @foreach($canteens as $canteen)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="canteens[]" value="{{ $canteen->id }}"
                                        id="canteen_{{ $canteen->id }}" 
                                        {{ in_array($canteen->id, old('canteens', $article->canteens->pluck('id')->toArray())) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="canteen_{{ $canteen->id }}">
                                        {{ $canteen->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hlavný obrázok</label>
                        @if($article->image_path)
                            <div class="mb-2 text-center">
                                <img src="{{ asset('storage/' . $article->image_path) }}" class="img-thumbnail" style="max-height: 120px;">
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
        document.querySelectorAll('.editor').forEach(el => {
            ClassicEditor.create(el).catch(error => { console.error(error); });
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
    </script>
@endsection