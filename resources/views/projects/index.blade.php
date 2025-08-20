<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Prosjekter</title>
        <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f3f4f6; text-align: left; }
        nav a { margin-right: 12px; }
        </style>
    </head>
    <body>
        <h1>Prosjekter</h1>
        <nav>
        <a href="/">Home</a>
        </nav>

        @if($projects->isEmpty())
        <p>Ingen prosjekter funnet.</p>
        @else
        <table>
            <tr>
            <th>ID</th>
            <th>Tittel</th>
            <th>Kunde</th>
            <th>Adresse</th>
            <th>Status</th>
            <th>Sist oppdatert</th>
            </tr>
            @foreach ($projects as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->customer_name }}</td>
                <td>{{ $p->address }}</td>
                <td>{{ $p->status }}</td>
                <td>{{ $p->updated_at_from_api ?? $p->updated_at }}</td>
            </tr>
            @endforeach
        </table>

        <div style="margin-top:12px;">
            {{ $projects->links() }}
        </div>
        @endif
    </body>
</html>
