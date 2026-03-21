@extends('admin.dashboard')

@section('admin_content')
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Články a novinky</h2>
    <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">Pridať nový</a>
</div>

<form method="GET" action="{{ route('admin.articles') }}" class="row g-2 mb-3">
    <div class="col-md-6 col-lg-4">
        <input
            type="search"
            name="q"
            class="form-control"
            value="{{ $searchQuery ?? '' }}"
            placeholder="Hľadať podľa názvu, autora, jedálne...">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Hľadať</button>
    </div>
    @if(!empty($searchQuery))
        <div class="col-auto">
            <a href="{{ route('admin.articles') }}" class="btn btn-outline-secondary">Vymazať filter</a>
        </div>
    @endif
</form>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Názov (SK)</th>
                <th>Jedálne</th>
                <th>Autor</th>
                <th>Status</th>
                <th>Vytvorené</th>
                <th class="text-end">Akcie</th>
            </tr>
        </thead>
        <tbody>
            @forelse($articles as $article)
            <tr>
                <td>{{ $article->id }}</td>
                <td>{{ $article->title_sk }}</td>
                <td>
                    @if($article->canteens->count() > 0)
                        {{ $article->canteens->pluck('name')->join(', ') }}
                    @else
                        <span class="text-muted">Všetky</span>
                    @endif
                </td>
                <td>{{ $article->user->first_name }}</td>
                <td>
                    <span class="badge {{ $article->is_published ? 'bg-success' : 'bg-warning' }}">
                        {{ $article->is_published ? 'Publikované' : 'Draft' }}
                    </span>
                </td>
                <td>{{ $article->created_at->format('d.m.Y') }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.articles.edit', $article->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Upraviť
                    </a>
                    
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteModal" 
                            data-id="{{ $article->id }}" 
                            data-title="{{ $article->title_sk }}">
                        <i class="bi bi-trash"></i> Zmazať
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">Žiadne články pre zadaný filter.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($articles->hasPages())
    <div class="d-flex justify-content-end mt-3">
        {{ $articles->links() }}
    </div>
@endif

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Potvrdiť zmazanie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Naozaj chcete zmazať článok <strong id="articleTitle"></strong>? Táto akcia je nevratná.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Zmazať</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const title = button.getAttribute('data-title');
        
        const modalTitleInput = deleteModal.querySelector('#articleTitle');
        const deleteForm = deleteModal.querySelector('#deleteForm');
        
        modalTitleInput.textContent = title;
        deleteForm.action = `/admin/articles/${id}`;
    });
</script>
@endsection