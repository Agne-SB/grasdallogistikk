@extends('admin.layout')

@section('content')
    <h1>Ny bruker</h1>

    @if ($errors->any())
        <div class="alert" role="alert">
        <ul>@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="form-grid" style="gap:14px;max-width:520px;">
        @csrf
        <div class="form-row">
        <label for="name">Navn</label>
        <input id="name" name="name" class="input" required value="{{ old('name') }}">
        </div>

        <div class="form-row">
        <label for="email">E-post</label>
        <input id="email" name="email" type="email" class="input" required value="{{ old('email') }}">
        </div>

        <div class="form-row">
        <label for="role">Rolle</label>
        <select id="role" name="role" class="input">
            <option value="user"  {{ old('role')==='user' ? 'selected' : '' }}>Bruker</option>
            <option value="admin" {{ old('role')==='admin'? 'selected' : '' }}>Admin</option>
        </select>
        </div>

        <label class="inline-check">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
        Aktiv ved opprettelse
        </label>

        <div class="form-actions" style="justify-content:flex-end;">
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Avbryt</a>
        <button class="btn btn-primary">Opprett</button>
        </div>
    </form>

    <p class="auth-sub" style="margin-top:12px;">Brukeren får e-post for å sette passord.</p>
@endsection
