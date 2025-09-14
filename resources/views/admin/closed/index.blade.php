@extends('admin.layout')

@section('content')
<h1>Lukkede prosjekter</h1>

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
            placeholder="Søk: Prosjekt nummer, tittel, ansvarlig..."
            class="search-input"
            autofocus>
        <button class="btn btn-primary">Søk</button>
        <a href="{{ url()->current() }}" class="btn btn-danger">Nullstill</a>
    </form>

    <div class="table-wrap">
    <table class="table table-admin">
        <thead>
        <tr>
            <th>Pr.nr.</th>
            <th>Tittel</th>
            <th>Ansvarlig</th>
            <th>Lukket</th>
            <th class="col-actions">Handling</th>
        </tr>
        </thead>
        <tbody>
    @forelse ($projects as $p)
        <tr>
            <td>{{ $p->external_number }}</td>
            <td>{{ $p->title }}</td>
            <td>{{ $p->supervisor_name ?? '—' }}</td>
            <td>{{ $p->vendor_closed_at ? \Illuminate\Support\Carbon::parse($p->vendor_closed_at)->format('Y-m-d H:i') : '—' }}</td>
            <td class="actions-cell col-actions">
            <div class="actions-inline">
                {{-- Gjenåpne --}}
                <form method="POST" action="{{ route('admin.closed.reopen', $p) }}">
                @csrf @method('PATCH')
                <button class="btn btn-ghost btn-fixed">Gjenåpne</button>
                </form>

                {{-- Slett (siste i linjen) --}}
                <form method="POST" action="{{ route('admin.closed.destroy', $p) }}"
                    onsubmit="return confirm('Slette prosjekt {{ $p->external_number }}?');">
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
            </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted">Ingen lukkede prosjekter.</td></tr>
    @endforelse
        </tbody>
    </table>
    </div>

    <div class="pager">
        {{ $projects->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endsection
