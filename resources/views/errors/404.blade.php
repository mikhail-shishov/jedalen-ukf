@if(request()->is('admin/*') || request()->is('admin'))
    @extends('admin.dashboard')
    
    @section('admin_content')
    <div class="text-center mt-5">
        <h1 class="h1">Error 404</h1>
        <h2 class="h2">Page Not Found</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-dark">Späť na nástenku</a>
    </div>
    @endsection
@else
    <!DOCTYPE html>
    <html lang="sk">
    <head>
        <meta charset="UTF-8">
        <title>Error 404</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="h1">404</h1>
            <h2 class="h2">Page Not Found</h2>
        </div>
    </body>
    </html>
@endif