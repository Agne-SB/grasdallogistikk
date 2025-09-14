<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Varer til lager</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
</head>
<body>
@include('partials.navbar')

<h1>Varer til lager</h1>

@if (session('ok'))
    <div class="alert" style="background:#f0fdf4;color:#166534;border-color:#bbf7d0">{{ session('ok') }}</div>
@endif
@if ($errors->any())
    <div class="alert">{{ $errors->first() }}</div>
@endif

{{-- Search --}}
<form method="get" class="searchbar">
    <input type="text" name="q" value="{{ request('q') }}"
            placeholder="Søk: tittel eller leverandør..."
            class="search-input" autofocus>
    <button class="btn btn-primary">Søk</button>
    <a href="{{ url()->current() }}" class="btn btn-danger">Nullstill</a>
</form>

{{-- Add row--}}
<form method="POST" action="{{ route('varer.store') }}"
        class="form-grid"
        style="grid-template-columns: 2fr 1.5fr 1fr auto; gap:.5rem; align-items:end; margin:.75rem 0 1rem;">
    @csrf
    <div>
        <label class="muted">Tittel</label>
        <input class="input" name="title" required>
    </div>
    <div>
        <label class="muted">Leverandør</label>
        <input class="input" name="supplier" required>
    </div>
    <div>
        <label class="muted">Leveringsdato</label>
        <input class="form-date" type="date" name="delivery_date">
    </div>
    <div>
        <button class="btn btn-primary">Legg til</button>
    </div>
</form>


<div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th>Tittel/Info</th>
            <th>Leverandør</th>
            <th>Leveringsdato</th>
            <th>Handling</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($items as $it)
        <tr>
            <td>{{ $it->title }}</td>
            <td>{{ $it->supplier }}</td>
            <td>{{ $it->delivery_date?->format('Y-m-d') ?? '—' }}</td>

            <td>
            {{-- Levert --}}
            <form method="POST" action="{{ route('varer.status', $it) }}" style="display:inline-block;">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="levert">
                <button class="btn btn-success" {{ $it->status==='levert' ? 'disabled' : '' }}>
                Levert
                </button>
            </form>

            {{-- Avvik --}}
            <button type="button" class="btn btn-warning js-open-avvik"
                    data-stock-id="{{ $it->id }}"
                    data-source="varer"
                    data-orderkey="{{ $it->title }}"
                    data-title="{{ $it->title }}"
                    data-customer="—"
                    data-address="—"
                    data-supervisor="{{ $it->supplier }}"
                    data-assigned="{{ $it->delivery_date?->format('Y-m-d') ?? '—' }}">
                Avvik
                </button>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="muted">Ingen varer registrert.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@include('partials.avvik-modal')
@include('partials.avvik-modal-js')
@include('partials.toast')

</body>
</html>
