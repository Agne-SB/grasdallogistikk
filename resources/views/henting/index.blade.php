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

    {{-- Search --}}
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
                        <button type="button" class="btn btn-warning js-open-avvik"
                            data-project-id="{{ $p->id }}"
                            data-source="henting"                
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
                        <button type="button" class="btn btn-warning js-open-avvik"
                            data-project-id="{{ $p->id }}"
                            data-source="henting"                
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

            // Badge hint
            $badge = null;
            if ($p->pickup_time_from) {
            if ($p->pickup_time_from->isToday())        $badge = 'I dag';
            elseif ($p->pickup_time_from->isTomorrow()) $badge = 'I morgen';
            }

            // Late indicator (+Xd)
            $lateDays = 0;
            if ($p->pickup_time_from && $p->pickup_time_from->lt($today) && !$p->pickup_collected_at) {
            $lateDays = $p->pickup_time_from->diffInDays($today);
            } elseif ($p->ready_at && $p->ready_at->lt($today->copy()->subDays(7)) && !$p->pickup_collected_at) {
            $lateDays = $p->ready_at->diffInDays($today) - 7;
            }
        @endphp

        <tr class="{{ $lateDays > 0 ? 'row-late' : '' }}">
            <td>{{ $p->external_number ?? '–' }}</td>
            <td>{{ $p->title }}</td>
            <td>{{ $p->staged_location ?: '–' }}</td>
            <td>{{ $p->ready_at?->format('Y-m-d') ?? '–' }}</td>

            {{-- Avtalt dato (read-only display) --}}
            <td>
            {{ $p->pickup_time_from?->format('Y-m-d') ?? '–' }}
            @if($badge)
                <div class="muted" style="margin-top:4px;">{{ $badge }}</div>
            @endif
            @if($lateDays > 0)
                <div class="late-badge" style="margin-top:4px;">+{{ $lateDays }}d</div>
            @endif
            </td>

            {{-- Handling: all buttons & inputs live here --}}
            <td>
            {{-- 1) Avtalt dato (sets/changes pickup date) --}}
            <form id="schedule-{{ $p->id }}" method="POST" action="{{ route('projects.schedulePickup', $p) }}" style="display:inline-block;">
                @csrf @method('PATCH')
                <div style="display:inline-flex; gap:6px; align-items:center;">
                <input type="date"
                        name="pickup_date"
                        value="{{ old('pickup_date', $p->pickup_time_from?->toDateString()) }}"
                        class="form-input"
                        required>
                @php $agreed = (bool) $p->pickup_time_from; @endphp
                <button class="btn {{ $agreed ? 'btn-agreed' : 'btn-secondary' }}">
                    {{ $agreed ? 'Endre' : 'Avtal' }}
                </button>
                </div>
            </form>

            {{-- 2) Utlevert (requires avtalt dato) --}}
            <form id="collect-{{ $p->id }}" method="POST" action="{{ route('projects.collected', $p) }}" style="display:inline-block; margin-left:6px;">
                @csrf @method('PATCH')
                <button class="btn btn-success">Utlevert</button>
            </form>

            {{-- 3) Avvik (opens modal) --}}
            <button type="button" class="btn btn-warning js-open-avvik" style="margin-left:6px;"
                data-project-id="{{ $p->id }}"
                data-source="henting"
                data-orderkey="{{ $p->external_number }}"
                data-title="{{ $p->title }}"
                data-customer="{{ $p->customer_name }}"
                data-address="{{ $p->address }}"
                data-supervisor="{{ $p->supervisor_name }}"
                data-assigned="{{ optional($p->updated_at)->format('Y-m-d') }}">
                Avvik
            </button>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
        // Require Avtalt dato before allowing "Utlevert"
        document.querySelectorAll('form[id^="collect-"]').forEach(function (collectForm) {
            collectForm.addEventListener('submit', function (e) {
            const row = collectForm.closest('tr');
            const dateInput = row ? row.querySelector('form[id^="schedule-"] input[name="pickup_date"]') : null;
            if (!dateInput || !dateInput.value.trim()) {
                e.preventDefault();
                if (dateInput) {
                // Show native browser validation bubble on the actual date field
                dateInput.focus();
                dateInput.reportValidity();
                } else {
                alert('Sett «Avtalt dato» først.');
                }
            }
            });
        });
        });
    </script>

</body>
</html>

