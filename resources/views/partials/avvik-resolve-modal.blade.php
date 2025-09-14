    <!doctype html>
    <html lang="no">
    <head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    </head>
    
    <div id="avvik-resolve-modal" class="modal" aria-hidden="true">
    <div class="modal__backdrop js-resolve-close"></div>

    <div class="modal__card" role="dialog" aria-modal="true" aria-labelledby="resolve-title">
        <form id="avvik-resolve-form" method="POST"
            action="{{ route('avvik.resolveRoute', ['deviation' => '__ID__']) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="deviation_id" id="resolve-deviation-id">

        <h2 id="resolve-title" class="modal__title">
            Løs avvik <span id="resolve-orderkey">–</span>
        </h2>

        <div class="modal__meta">
            <div><strong>Tittel:</strong> <span id="resolve-title-text">–</span></div>
            <div><strong>Kunde:</strong> <span id="resolve-customer">–</span></div>
            <div><strong>Adresse:</strong> <span id="resolve-address">–</span></div>
            <div><strong>Ansvarlig:</strong> <span id="resolve-supervisor">–</span></div>
            <div><strong>Kilde:</strong> <span id="resolve-source">–</span></div>
        </div>

        <div class="modal__fields">
            <label class="modal__label">Varenotat</label>
            <textarea name="goods_note" class="form-textarea" rows="3"
                    placeholder="Oppdater kort hva som gjelder varen(e)"></textarea>

            <label class="modal__label" style="margin-top:8px;">Ny leveringsdato</label>
            <input type="date" name="delivery_date" class="form-input" required>

            <label class="modal__label" style="margin-top:8px;">Send sak til</label>
            <div style="display:flex; gap:12px; align-items:center;">
            <label><input type="radio" name="destination" value="henting" required> Henting (HO)</label>
            <label><input type="radio" name="destination" value="montering" required> Montering (MO)</label>
            <label><input type="radio" name="destination" value="varer"> Varer (LA)</label>
            </div>

            <label class="modal__label" style="margin-top:8px;">Løsningsnotat (valgfritt)</label>
            <input type="text" name="resolution_note" class="form-input" placeholder="Hva ble gjort for å løse avviket?">
        </div>

        <div class="modal__actions">
            <button type="submit" class="btn btn-primary">Lagre og flytt</button>
            <button type="button" class="btn btn-danger js-resolve-close">Avbryt</button>
        </div>
        </form>
    </div>
    </div>
