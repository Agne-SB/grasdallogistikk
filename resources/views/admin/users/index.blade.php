@extends('admin.layout')

    @section('content')
    <h1>Brukere</h1>

    @if (session('ok'))
        <div class="alert" style="background:#f0fdf4;color:#166534;border-color:#bbf7d0">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="alert">{{ session('err') }}</div>
    @endif

    <form method="GET" class="form-grid" style="grid-template-columns:1fr auto;gap:.5rem;max-width:420px;margin:.5rem 0 1rem;">
        <input class="input" type="search" name="q" value="{{ $q ?? '' }}" placeholder="Søk navn eller e-post">
        <button class="btn btn-ghost" type="submit">Søk</button>
    </form>

    <p><a class="btn btn-primary" href="{{ route('admin.users.create') }}">Ny bruker</a></p>

    <div class="table-wrap">
        <table class="table">
        <thead>
            <tr>
            <th>Navn</th>
            <th>E-post</th>
            <th>Rolle</th>
            <th>Status</th>
            <th>Sist innlogget</th>
            <th>Handlinger</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($users as $u)
            <tr>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->role }}</td>
            <td>{{ $u->is_active ? 'Aktiv' : 'Deaktivert' }}</td>
            <td>{{ $u->last_login_at? $u->last_login_at->format('Y-m-d H:i') : '—' }}</td>
            <td>
                <form method="POST" action="{{ route('admin.users.role',$u) }}" style="display:inline;">
                @csrf @method('PATCH')
                <input type="hidden" name="role" value="{{ $u->role === 'admin' ? 'user' : 'admin' }}">
                <button class="btn btn-ghost" {{ (auth()->id()===$u->id && $u->role==='admin') ? 'disabled' : '' }}>
                    {{ $u->role==='admin' ? 'Gjør til bruker' : 'Gjør til admin' }}
                </button>
                </form>

                @if($u->is_active)
                <form method="POST" action="{{ route('admin.users.deactivate',$u) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <button class="btn btn-ghost" {{ auth()->id()===$u->id ? 'disabled' : '' }}>Deaktiver</button>
                </form>
                @else
                <form method="POST" action="{{ route('admin.users.activate',$u) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <button class="btn btn-ghost">Aktiver</button>
                </form>
                @endif

                <form method="POST" action="{{ route('admin.users.sendReset',$u) }}" style="display:inline;">
                @csrf
                <button class="btn btn-ghost">Send reset</button>
                </form>
            </td>
            </tr>
        @endforeach
        </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">
        {{ $users->links() }}
    </div>
@endsection
