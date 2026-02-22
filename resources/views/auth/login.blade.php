<!DOCTYPE html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prihlásenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../styles/admin.css" rel="stylesheet">
</head>

<body>

    <div class="login-form">
        <div class="card shadow p-4">
            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="login_id" class="form-label">Osobné číslo</label>
                    <input type="text" name="login_id" class="form-control" id="login_id" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Heslo</label>
                    <input type="password" name="password" class="form-control" id="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Zapamätať si ma</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Prihlásiť sa</button>
            </form>
        </div>
    </div>

</body>

</html>