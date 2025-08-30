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

    @php use Illuminate\Support\Str; @endphp

    @if($open->isEmpty())
        <p>Ingen åpne avvik.</p>
    @else
        <table class="table">
        <thead>
            <tr>
            <th>OrderKey</th>
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
                <td>{{ $d->project?->title ?? '–' }}</td>
                <td>{{ ucfirst($d->source) }}</td>
                <td>{{ $d->type }}</td>
                <td>{{ Str::limit($d->note, 80) }}</td>
                <td>{{ optional($d->opened_at ?? $d->created_at)->format('Y-m-d H:i') }}</td>
                <td>
                <form method="POST" action="{{ route('avvik.resolve', $d) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-success">Løst</button>
                </form>
                </td>
            </tr>
            @endforeach
        </tbody>
        </table>

        {{ $open->links('pagination::bootstrap-5') }}
    @endif

    <hr>

    <h2>Registrer nytt avvik</h2>
    <form method="POST" action="{{ route('avvik.store') }}" class="searchbar" style="flex-wrap:wrap;">
        @csrf
        <input type="number" name="project_id" class="form-input" placeholder="Project ID" style="max-width:160px">
        <select name="source" class="form-input" style="max-width:160px">
        <option value="henting">Henting</option>
        <option value="montering">Montering</option>
        </select>
        <input type="text" name="type" class="form-input" placeholder="Type (mangler/skade/…)" style="max-width:220px">
        <input type="number" name="qty_expected" class="form-input" placeholder="Antall forventet" style="max-width:160px">
        <input type="number" name="qty_received" class="form-input" placeholder="Antall mottatt" style="max-width:160px">
        <input type="text" name="note" class="form-input" placeholder="Notat (valgfritt)" style="min-width:240px; flex:1">
        <button class="btn btn-primary">Lagre avvik</button>
    </form>

    @include('partials.toast')
</body>
</html>
