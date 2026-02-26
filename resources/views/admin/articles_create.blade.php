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
                <ul class="nav nav-tabs" id="langTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab"
                            data-bs-target="#sk">Slovenčina</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#en">Angličtina</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#ua">Ukrajinčina</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ru">Ruština</button>
                    </li>
                </ul>

                <div class="tab-content border border-top-0 p-4 bg-white shadow-sm rounded-bottom">
                    @foreach(['sk', 'en', 'ua', 'ru'] as $lang)
                        <div class="tab-pane fade {{ $lang == 'sk' ? 'show active' : '' }}" id="{{ $lang }}">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nadpis ({{ strtoupper($lang) }})</label>
                                <input type="text" name="title_{{$lang}}" class="form-control" value="{{ old('title_' . $lang) }}"
                                    {{ $lang == 'sk' ? 'required' : '' }}>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Obsah ({{ strtoupper($lang) }})</label>
                                <textarea name="content_{{$lang}}" class="editor"
                                    id="editor_{{$lang}}">{{ old('content_' . $lang) }}</textarea>
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
                        <small class="text-muted">Ak nezaškrtnete žiadnu, článok bude globálny.</small>
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
        document.querySelectorAll('.editor').forEach(el => {
            ClassicEditor.create(el).catch(error => { console.error(error); });
        });
    </script>
@endsection