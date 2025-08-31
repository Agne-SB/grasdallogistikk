<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Planlegging</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    <style>
        .row-avvik    { outline: 2px solid #ff5f00; outline-offset:-2px; }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body>
    @include('partials.navbar')

    <h1>Planlegging</h1>

    <div id="plan-map" style="height:420px;width:100%;border:1px solid #d1d5db;border-radius:8px;margin:8px 0 16px;"></div>

    {{-- TABLE 1: Levert (hos oss, venter klargjøring) --}}
    <h2 style="margin-top:18px;">Levert</h2>
    @if($levert->isEmpty())
    <p>Ingen leverte prosjekter venter.</p>
    @else
    <table class="table table-bucket-green">
    <thead>
        <tr>
        <th>Pr.nr.</th>
        <th>Kunde</th>
        <th>Adresse</th>
        <th>Mann</th>
        <th>Avstand fra GG</th>
        </tr>
    </thead>
    <tbody>
        @foreach($levert as $p)
        <tr class="{{ $p->has_open_avvik ? 'row-avvik' : '' }}">
        <td>{{ $p->external_number }}</td>
        <td>{{ $p->customer_name }}</td>
        <td>{{ $p->address }}</td>
        <td>{{ $p->mann ?? '–' }}</td>
        <td>@if (is_null($p->distance_km)) — @else {{ number_format($p->distance_km, 1, ',', ' ') }} km @endif </td>
        </tr>
        @endforeach
    </tbody>
    </table>
    @endif

    {{-- TABLE 2: Leveres i 7 dager --}}
    <h2 style="margin-top:18px;">Leveres innen 7 dager</h2>
    @if($leveres7->isEmpty())
    <p>Ingen leveranser neste 7 dager.</p>
    @else
    <table class="table table-bucket-orange">
    <thead>
        <tr>
        <th>Pr.nr.</th>
        <th>Kunde</th>
        <th>Adresse</th>
        <th>Mann</th>
        <th>Avstand fra GG</th>
        <th>Gjenstår</th>
        </tr>
    </thead>
    <tbody>
        @foreach($leveres7 as $p)
        @php $left = now()->startOfDay()->diffInDays($p->delivery_date, false); @endphp
        <tr class="{{ $p->has_open_avvik ? 'row-avvik' : '' }}">
        <td>{{ $p->external_number }}</td>
        <td>{{ $p->customer_name }}</td>
        <td>{{ $p->address }}</td>
        <td>{{ $p->mann ?? '–' }}</td>
        <td>@if (is_null($p->distance_km)) — @else {{ number_format($p->distance_km, 1, ',', ' ') }} km @endif </td>
        <td class="muted">{{ $left <= 0 ? 'I dag' : $left.' d' }}</td>
        </tr>
        @endforeach
    </tbody>
    </table>
    @endif

    {{-- TABLE 3: Leveres om 8–14 dager --}}
    <h2 style="margin-top:18px;">Leveres om 8–14 dager</h2>
    @if($leveres14->isEmpty())
    <p>Ingen leveranser i 8–14 dagers vindu.</p>
    @else
    <table class="table table-bucket-gray">
    <thead>
        <tr>
        <th>Pr.nr.</th>
        <th>Kunde</th>
        <th>Adresse</th>
        <th>Mann</th>
        <th>Avstand fra GG</th>
        <th>Gjenstår</th>
        </tr>
    </thead>
    <tbody>
        @foreach($leveres14 as $p)
        @php $left = now()->startOfDay()->diffInDays($p->delivery_date, false); @endphp
        <tr class="{{ $p->has_open_avvik ? 'row-avvik' : '' }}">
        <td>{{ $p->external_number }}</td>
        <td>{{ $p->customer_name }}</td>
        <td>{{ $p->address }}</td>
        <td>{{ $p->mann ?? '–' }}</td>
        <td>@if (is_null($p->distance_km)) — @else {{ number_format($p->distance_km, 1, ',', ' ') }} km @endif </td>
        <td class="muted">{{ $left.' d' }}</td>
        </tr>
        @endforeach
    </tbody>
    </table>
    @endif

    {{-- TABLE 4: Leveres om mer en 14 dager --}}
    <h2 style="margin-top:18px;">Leveres om > 14 dager</h2>
    @if($leveres15plus->isEmpty())
    <p>Ingen leveranser i mer en 14dager.</p>
    @else
    <table class="table">
    <thead>
        <tr>
        <th>Pr.nr.</th>
        <th>Kunde</th>
        <th>Adresse</th>
        <th>Mann</th>
        <th>Avstand fra GG</th>
        <th>Gjenstår</th>
        </tr>
    </thead>
    <tbody>
        @foreach($leveres15plus as $p)
        @php $left = now()->startOfDay()->diffInDays($p->delivery_date, false); @endphp
        <tr class="{{ $p->has_open_avvik ? 'row-avvik' : '' }}">
        <td>{{ $p->external_number }}</td>
        <td>{{ $p->customer_name }}</td>
        <td>{{ $p->address }}</td>
        <td>{{ $p->mann ?? '–' }}</td>
        <td>@if (is_null($p->distance_km)) — @else {{ number_format($p->distance_km, 1, ',', ' ') }} km @endif </td>
        <td class="muted">{{ $left.' d' }}</td>
        </tr>
        @endforeach
    </tbody>
    </table>
    @endif

    @include('partials.toast')

    
<script>
    (function(){
    console.log('mapItems count:', {{ isset($mapItems) ? count($mapItems) : 0 }});
    const origin = { lat: {{ (float)$originLat }}, lng: {{ (float)$originLng }} };
    const items  = @json($mapItems);

    const map = L.map('plan-map', { scrollWheelZoom: true });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    if (!Array.isArray(items) || items.length === 0) {
        const center = (origin.lat && origin.lng) ? [origin.lat, origin.lng] : [63.4305, 10.3951];
        map.setView(center, 9);
        return;
    }

    const colorMap = { green:'#6c733d', orange:'#ff5f00', 'orange-strong':'#ff5f00', gray:'#8c8c88',};
    const bounds = L.latLngBounds();

    items.forEach(it => {
        if (typeof it.lat !== 'number' || typeof it.lng !== 'number') return;
        const c = colorMap[it.color] || '#6c733d';
        const m = L.circleMarker([it.lat, it.lng], {
        radius: 7, color: c, fillColor: c,
        fillOpacity: it.color === 'orange-strong' ? 1 : 0.7,
        weight: it.color === 'orange-strong' ? 3 : 1.5
        });
        if (it.label) m.bindTooltip(it.label, {direction:'top'});
        m.addTo(map);
        bounds.extend([it.lat, it.lng]);
    });

    if (bounds.isValid()) map.fitBounds(bounds, { padding:[30,30], maxZoom:12 });
    else if (origin.lat && origin.lng) map.setView([origin.lat, origin.lng], 9);
    })();
</script>


</body>
</html>
