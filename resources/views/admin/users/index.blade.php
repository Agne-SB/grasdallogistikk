@extends('admin.layout')

    @section('content')
    <h1>Brukere</h1>

    @if (session('ok'))
        <div class="alert" style="background:#f0fdf4;color:#166534;border-color:#bbf7d0">{{ session('ok') }}</div>
    @endif
    @if (session('err'))
        <div class="alert">{{ session('err') }}</div>
    @endif

    <form method="get" class="searchbar">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Søk: navn eller e-post"
            class="search-input"
            autofocus>
        <button class="btn btn-primary">Søk</button>
        <a href="{{ url()->current() }}" class="btn btn-danger">Nullstill</a>
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
            <td class="actions-cell">
                <div class="actions-inline">

                    {{-- Rolle: admin ⇄ user --}}
                    <form method="POST" action="{{ route('admin.users.role',$u) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="role" value="{{ $u->role === 'admin' ? 'user' : 'admin' }}">
                    <button class="btn btn-ghost btn-fixed"
                        {{ (auth()->id()===$u->id && $u->role==='admin') ? 'disabled' : '' }}>
                        {{ $u->role==='admin' ? 'Gjør bruker' : 'Gjør admin' }}
                    </button>
                    </form>

                    {{-- Aktiv / Deaktiv --}}
                    @if($u->is_active)
                    <form method="POST" action="{{ route('admin.users.deactivate',$u) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-ghost btn-fixed" {{ auth()->id()===$u->id ? 'disabled' : '' }}>
                        Deaktiver
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.users.activate',$u) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-ghost btn-fixed">
                        Aktiver
                        </button>
                    </form>
                    @endif

                    {{-- Send reset (always available) --}}
                    <form method="POST" action="{{ route('admin.users.sendReset',$u) }}">
                    @csrf
                    <button class="btn btn-ghost btn-fixed">Send reset</button>
                    </form>

                    {{-- Slett (kun når deaktivert) — LAST IN LINE --}}
                    @if(!$u->is_active)
                    <form method="POST" action="{{ route('admin.users.destroy',$u) }}"
                            onsubmit="return confirm('Slette bruker {{ $u->email }}?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-icon" title="Slett" aria-label="Slett">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                            <path d="M10 11v6"></path>
                            <path d="M14 11v6"></path>
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                        </svg>
                        </button>
                    </form>
                    @endif
                </div>
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
