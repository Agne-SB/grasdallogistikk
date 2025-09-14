<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    <title>Admin</title>
</head>
<body>
    @include('admin.partials.topnav')

    <div class="admin-shell">
        <main class="admin-content">
            @yield('content')
        </main>
    </div>
    @include('partials.toast')
</body>

</html>
