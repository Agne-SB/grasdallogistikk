<!doctype html>
<html>
    <head>
    <meta charset="utf-8">
    <title>Home</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
</head>

<body>
    @include('partials.navbar')

    <h1>Welcome to Grasdal Logistic</h1>
    <p>Text and functions will be added later.</p>

    <a href="{{ route('projects.index') }}" class="button">Go to Projects</a>
</body>
</html>
