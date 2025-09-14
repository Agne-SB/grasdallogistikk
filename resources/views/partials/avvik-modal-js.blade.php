    <script>
    document.addEventListener('DOMContentLoaded', function () {
    /* --------- (optional) require plassering before enabling "Klargjort" --------- */
    document.querySelectorAll('form[id^="prep-"]').forEach(function (f) {
        const input = f.querySelector('[name="staged_location"]');
        const btn   = f.querySelector('[data-action="mark-ready"], button[type="submit"], input[type="submit"]');
        if (!input || !btn) return;
        input.setAttribute('required', 'required');
        const toggle = () => { btn.disabled = !input.value.trim(); };
        input.addEventListener('input', toggle);
        input.addEventListener('change', toggle);
        toggle();
    });

    /* ---------------- Avvik modal: open, populate, close ---------------- */
    const modal = document.getElementById('avvik-modal');
    const form  = document.getElementById('avvik-form');
    if (!modal || !form) return;

    const closeBtns = modal.querySelectorAll('.js-avvik-close');
    const fields = {
        projectId:  document.getElementById('avvik-project-id'),
        stockId:    document.getElementById('avvik-stock-id'),
        source:     document.getElementById('avvik-source'),
        orderkey:   document.getElementById('avvik-orderkey'),
        title:      document.getElementById('avvik-project-title'),
        customer:   document.getElementById('avvik-customer'),
        address:    document.getElementById('avvik-address'),
        supervisor: document.getElementById('avvik-supervisor'),
        sourceLbl:  document.getElementById('avvik-source-label'),
        assigned:   document.getElementById('avvik-assigned'),
    };

    function openModal(data){
        // FIX: fields, not ffields
        fields.projectId.value = data.projectId || '';
        fields.stockId.value   = data.stockId || '';

        // if stock item is set, clear projectId to avoid sending both
        if (fields.stockId.value) fields.projectId.value = '';

        // normalize source to lowercase so the label map works
        const src = (data.source || '').toLowerCase();
        fields.source.value = src; // 'henting' | 'montering' | 'varer'

        fields.orderkey.textContent   = data.orderkey || '–';
        fields.title.textContent      = data.title || '–';
        fields.customer.textContent   = data.customer || '–';
        fields.address.textContent    = data.address || '–';
        fields.supervisor.textContent = data.supervisor || '–';

        const srcMap = { henting: 'HO', montering: 'MO', varer: 'LA' };
        fields.sourceLbl.textContent = srcMap[src] ?? (src || '–');

        fields.assigned.textContent = data.assigned || '–';

        modal.classList.add('is-open');
        document.body.classList.add('modal-open');

        const first = form.querySelector('input[name="type"]');
        if (first) first.focus();
    }

    function closeModal(){
        modal.classList.remove('is-open');
        document.body.classList.remove('modal-open');
        form.reset();
    }

    // open on any .js-open-avvik (button must be type="button")
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.js-open-avvik');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const row = btn.closest('tr');
        const get = (k) => btn.dataset[k] || row?.dataset[k] || '';

        openModal({
        projectId:  get('projectId'),
        stockId:    get('stockId'),
        source:     get('source') || (document.body.dataset.pageSource || ''), // fallback to page-level
        orderkey:   get('orderkey'),
        title:      get('title'),
        customer:   get('customer'),
        address:    get('address'),
        supervisor: get('supervisor'),
        assigned:   get('assigned'),
        });
    });

    // close handlers
    closeBtns.forEach(b => b.addEventListener('click', closeModal));
    modal.addEventListener('click', function(e){
        if (e.target === modal) closeModal(); // click backdrop
    });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
    });
    </script>
