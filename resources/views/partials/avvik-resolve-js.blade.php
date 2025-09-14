    <script>
    document.addEventListener('DOMContentLoaded', function(){
    const modal = document.getElementById('avvik-resolve-modal');
    const form  = document.getElementById('avvik-resolve-form');
    if (!modal || !form) return;

    const closeBtns = modal.querySelectorAll('.js-resolve-close');
    const F = {
        devId:   document.getElementById('resolve-deviation-id'),
        order:   document.getElementById('resolve-orderkey'),
        title:   document.getElementById('resolve-title-text'),
        customer:document.getElementById('resolve-customer'),
        address: document.getElementById('resolve-address'),
        supervisor: document.getElementById('resolve-supervisor'),
        source:  document.getElementById('resolve-source'),
        goods:   modal.querySelector('textarea[name="goods_note"]'),
        date:    modal.querySelector('input[name="delivery_date"]'),
        destHO:  modal.querySelector('input[name="destination"][value="henting"]'),
        destMO:  modal.querySelector('input[name="destination"][value="montering"]'),
        destLA:  modal.querySelector('input[name="destination"][value="varer"]'),
    };

    function openResolve(btn){
        const id   = btn.dataset.deviationId;
        const act  = form.getAttribute('action').replace('__ID__', id);
        form.setAttribute('action', act);

        F.devId.value = id;
        F.order.textContent = btn.dataset.orderkey || '–';
        F.title.textContent = btn.dataset.title || '–';
        F.customer.textContent = btn.dataset.customer || '–';
        F.address.textContent  = btn.dataset.address || '–';
        F.supervisor.textContent = btn.dataset.supervisor || '–';
        F.source.textContent   = (btn.dataset.source === 'henting') ? 'HO' : 'MO';

        F.goods.value = btn.dataset.goodsNote || '';
        F.date.value  = btn.dataset.deliveryDate || '';

        const suggest = btn.dataset.suggestDest || btn.dataset.source || '';
        F.destHO.checked = (suggest === 'henting');
        F.destMO.checked = (suggest === 'montering');
        if (F.destLA) F.destLA.checked = (suggest === 'varer');

        modal.classList.add('is-open'); document.body.classList.add('modal-open');
        (F.goods.value ? F.date : F.goods).focus();
    }

    function closeResolve(){
        modal.classList.remove('is-open'); document.body.classList.remove('modal-open');
        // Reset the form action back to placeholder so we don't stack IDs
        form.setAttribute('action', form.getAttribute('action').replace(/\/\d+$/, '/__ID__'));
        form.reset();
    }

    document.addEventListener('click', function(e){
        const btn = e.target.closest('.js-open-resolve');
        if (!btn) return;
        e.preventDefault(); e.stopPropagation();
        openResolve(btn);
    });

    closeBtns.forEach(b => b.addEventListener('click', closeResolve));
    modal.addEventListener('click', function(e){ if (e.target === modal) closeResolve(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && modal.classList.contains('is-open')) closeResolve(); });
    });
    </script>
