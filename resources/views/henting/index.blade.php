<!doctype html>
<html lang="no">
    <head>
    <meta charset="utf-8">
    <title>Henting</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    </head>
    <body>
    @include('partials.navbar')

    <h1>Henting (HO)</h1>

    {{-- Search (same as Prosjekter) --}}
    <form method="get" class="searchbar">
        <input type="text" name="q" value="{{ request('q') }}"
            placeholder="Søk: Prosjekt nummer, tittel, ansvarlig, plassering..."
            class="search-input" autofocus>
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
            <th>Pr.nr.</th>
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

                {{-- Read-only: use values saved on Prosjekter --}}
                <td>{{ $p->goods_note ?: '–' }}</td>
                <td>{{ $p->delivery_date ? $p->delivery_date->format('Y-m-d') : '–' }}</td>

                <td>
                    {{-- Levert --}}
                    <form method="POST" action="{{ route('projects.delivered', $p) }}" 
                    style="display:inline-block;">
                    @csrf @method('PATCH')
                    <button class="btn btn-success">Levert</button>
                    </form>

                    {{-- Avvik --}}
                    <form method="POST" action="{{ route('avvik.store') }}" style="display:inline-block;margin-left:6px;">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $p->id }}">
                    <input type="hidden" name="source" value="henting">
                    <input type="hidden" name="type" value="mangler">
                    <button class="btn btn-warning">Avvik</button>
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
            <th>Pr.nr.</th>
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
                <td>{{ optional($p->delivered_at)->format('Y-m-d H:i') }}</td>
                <td>
                    <form id="prep-{{ $p->id }}" method="POST" action="{{ route('projects.ready', $p) }}">
                        @csrf @method('PATCH')
                        <input type="text"
                            name="staged_location"
                            value="{{ old('staged_location', $p->staged_location) }}"
                            class="form-input"
                            placeholder="f.eks. Reol B3"
                            required>
                    </td>
                    <td>
                        <button class="btn btn-success">Klargjort</button>
                    </form>

                    {{-- Avvik --}}
                    <form method="POST" action="{{ route('avvik.store') }}" style="display:inline-block;margin-left:6px;">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $p->id }}">
                        <input type="hidden" name="source" value="henting">
                        <input type="hidden" name="type" value="skade">
                        <button class="btn btn-warning">Avvik</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
        </table>
        {{ $preparing->links('pagination::bootstrap-5') }}
    @endif

    {{-- TABLE C: Klar for henting --}}
    <h2 style="margin-top:24px;">Klar for henting</h2>
    @if($ready->isEmpty())
        <p>Ingen klare prosjekter.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Pr.nr.</th>
                    <th>Tittel</th>
                    <th>Plassering</th>
                    <th>Klar siden</th>
                    <th>Avtalt dato</th>
                    <th>Handling</th>
                </tr>
            </thead>

            <tbody>
            @foreach($ready as $p)
            @php
                $today = now()->startOfDay();

                // 1) Badge for contextual hint (today / tomorrow)
                $badge = null;
                if ($p->pickup_time_from) {
                    if ($p->pickup_time_from->isToday())        $badge = 'I dag';
                    elseif ($p->pickup_time_from->isTomorrow()) $badge = 'I morgen';
                }

                // 2) Late indicator (+Xd)
                $lateDays = 0;

                // If pickup date is in the past and not collected → days overdue since agreed date
                if ($p->pickup_time_from && $p->pickup_time_from->lt($today) && !$p->pickup_collected_at) {
                    $lateDays = $p->pickup_time_from->diffInDays($today);
                }
                // Else, if no pickup date but it's been >7 days since ready → days beyond 7 since ready
                elseif ($p->ready_at && $p->ready_at->lt($today->copy()->subDays(7)) && !$p->pickup_collected_at) {
                    $lateDays = $p->ready_at->diffInDays($today) - 7;
                }
            @endphp

            <tr class="{{ $lateDays > 0 ? 'row-late' : '' }}">
                <td>{{ $p->external_number ?? '–' }}</td>
                <td>{{ $p->title }}</td>

                {{-- NEW: Plassering (read-only) --}}
                <td>{{ $p->staged_location ?: '–' }}</td>

                <td>{{ $p->ready_at?->format('Y-m-d') ?? '–' }}</td>

                <td>
                    <form id="sched-{{ $p->id }}" method="POST" action="{{ route('projects.schedulePickup', $p) }}">
                    @csrf @method('PATCH')
                    <input type="date"
                            name="pickup_date"
                            value="{{ old('pickup_date', $p->pickup_time_from?->toDateString()) }}"
                            class="form-date"
                            required>

                    @if($badge)
                        <div class="muted" style="margin-top:6px">{{ $badge }}</div>
                    @endif
                    @if($lateDays > 0)
                        <div class="late-badge" style="margin-top:6px">+{{ $lateDays }}d</div>
                    @endif
                </td>

                <td>
                    @php $agreed = (bool) $p->pickup_time_from; @endphp

                    <button class="btn {{ $agreed ? 'btn-agreed' : 'btn-secondary' }}">
                    {{ $agreed ? 'Endre dato' : 'Avtalt dato' }}
                    </button>

                    </form>

                    <form method="POST" action="{{ route('projects.collected', $p) }}" style="display:inline-block;margin-left:6px;">
                    @csrf @method('PATCH')
                    <button class="btn btn-success">Utlevert</button>
                    </form>

                    <form method="POST" action="{{ route('avvik.store') }}" style="display:inline-block;margin-left:6px;">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $p->id }}">
                    <input type="hidden" name="source" value="henting">
                    <input type="hidden" name="type" value="annet">
                    <button class="btn btn-warning">Avvik</button>
                    </form>
                </td>
            </tr>
            
            @endforeach
            </tbody>

        </table>
        {{ $ready->links('pagination::bootstrap-5') }}
    @endif

    @include('partials.toast')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[id^="prep-"]').forEach(function (f) {
            const input = f.querySelector('input[name="staged_location"]');
            const btn   = f.querySelector('button.btn.btn-success');
            if (!input || !btn) return;
            const toggle = () => btn.disabled = !input.value.trim();
            input.addEventListener('input', toggle);
            toggle();
        });
    });
    </script>
</body>
</html>

