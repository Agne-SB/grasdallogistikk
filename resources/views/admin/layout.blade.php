<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    <title>Admin</title>
</head>
<body>
    @include('partials.navbar')

    <div class="admin-shell">
        <aside class="admin-sidebar">
        <div class="admin-side-title">Admin</div>
        <nav class="admin-side-nav">
            <a href="{{ url('/admin/users') }}"
            class="admin-side-link {{ request()->is('admin/users*') ? 'active' : '' }}">
            Brukere
            </a>

            <a href="{{ url('/admin/closed-projects') }}"
            class="admin-side-link {{ request()->is('admin/closed-projects*') ? 'active' : '' }}">
            Lukkede prosjekter
            </a>
        </nav>
        </aside>

        <main class="admin-content">
        @yield('content')
        </main>
    </div>
</body>
</html>
