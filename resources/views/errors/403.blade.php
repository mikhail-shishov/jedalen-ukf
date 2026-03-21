<!doctype html>
<html lang="sk">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Prístup zamietnutý</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5 text-center">
                        <h1 class="display-6 mb-3">403</h1>
                        <h2 class="h4 mb-3">Prístup zamietnutý</h2>
                        <p class="text-muted mb-4">
                            {{ $exception->getMessage() ?: 'Nemáte oprávnenie na zobrazenie tejto stránky.' }}
                        </p>

                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    Odhlásiť sa
                                </button>
                            </form>
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                Prihlásiť sa pod iným účtom
                            </a>
                            <a href="/" class="btn btn-outline-secondary">
                                Prejsť na hlavnú stránku
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
