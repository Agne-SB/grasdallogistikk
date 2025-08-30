<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Prosjekter</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
</head>
<body>
    @include('partials.navbar')
    
    <h1>Prosjekter</h1>

    <form method="get" class="searchbar">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Søk: Prosjekt nummer, tittel, ansvarlig..."
            class="search-input"
            autofocus>
        <button class="btn btn-primary">Søk</button>
        <a href="{{ url()->current() }}" class="btn btn-danger">Nullstill</a>
    </form>


    @if($projects->isEmpty())
        <p>Ingen prosjekter funnet.</p>
    @else
        <table class="table">
        <thead>
            <tr>
                <th>Pr.nr.</th>
                <th>Tittel</th>
                <th>Ansvarlig</th>
                <th>Varenotat</th>
                <th>Leveringsdato</th>
                <th>Handling</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($projects as $p)
            <tr>
                <td>
                    {{ $p->external_number ?? '–' }}
                    @if(is_null($p->external_number) && $p->external_id)
                        <div class="muted">id: {{ $p->external_id }}</div>
                    @endif
                </td>
                <td>{{ $p->title }}</td>
                <td>{{ $p->supervisor_name ?? '–' }}</td>

                <td>
                <div class="textarea-wrap">
                    <textarea
                    name="goods_note"
                    form="f-{{ $p->id }}"
                    rows="2"
                    class="form-textarea js-row-field"
                    placeholder="Varenotat…">{{ old('goods_note', $p->goods_note) }}</textarea>
                </div>
                </td>

                <td>
                <input
                    type="date"
                    name="delivery_date"
                    form="f-{{ $p->id }}"
                    class="form-date js-row-field"
                    value="{{ old('delivery_date', optional($p->delivery_date)->format('Y-m-d')) }}">
                </td>

                <td>
                <div class="row-actions" id="ra-{{ $p->id }}">
                    {{-- SAVE area (shown only when row has unsaved edits) --}}
                    <div class="save-area">
                    <form id="f-{{ $p->id }}" method="POST" action="{{ route('projects.update', $p->id) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-primary" onclick="this.disabled=true;this.form.submit();">Lagre</button>
                    </form>
                    <div class="muted">Lagre først for å velge HO/MP</div>
                    </div>

                    {{-- ROUTE area (shown when clean/saved) --}}
                    <div class="route-area">
                    <form method="POST" action="{{ route('projects.moveBucket', $p) }}">
                        @csrf @method('PATCH')
                        <button type="submit" name="bucket" value="henting"   class="btn btn-warning">HO</button>
                        <button type="submit" name="bucket" value="montering" class="btn btn-success">MP</button>
                        @if(session('saved_project_id') == $p->id)
                        <span class="saved-badge">Lagret ✓</span>
                        @endif
                    </form>
                    </div>
                </div>
                </td>

            </tr>
            @endforeach
        </tbody>
        </table>

        <div class="pager">
        {{ $projects->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
    function checkTa(ta) {
        // if scrollHeight is bigger than the visible height, there is hidden text
        const wrap = ta.closest('.textarea-wrap');
        if (!wrap) return;
        const hasMore = ta.scrollHeight > ta.clientHeight + 1;
        wrap.classList.toggle('has-overflow', hasMore);
    }
    const tas = document.querySelectorAll('.js-check-overflow');
    tas.forEach(ta => {
        checkTa(ta);
        ['input','keyup','change'].forEach(ev => ta.addEventListener(ev, () => checkTa(ta)));
    });
    window.addEventListener('resize', () => tas.forEach(checkTa));
    });

    document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('tr').forEach(tr => {
        const actions = tr.querySelector('.row-actions');
        if (!actions) return;

        const fields = tr.querySelectorAll('.js-row-field');
        // Remember initial values
        fields.forEach(el => el.dataset.initial = (el.value ?? ''));

        const refresh = () => {
        const dirty = Array.from(fields).some(el => (el.value ?? '') !== el.dataset.initial);
        actions.classList.toggle('row-dirty', dirty);
        };

        fields.forEach(el => {
        el.addEventListener('input', refresh);
        el.addEventListener('change', refresh);
        });

        refresh(); // set initial state on page load
    });

    // Optional: auto-hide the "Lagret ✓" badge after 3s
    setTimeout(() => {
        document.querySelectorAll('.saved-badge').forEach(el => el.remove());
    }, 3000);
    });
</script>

    @include('partials.toast')
</body>
</html>
