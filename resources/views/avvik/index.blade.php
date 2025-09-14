<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Avvik</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    </head>
    <body>
    @include('partials.navbar')

    <h1>Avvik</h1>

    {{-- Search  --}}
    <form method="get" class="searchbar">
        <input type="text" name="q" value="{{ request('q') }}"
            placeholder="Søk: Prosjekt nummer, tittel, ansvarlig..."
            class="search-input" autofocus>
        <button class="btn btn-primary">Søk</button>
        <a href="{{ url()->current() }}" class="btn btn-danger">Nullstill</a>
    </form>

    @php use Illuminate\Support\Str; @endphp

    @if($open->isEmpty())
        <p>Ingen åpne avvik.</p>
    @else
        <table class="table">
        <thead>
            <tr>
            <th>Pr.nr</th>
            <th>Tittel</th>
            <th>Kilde</th>
            <th>Type</th>
            <th>Notat</th>
            <th>Åpnet</th>
            <th>Handling</th>
            </tr>
        </thead>
        <tbody>
            @foreach($open as $d)
            <tr>
                <td>{{ $d->project?->external_number ?? '–' }}</td>
                <td>
                {{ $d->subject_title }}
                @if($d->source === 'varer')
                    <div class="muted">Leverandør: {{ $d->stockItem->supplier ?? '—' }}</div>
                @else
                    <div class="muted">Pr.nr.: {{ $d->project->external_number ?? '—' }}</div>
                @endif
                </td>
                <td>{{ ucfirst($d->source) }}</td>
                <td>{{ $d->type }}</td>
                <td>{{ Str::limit($d->note, 80) }}</td>
                <td>{{ optional($d->opened_at ?? $d->created_at)->format('Y-m-d H:i') }}</td>
                <td>
                <form method="POST" action="{{ route('avvik.resolve', $d) }}">
                    @csrf @method('PATCH')
                    <button type="button" class="btn btn-success btn-block js-open-resolve"
                    data-deviation-id="{{ $d->id }}"
                    data-orderkey="{{ $d->project?->external_number }}"
                    data-title="{{ $d->project?->title }}"
                    data-customer="{{ $d->project?->customer_name }}"
                    data-address="{{ $d->project?->address }}"
                    data-supervisor="{{ $d->project?->supervisor_name }}"
                    data-source="{{ $d->source }}"
                    data-goods-note="{{ $d->project?->goods_note }}"
                    data-delivery-date="{{ optional($d->project?->delivery_date)->format('Y-m-d') }}"
                    data-suggest-dest="{{ $d->source }}"> {{-- default to same side (HO/MO) --}}
                    Løs
                    </button>

                </form>
                </td>
            </tr>
            @endforeach
        </tbody>
        </table>

        {{ $open->links('pagination::bootstrap-5') }}
    @endif

    @include('partials.toast')
    @include('partials/avvik-resolve-modal')
    @include('partials/avvik-resolve-js')

</body>
</html>
