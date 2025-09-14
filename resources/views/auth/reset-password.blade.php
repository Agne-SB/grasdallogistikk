<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Tilbakestill passord</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{ asset('css/global.css') }}" rel="stylesheet">
</head>
<body class="auth-page">
    <section class="auth-card">
        <h1 class="auth-title">Tilbakestill passord</h1>
        <p class="auth-sub">Velg et nytt passord for kontoen din.</p>

        @if ($errors->any())
        <div class="alert" role="alert">
            <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}" class="form-grid">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-row">
            <label for="email">E-post</label>
            <input id="email" name="email" type="email" class="input" value="{{ $email ?? old('email') }}" required autofocus>
        </div>

        <div class="form-row">
            <label for="password">Nytt passord</label>
            <input id="password" name="password" type="password" class="input" required autocomplete="new-password">
        </div>

        <div class="form-row">
            <label for="password_confirmation">Bekreft passord</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Oppdater passord</button>
            <a class="auth-link" href="{{ route('login') }}">Tilbake til innlogging</a>
        </div>
        </form>
    </section>
</body>
</html>
