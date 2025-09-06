<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Logg inn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{ asset('css/global.css') }}?v=3" rel="stylesheet">
</head>
<body class="auth-page">
    <section class="auth-card">
        <img class="login-logo" src="{{ asset('images/logo.png') }}" alt="Græsdal" onerror="this.style.display='none'">

        <h1 class="auth-title">Logg inn</h1>
        <p class="auth-sub">Logg inn med firma e-post for å fortsette.</p>

        @if ($errors->any())
        <div class="alert" role="alert">
            <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="form-grid">
        @csrf
        <div class="form-row">
            <label for="email">E-post</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="form-row">
            <label for="password">Passord</label>
            <input id="password" name="password" type="password" class="input" required autocomplete="current-password">
        </div>

        <div class="form-footer">
            <label for="remember" class="inline-check">
            <input id="remember" type="checkbox" name="remember" value="1"> Husk meg
            </label>

            <div class="actions-center">
            <button type="submit" class="btn btn-primary btn-lg">Logg inn</button>
            <a class="auth-link" href="{{ route('password.request') }}">Glemt passord?</a>
            </div>
        </div>
        </form>
    </section>
</body>
</html>
