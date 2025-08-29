<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Montering</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
</head>
    
<body>
    @include('partials.navbar')

    <h1>Montering</h1>

    @if($projects->isEmpty())
        <p>Ingen prosjekter funnet.</p>
    @else
        <table class="table">
        <thead>
            <tr>
            <th>ID</th>
            <th>Tittel</th>
            <th>Kunde</th>
            <th>Adresse</th>
            <th>Varenotat</th>
            <th>Leveringsdato</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->customer_name ?? '' }}</td>
                <td>{{ $p->address }}</td>
                <td>{{ $p->goods_note ?? '' }}</td>
                <td>{{ optional($p->delivery_date)->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
        </table>

        <div class="pager">
        {{ $projects->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
</body>
</html>
