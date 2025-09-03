    <!doctype html>
    <html lang="no">
    <head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    </head>
    
    <div id="avvik-modal" class="modal" aria-hidden="true">
    <div class="modal__backdrop js-avvik-close"></div>

    <div class="modal__card" role="dialog" aria-modal="true" aria-labelledby="avvik-title">
        <form id="avvik-form" method="POST" action="{{ route('avvik.store') }}">
        @csrf
        <input type="hidden" name="project_id" id="avvik-project-id">
        <input type="hidden" name="source" id="avvik-source"> {{-- henting | montering --}}

        <h2 id="avvik-title" class="modal__title">
        Avvik <span id="avvik-orderkey">–</span>
        </h2>

        <div class="modal__meta">
            <div><strong>Tittel:</strong> <span id="avvik-project-title">–</span></div>
            <div><strong>Kunde:</strong> <span id="avvik-customer">–</span></div>
            <div><strong>Adresse:</strong> <span id="avvik-address">–</span></div>
            <div><strong>Ansvarlig:</strong> <span id="avvik-supervisor">–</span></div>
            <div>
            <strong>Kilde:</strong> <span id="avvik-source-label">–</span>
            &nbsp;•&nbsp;<strong>Tildelt dato:</strong> <span id="avvik-assigned">–</span>
            </div>
        </div>

        <div class="modal__fields">
            <label class="modal__label">Avviksgrunn</label>
            <input type="text" name="type" class="form-input" placeholder="f.eks. mangler, skade, feil mål" required>

            <label class="modal__label" style="margin-top:8px;">Beskrivelse</label>
            <textarea name="note" class="form-textarea" rows="4"
                    placeholder="Beskriv hva som gikk galt og hva som må gjøres videre" required></textarea>
        </div>

        <div class="modal__actions">
            <button type="submit" class="btn btn-warning">Lagre</button>
            <button type="button" class="btn btn-danger js-avvik-close">Avbryt</button>
        </div>
        </form>
    </div>
    </div>
