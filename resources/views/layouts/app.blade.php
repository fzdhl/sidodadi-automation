<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<nav class="sidebar position-fixed top-0 start-0 vh-100 p-3" aria-label="Navigasi utama">
    <div class="d-flex align-items-center mb-4">
        <img src="{{ asset('logo.png') }}" alt="Logo Sidodadi" style="height: 32px; width: 32px; margin-right: 10px; object-fit: contain;">
        <h2 class="m-0 fw-bolder">SIDODADI</h2>
    </div>
    <a class="nav-link {{ request()->routeIs('documents.create') ? 'active' : '' }} d-block my-1 px-3 py-2 rounded text-decoration-none" href="{{ route('documents.create') }}">Buat Dokumen</a>
    <a class="nav-link {{ request()->routeIs('residents.index') ? 'active' : '' }} d-block my-1 px-3 py-2 rounded text-decoration-none" href="{{ route('residents.index') }}">Data Penduduk</a>
</nav>

<main class="page-content" id="app-content">
    @yield('content')
</main>
</body>
</html>
