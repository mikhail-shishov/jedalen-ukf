<!DOCTYPE html>
<html lang="sk">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Jedáleň UKF">
    <meta property="og:title" content="Jedáleň UKF">
    <meta property="og:image" content="{{ asset('favicon.ico') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Jedáleň UKF">
    <meta name="twitter:image" content="{{ asset('favicon.ico') }}">
    <title>Jedáleň UKF</title>

    @vite(['resources/js/app.ts'])
</head>

<body class="antialiased">
    <div id="app"></div>
</body>

</html>