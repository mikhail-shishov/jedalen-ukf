@extends('admin.dashboard')

@section('admin_content')
    <div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between">
        <h2>{{ $article->title_sk }}</h2>
        <a href="{{ route('admin.articles') }}" class="btn btn-outline-secondary btn-sm align-self-center">Späť</a>
    </div>

    <form action="{{ route('admin.articles.update', $article->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-8">
                <ul class="nav nav-tabs" id="langTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sk"
                            type="button">Slovenčina</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#en"
                            type="button">English</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ua"
                            type="button">Ukrainčina</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ru"
                            type="button">Ruština</button></li>
                </ul>

                <div class="tab-content border border-top-0 p-4 bg-white shadow-sm rounded-bottom">
                    @foreach(['sk', 'en', 'ua', 'ru'] as $lang)
                        <div class="tab-pane fade {{ $lang == 'sk' ? 'show active' : '' }}" id="{{ $lang }}">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nadpis ({{ strtoupper($lang) }})</label>
                                <input type="text" name="title_{{ $lang }}" class="form-control"
                                    value="{{ old('title_' . $lang, $article->{'title_' . $lang}) }}" {{ $lang == 'sk' ? 'required' : '' }}>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Obsah ({{ strtoupper($lang) }})</label>
                                <textarea name="content_{{ $lang }}" class="editor" id="editor_{{ $lang }}">
                                    {{ old('content_' . $lang, $article->{'content_' . $lang}) }}
                                </textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm p-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jedáleň</label>
                        <select name="canteens_id" class="form-select" required>
                            @foreach($canteens as $canteen)
                                <option value="{{ $canteen->id }}" {{ $article->canteens_id == $canteen->id ? 'selected' : '' }}>
                                    {{ $canteen->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hlavný obrázok</label>
                        @if($article->image_path)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $article->image_path) }}" class="img-thumbnail"
                                    style="max-height: 150px;">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control">
                        <small class="text-muted">Nechajte prázdne, ak nechcete meniť obrázok.</small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_published" class="form-check-input" id="is_published" {{ $article->is_published ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Publikovať</label>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-save"></i> Aktualizovať
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        document.querySelectorAll('.editor').forEach(el => {
            ClassicEditor
                .create(el, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
                })
                .catch(error => {
                    console.error(error);
                });
        });
    </script>

    <style>
        .ck-editor__editable {
            min-height: 300px;
        }
    </style>
@endsection