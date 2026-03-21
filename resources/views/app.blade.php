<!DOCTYPE html>
<html lang="sk">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jedáleň UKF</title>

    @vite(['resources/js/app.ts'])
</head>

<body class="antialiased">
    <div id="app"></div>
</body>

</html>