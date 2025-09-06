<!doctype html>
<html>
    <head>
    <meta charset="utf-8">
    <title>Home</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
</head>

<body>
    @include('partials.navbar')

    <h1>Velkommen til Græsdal Glass logistikk</h1>

    @php
        use Illuminate\Support\Carbon;
        use App\Models\Project;

        $tz = config('app.timezone', 'Europe/Oslo');

        $todayStart    = Carbon::now($tz)->startOfDay();
        $todayEnd      = (clone $todayStart)->endOfDay();
        $tomorrowStart = (clone $todayStart)->addDay()->startOfDay();
        $tomorrowEnd   = (clone $todayStart)->addDay()->endOfDay();

        $base = Project::where('bucket','henting')
        ->whereNull('pickup_collected_at')
        ->whereNotNull('pickup_time_from');

        $todayPickups = (clone $base)
        ->whereBetween('pickup_time_from', [$todayStart, $todayEnd])
        ->orderBy('pickup_time_from')
        ->get();

        $tomorrowPickups = (clone $base)
        ->whereBetween('pickup_time_from', [$tomorrowStart, $tomorrowEnd])
        ->orderBy('pickup_time_from')
        ->get();

        $tz    = config('app.timezone', 'Europe/Oslo');
        $today = Carbon::now($tz)->startOfDay();
        $end   = (clone $today)->addDays(7)->endOfDay();

        /**
        * Show any order with a delivery_date coming up in the next 7 days,
        * that hasn't been delivered yet.
        * (Works across all buckets: prosjekter / henting / montering.)
        */
        $incoming = Project::query()
            ->whereNotNull('delivery_date')
            ->whereNull('delivered_at')
            ->whereBetween('delivery_date', [$today->toDateString(), $end->toDateString()])
            ->orderBy('delivery_date')
            ->orderBy('external_number')
            ->get();

        @endphp

        <h2>Hentes i dag</h2>
        @if($todayPickups->isEmpty())
        <p>Ingen planlagte henting i dag.</p>
        @else
        <table class="table">
            <thead><tr><th>Dato</th><th>OrderKey</th><th>Tittel</th><th>Ansvarlig</th><th>Plassering</th></tr></thead>
            <tbody>
            @foreach($todayPickups as $p)
            <tr>
                <td>{{ $p->pickup_time_from?->timezone($tz)->format('Y-m-d') }}</td>
                <td>{{ $p->external_number }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->supervisor_name }}</td>
                <td>{{ $p->staged_location }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif

        <h2 style="margin-top:18px;">Hentes i morgen</h2>
        @if($tomorrowPickups->isEmpty())
        <p>Ingen planlagte henting i morgen.</p>
        @else
        <table class="table">
            <thead><tr><th>Dato</th><th>OrderKey</th><th>Tittel</th><th>Ansvarlig</th><th>Plassering</th></tr></thead>
            <tbody>
            @foreach($tomorrowPickups as $p)
            <tr>
                <td>{{ $p->pickup_time_from?->timezone($tz)->format('Y-m-d') }}</td>
                <td>{{ $p->external_number }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->supervisor_name }}</td>
                <td>{{ $p->staged_location }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    @endif


        <h2>Forventet levering (neste 7 dager)</h2>

        @if($incoming->isEmpty())
        <p>Ingen forventede leveringer neste 7 dager.</p>
        @else
        <table class="table">
            <thead>
            <tr>
                <th>Dato</th>
                <th>Pr.nr.</th>
                <th>Tittel</th>
                <th>Ansvarlig</th>
                <th>Merknad</th>
                <th>Gjenstår</th>
            </tr>
            </thead>
            <tbody>
            @foreach($incoming as $p)
                @php
                $d = $p->delivery_date?->setTimezone($tz);
                $left = $d ? $today->diffInDays($d, false) : null; // negative if past (shouldn't happen with the filter)
                $hint = $d?->isToday() ? 'I dag' : ($d?->isTomorrow() ? 'I morgen' : ($left !== null ? $left.' d' : ''));
                @endphp
                <tr>
                <td>{{ $d?->toDateString() ?? '–' }}</td>
                <td>{{ $p->external_number ?? '–' }}</td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->supervisor_name ?? '–' }}</td>
                <td>{{ $p->goods_note ?: '–' }}</td>
                <td class="muted">{{ $hint }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>
