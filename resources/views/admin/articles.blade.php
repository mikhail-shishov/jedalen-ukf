@extends('admin.dashboard')

@section('admin_content')
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Články a novinky</h2>
    <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">Pridať nový</a>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Názov v slovenčine</th>
                <th>Jedáleň</th>
                <th>Autor</th>
                <th>Status</th>
                <th>Vytvorené</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            @foreach($articles as $article)
            <tr>
                <td>{{ $article->id }}</td>
                <td>{{ $article->title_sk }}</td>
                <td>{{ $article->canteen->name ?? 'Všetky' }}</td>
                <td>{{ $article->user->first_name }}</td>
                <td>
                    <span class="badge {{ $article->is_published ? 'bg-success' : 'bg-warning' }}">
                        {{ $article->is_published ? 'Publikované' : 'Draft' }}
                    </span>
                </td>
                <td>{{ $article->created_at->format('d.m.Y') }}</td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary">Upraviť</button>
                    </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection