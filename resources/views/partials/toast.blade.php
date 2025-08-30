<!doctype html>
<html lang="no">
    <head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    </head>

<div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="true"></div>

<script>
    (function () {
    const data = {
        status: @json(session('status')),
        errors: @json($errors->all()),
    };
    const stack = document.getElementById('toast-stack');

    function showToast(msg, type='success', timeout=3000){
        if(!msg) return;
        const el = document.createElement('div');
        el.className = 'toast ' + (type === 'error' ? 'error' : 'success');
        el.innerHTML = `<button class="close" aria-label="Lukk" title="Lukk">&times;</button><div>${msg}</div>`;
        stack.appendChild(el);
        const close = () => { el.style.animation = 'toast-out .18s ease-in forwards'; setTimeout(()=>el.remove(), 180); };
        el.querySelector('.close').addEventListener('click', close);
        if(timeout) setTimeout(close, timeout);

    }

    if (data.status) showToast(data.status, 'success', 30000);
    if (Array.isArray(data.errors)) data.errors.forEach(e => showToast(e, 'error', 12000));
    })();
</script>
