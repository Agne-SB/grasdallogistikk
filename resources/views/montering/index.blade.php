<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Montering</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    </head>
<body data-page-source="montering">
    @include('partials.navbar')


    <h1>Montering (MP)</h1>

    {{-- Search --}}
    <form method="get" class="searchbar">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Søk: OrderKey, tittel, ansvarlig, adresse" class="search-input">
        <button class="btn btn-primary">Søk</button>
        <a href="{{ url()->current() }}" class="btn btn-danger">Nullstill</a>
    </form>

    {{-- TABLE A: Venter på levering --}}
    <h2>Venter på levering</h2>
    @if($waiting->isEmpty())
        <p>Ingen prosjekter venter på levering.</p>
    @else
        <table class="table">
        <thead>
            <tr>
            <th>OrderKey</th>
            <th>Tittel</th>
            <th>Varenotat</th>
            <th>Forventet levering</th>
            <th>Handling</th>
            </tr>
        </thead>
        <tbody>
            @foreach($waiting as $p)
            <tr>
                <td>{{ $p->external_number ?? '–' }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->goods_note ?: '–' }}</td>
                <td>{{ $p->delivery_date?->format('Y-m-d') ?? '–' }}</td>
                <td>
                <form method="POST" action="{{ route('projects.delivered', $p) }}" style="display:inline-block;">
                    @csrf @method('PATCH')
                    <button class="btn btn-success">Levert</button>
                </form>

                <form method="POST" action="{{ route('avvik.store') }}" style="display:inline-block;margin-left:6px;">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $p->id }}">
                    <input type="hidden" name="source" value="montering">
                    <input type="hidden" name="type" value="mangler">
                    <button type="button" class="btn btn-warning js-open-avvik"
                    data-project-id="{{ $p->id }}"
                    data-source="montering"
                    data-orderkey="{{ $p->external_number }}"
                    data-title="{{ $p->title }}"
                    data-customer="{{ $p->customer_name }}"
                    data-address="{{ $p->address }}"
                    data-supervisor="{{ $p->supervisor_name }}"
                    data-assigned="{{ optional($p->updated_at)->format('Y-m-d') }}">
                    Avvik
                    </button>
                </form>
                </td>
            </tr>
            @endforeach
        </tbody>
        </table>
        {{ $waiting->links('pagination::bootstrap-5') }}
    @endif

    {{-- TABLE B: Klargjøring --}}
    <h2 style="margin-top:24px;">Klargjøring</h2>
    @if($preparing->isEmpty())
        <p>Ingen prosjekter i klargjøring.</p>
    @else
        <table class="table">
        <thead>
            <tr>
            <th>OrderKey</th>
            <th>Tittel</th>
            <th>Mottatt</th>
            <th>Plassering</th>
            <th>Handling</th>
            </tr>
        </thead>
        <tbody>
            @foreach($preparing as $p)
            <tr>
                <td>{{ $p->external_number ?? '–' }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->delivered_at?->format('Y-m-d H:i') ?? '–' }}</td>
                <td>
                <form id="prep-{{ $p->id }}" method="POST" action="{{ route('projects.ready', $p) }}">
                    @csrf @method('PATCH')
                    <input type="text" name="staged_location" value="{{ old('staged_location', $p->staged_location) }}" class="form-input" placeholder="f.eks. Reol B3" required>
                </td>
                <td>
                    <button class="btn btn-success">Klargjort</button>
                </form>

                <form method="POST" action="{{ route('avvik.store') }}" style="display:inline-block;margin-left:6px;">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $p->id }}">
                    <input type="hidden" name="source" value="montering">
                    <input type="hidden" name="type" value="skade">
                    <button type="button" class="btn btn-warning js-open-avvik"
                    data-project-id="{{ $p->id }}"
                    data-source="montering"
                    data-orderkey="{{ $p->external_number }}"
                    data-title="{{ $p->title }}"
                    data-customer="{{ $p->customer_name }}"
                    data-address="{{ $p->address }}"
                    data-supervisor="{{ $p->supervisor_name }}"
                    data-assigned="{{ optional($p->updated_at)->format('Y-m-d') }}">
                    Avvik
                    </button>
                </form>
                </td>
            </tr>
            @endforeach
        </tbody>
        </table>
        {{ $preparing->links('pagination::bootstrap-5') }}
    @endif

    {{-- TABLE C: Klar for montering --}}
    <h2 style="margin-top:24px;">Klar for montering</h2>
    @if($ready->isEmpty())
        <p>Ingen klare prosjekter.</p>
    @else
        <table class="table">
        <thead>
            <tr>
            <th>OrderKey</th>
            <th>Tittel</th>
            <th>Plassering</th>
            <th>Klar siden</th>
            <th>Adresse</th>
            <th>Handling</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ready as $p)
            @php $inProgress = (bool) $p->mount_started_at; @endphp
            <tr class="{{ $inProgress ? 'row-inprogress' : '' }}">
                <td>{{ $p->external_number ?? '–' }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->staged_location ?: '–' }}</td>
                <td>{{ $p->ready_at?->format('Y-m-d') ?? '–' }}</td>
                <td>{{ $p->address ?? '–' }}</td>
                <td>
                {{-- I oppdrag (freeze) --}}
                <form method="POST" action="{{ route('projects.mountStart', $p) }}" style="display:inline-block;">
                    @csrf @method('PATCH')
                    <button class="btn {{ $inProgress ? 'btn-agreed' : 'btn-secondary' }}" {{ $inProgress ? 'disabled' : '' }}>
                    {{ $inProgress ? 'I oppdrag ✓' : 'Til oppdrag' }}
                    </button>
                </form>

                {{-- Avvik --}}
                <form method="POST" action="{{ route('avvik.store') }}" style="display:inline-block;margin-left:6px;">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $p->id }}">
                    <input type="hidden" name="source" value="montering">
                    <input type="hidden" name="type" value="annet">
                    <button type="button" class="btn btn-warning js-open-avvik"
                    data-project-id="{{ $p->id }}"
                    data-source="montering"
                    data-orderkey="{{ $p->external_number }}"
                    data-title="{{ $p->title }}"
                    data-customer="{{ $p->customer_name }}"
                    data-address="{{ $p->address }}"
                    data-supervisor="{{ $p->supervisor_name }}"
                    data-assigned="{{ optional($p->updated_at)->format('Y-m-d') }}">
                    Avvik
                    </button>
                </form>

                {{-- Utført (remove from list) --}}
                <form method="POST" action="{{ route('admin.projects.close', $p) }}" style="display:inline-block;margin-left:6px;">
                    @csrf @method('PATCH')
                    <button class="btn btn-success">Utført</button>
                </form>
                </td>
            </tr>
            @endforeach
        </tbody>
        </table>
        {{ $ready->links('pagination::bootstrap-5') }}
    @endif

    @include('partials.toast')
    @include('partials.avvik-modal')
    @include('partials.avvik-modal-js')

</body>
</html>
