// src/js/ficha-camper.js
document.addEventListener('DOMContentLoaded', () => {
    // ===== Rutas base =====
    // p.ej. /CanaryVanGit/AlisiosVan/src/ficha-camper.php
    const PATH = location.pathname;

    // ROOT = parte antes de /src (donde viven /api y /src al mismo nivel)
    // BASE = carpeta /src para enlazar a otras páginas dentro de src
    let ROOT = '';
    let BASE = '';
    if (PATH.includes('/src/')) {
        ROOT = PATH.split('/src/')[0];  // -> /CanaryVanGit/AlisiosVan
        BASE = ROOT + '/src';           // -> /CanaryVanGit/AlisiosVan/src
    } else {
        // Si en producción sirves sin /src en la URL, ROOT = dominio y BASE = raíz
        ROOT = '';
        BASE = '';
    }

    // URL helper para API correcto (al mismo nivel que /src)
    const API = (file) => new URL(`${ROOT}/api/${file}`, location.origin).toString();

    // ===== Parámetros de la URL =====
    const qs    = new URLSearchParams(location.search);
    const from  = qs.get('from')  || '';        // "buscar" si vienes de buscar.php
    const start = qs.get('start') || '';
    const end   = qs.get('end')   || '';

    // ===== Elementos =====
    const btnReserve = document.getElementById('btnReserve');
    const btnBack    = document.getElementById('btnBack');
    const camperId   = Number(btnReserve?.dataset.id || 0);

    // ===== Back inteligente =====
    if (btnBack) {
        if (from === 'buscar') {
            const u = new URL(`${BASE}/buscar.php`, location.origin);
            if (start && end) { u.searchParams.set('start', start); u.searchParams.set('end', end); }
            btnBack.href = u.toString();
        } else {
            btnBack.href = `${BASE}/campers.php`;
        }
    }

    // ===== Overlay “redirigiendo” =====
    function ensureOverlay() {
        let el = document.getElementById('checkoutOverlay');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'checkoutOverlay';
        el.style.cssText = 'position:fixed;inset:0;display:none;place-items:center;background:rgba(255,255,255,.9);z-index:2000;';
        el.innerHTML = `
      <div style="background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:12px;padding:18px 22px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.12)">
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
        // Si no hay fechas, llévalo a la home con el buscador
        if (!start || !end) {
            const u = new URL(`${BASE}/index.php`, location.origin);
            u.searchParams.set('openDates', '1');  // para que aterrices con el calendario abierto (si lo usas)
            u.hash = 'searchForm';
            location.href = u.toString();
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
                location.href = data.url;   // a Stripe
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
