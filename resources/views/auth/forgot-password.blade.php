<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Glemt passord</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{ asset('css/global.css') }}" rel="stylesheet">
</head>
<body class="auth-page">
    <section class="auth-card">
        <h1 class="auth-title">Glemt passord</h1>
        <p class="auth-sub">Skriv inn e-postadressen din så sender vi en lenke for å tilbakestille passordet.</p>

        @if (session('status'))
        <div class="alert" role="alert">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
        <div class="alert" role="alert">
            <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="form-grid">
        @csrf
        <div class="form-row">
            <label for="email">E-post</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Send tilbakestillingslenke</button>
            <a class="auth-link" href="{{ route('login') }}">Tilbake til innlogging</a>
        </div>
        </form>
    </section>
</body>
</html>
