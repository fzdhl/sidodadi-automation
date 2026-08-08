<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#001207">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<!-- Splash Screen Overlay -->
<div id="splash-screen" class="splash-screen">
    <div class="splash-content text-center">
        <div class="splash-logo-container">
            <div class="splash-logo-glow"></div>
            <img src="{{ asset('logo.png') }}" alt="Logo Sidodadi" class="splash-logo">
        </div>
        <h2 class="splash-subtitle">PEMERINTAH DESA SIDODADI</h2>
        <h1 class="splash-title">Sidodadi Generator</h1>
        <p class="splash-tagline">Sistem Otomasi Pelayanan Surat & Data Penduduk</p>
        
        <div class="splash-loader-box">
            <div class="splash-progress-track">
                <div id="splash-progress-bar" class="splash-progress-bar"></div>
            </div>
            <div id="splash-status" class="splash-status">Memuat sistem...</div>
        </div>
    </div>
</div>

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

