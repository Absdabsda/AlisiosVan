// src/js/ficha-camper.js
document.addEventListener('DOMContentLoaded', () => {
    // ===== Rutas base (compatibilidad local/dev) =====
    const PATH = location.pathname;
    let ROOT = '';
    let BASE = '';
    if (PATH.includes('/src/')) {
        ROOT = PATH.split('/src/')[0];  // ej: /CanaryVanGit/AlisiosVan
        BASE = ROOT + '/src';           // ej: /CanaryVanGit/AlisiosVan/src
    } else {
        ROOT = '';
        BASE = '';
    }

    // Helper para API correcto (al mismo nivel que /src)
    const API = (file) => new URL(`${ROOT}/api/${file}`, location.origin).toString();

    // ===== Parámetros de la URL =====
    const qs    = new URLSearchParams(location.search);
    const from  = qs.get('from')  || '';
    const start = qs.get('start') || '';
    const end   = qs.get('end')   || '';

    // ===== Elementos =====
    const btnReserve = document.getElementById('btnReserve');
    const btnBack    = document.getElementById('btnBack');
    const camperId   = Number(btnReserve?.dataset.id || 0);

    // Idioma actual del <html lang="...">
    const lang = (document.documentElement.lang || 'es').split('-')[0];

    // ===== Back bonito por idioma =====
    if (btnBack) {
        let backUrl = `/${encodeURIComponent(lang)}/campers/`;
        if (from === 'buscar') {
            // /<lang>/buscar/ o /<lang>/buscar/YYYY-MM-DD/YYYY-MM-DD/
            backUrl = `/${encodeURIComponent(lang)}/buscar/`;
            if (start && end) backUrl += `${start}/${end}/`;
        }
        btnBack.href = backUrl;
    }

    // ===== Overlay “redirigiendo” =====
    function ensureOverlay() {
        let el = document.getElementById('checkoutOverlay');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'checkoutOverlay';
        el.style.cssText = `
      position:fixed;inset:0;display:none;place-items:center;
      background:rgba(255,255,255,.9);z-index:2000;
    `;
        el.innerHTML = `
      <div style="background:#fff;border:1px solid rgba(0,0,0,.06);
                  border-radius:12px;padding:18px 22px;text-align:center;
                  box-shadow:0 10px 30px rgba(0,0,0,.12)">
        <div class="spinner-border" role="status" aria-hidden="true"></div>
        <p class="mt-3 mb-0">Redirecting you to a safe checkout…</p>
        <div class="text-muted small">Don’t close this window.</div>
      </div>`;
        document.body.appendChild(el);
        return el;
    }

    function showOverlay(msg) {
        const el = ensureOverlay();
        if (msg) el.querySelector('p').textContent = msg;
        el.style.display = 'grid';
    }

    function hideOverlay() {
        const el = document.getElementById('checkoutOverlay');
        if (el) el.style.display = 'none';
    }

    // ===== Reservar =====
    btnReserve?.addEventListener('click', async () => {
        // Si no hay fechas → home bonita con el buscador abierto
        if (!start || !end) {
            const home = new URL(`/${encodeURIComponent(lang)}/`, location.origin);
            home.searchParams.set('openDates', '1');
            home.hash = 'searchForm';
            location.href = home.pathname + home.search + home.hash;
            return;
        }

        if (!camperId) {
            alert('Missing camper id.');
            return;
        }

        const old = btnReserve.innerHTML;
        btnReserve.disabled = true;
        btnReserve.innerHTML = 'Redirecting…';
        showOverlay('Redirecting you to a safe checkout…');

        try {
            const res = await fetch(API('create-checkout.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ camper_id: camperId, start, end })
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok && data.ok && data.url) {
                location.href = data.url;   // A Stripe
                return;
            }

            hideOverlay();
            alert(data.error || 'Could not initialize checkout.');
        } catch (err) {
            hideOverlay();
            alert('Network error. Please try again.');
        } finally {
            btnReserve.disabled = false;
            btnReserve.innerHTML = old;
        }
    });
});
