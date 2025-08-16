// src/js/buscar.js
(function () {
    // Detecta la raíz del proyecto según estés en /src/...
    // Ej: en local => "/CanaryVanGit/AlisiosVan", en prod => ""
    const ROOT = location.pathname.includes('/src/')
        ? location.pathname.split('/src/')[0]
        : '';

    // Helpers para construir URLs
    const apiUrl = (file) => new URL(`${ROOT}/api/${file}`, location.origin).toString();
    const IMG_PREFIX = `${ROOT}/src/`;

    // ---------- Helpers de imágenes ----------
    function resolveImage(path, fallback) {
        const p = (path || '').trim();
        if (!p) return IMG_PREFIX + (fallback || 'img/carousel/t3-azul-mar.webp');
        if (/^https?:\/\//i.test(p) || p.startsWith('/')) return p;
        return IMG_PREFIX + p.replace(/^\.?\//, '');
    }
    const IMAGE_BY_ID = {
        1: 'img/carousel/matcha-surf.34.32.jpeg',
        2: 'img/carousel/t3-azul-playa.webp',
        3: 'img/carousel/t4-sol.webp'
    };
    function guessImageFromName(name) {
        const n = (name || '').toLowerCase();
        if (n.includes('matcha')) return 'img/carousel/t3-azul-mar.webp';
        if (n.includes('skye'))   return 'img/carousel/t3-azul-playa.webp';
        if (n.includes('rusty'))  return 'img/carousel/t4-sol.webp';
        return 'img/carousel/t3-azul-mar.webp';
    }

    // ---------- Estado inicial ----------
    const qs = new URLSearchParams(location.search);
    let start = qs.get('start') || '';
    let end   = qs.get('end')   || '';
    let seriesFilter = '';

    // ---------- Elementos del DOM ----------
    const resultsEl  = document.getElementById('results');
    const emptyMsg   = document.getElementById('emptyMsg');
    const rangeLabel = document.getElementById('rangeLabel');
    const dateInput  = document.getElementById('dateRange');
    const backLink   = document.getElementById('backLink');

    // ---------- Overlay checkout ----------
    function showCheckoutOverlay(msg){
        const el = document.getElementById('checkoutOverlay');
        if (!el) return; // si no existe en el HTML, simplemente no mostramos overlay
        const p = el.querySelector('p');
        if (p && msg) p.textContent = msg;
        el.classList.add('show');
        el.removeAttribute('hidden');
    }
    function hideCheckoutOverlay(){
        const el = document.getElementById('checkoutOverlay');
        if (el) el.classList.remove('show');
    }

    // ---------- Fechas: SIEMPRE local ----------
    const ymdLocal = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    function parseYMDToLocalDate(s) {
        if (!s) return null;
        const [y, m, d] = s.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    // ---------- Locale común para Flatpickr (arreglado Oct/Nov) ----------
    const localeEN = {
        firstDayOfWeek: 1,
        weekdays: {
            shorthand: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
            longhand:  ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
        },
        months: {
            shorthand: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            longhand:  ['January','February','March','April','May','June','July','August','September','October','November','December'],
        },
    };

    // ---------- UI helpers ----------
    function updateRangeLabel() {
        if (!rangeLabel) return;
        rangeLabel.textContent = (start && end) ? `From ${start} to ${end}` : '';
    }
    function updateQueryString() {
        const url = new URL(location.href);
        if (start && end) {
            url.searchParams.set('start', start);
            url.searchParams.set('end', end);
        } else {
            url.searchParams.delete('start');
            url.searchParams.delete('end');
        }
        history.replaceState(null, '', url);
    }
    function updateBackLinkHref() {
        if (!backLink) return;
        const url = new URL(backLink.href, location.origin);
        if (start && end) {
            url.searchParams.set('start', start);
            url.searchParams.set('end', end);
        } else {
            url.searchParams.delete('start');
            url.searchParams.delete('end');
        }
        backLink.href = url.toString();
    }

    // ---------- Render de cards ----------
    function render(campers) {
        resultsEl.innerHTML = '';
        if (!campers.length) {
            emptyMsg.textContent = 'No campers available for these dates.';
            emptyMsg.style.display = '';
            return;
        }
        emptyMsg.style.display = 'none';

        campers.forEach(c => {
            const img = resolveImage(c.image || IMAGE_BY_ID[c.id] || guessImageFromName(c.name));
            const q = (start && end) ? `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}` : '';
            const detailsHref = `camper.php?id=${encodeURIComponent(c.id)}${q}`;

            const col = document.createElement('div');
            col.className = 'col-md-4 camper-col';
            col.innerHTML = `
        <div class="camper-card">
          <a href="${detailsHref}">
            <img src="${img}" alt="${c.name}" loading="lazy">
          </a>
          <div class="camper-info">
            <h3>"${c.name}"</h3>
            <p>${Number(c.price_per_night).toFixed(0)}€ per night.</p>
            <div class="d-flex align-items-center mt-2">
              <button class="btn btn-primary btn-sm js-reserve" data-id="${c.id}">Reserve</button>
              <a class="btn btn-outline-secondary btn-sm ms-auto" href="${detailsHref}">View camper</a>
            </div>
          </div>
        </div>
      `;
            resultsEl.appendChild(col);
        });

        hookReserveButtons();
    }

    // ---------- Cargar disponibilidad ----------
    async function loadAvailability() {
        resultsEl.innerHTML = '';
        emptyMsg.style.display = 'none';

        if (!start || !end) {
            emptyMsg.textContent = 'Missing dates.';
            emptyMsg.style.display = '';
            return;
        }

        const url = new URL(apiUrl('availability.php'));
        url.searchParams.set('start', start);
        url.searchParams.set('end', end);
        if (seriesFilter) url.searchParams.set('series', seriesFilter);

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Error');
            render(data.campers);
        } catch (e) {
            console.error(e);
            emptyMsg.textContent = "Couldn't load availability.";
            emptyMsg.style.display = '';
        }
    }

    // ---------- Filtro por modelo ----------
    document.querySelectorAll('.model-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.model-chip').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            seriesFilter = btn.dataset.series || '';
            loadAvailability();
        });
    });

    // ---------- Checkout directo (SIN modal) ----------
    function hookReserveButtons() {
        document.querySelectorAll('.js-reserve').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!start || !end) {
                    alert('Select the dates before reserving.');
                    return;
                }

                const camperId = Number(btn.dataset.id || 0);
                if (!camperId) return;

                const old = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = 'Redirecting...';

                // Muestra overlay (si está añadido en el HTML)
                showCheckoutOverlay('Redirecting you to a safe checkout…');

                try {
                    const res = await fetch(apiUrl('create-checkout.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ camper_id: camperId, start, end })
                    });
                    const data = await res.json();
                    if (data.ok && data.url) {
                        // dejamos el overlay visible mientras salta a Stripe
                        window.location.href = data.url;
                    } else {
                        hideCheckoutOverlay();
                        alert(data.error || 'Could not initialize checkout.');
                    }
                } catch (err) {
                    console.error(err);
                    hideCheckoutOverlay();
                    alert('Error de red.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = old;
                }
            });
        });
    }

    // ---------- Inicializar UI ----------
    updateRangeLabel();
    updateBackLinkHref();

    // Calendario
    if (window.flatpickr && dateInput) {
        flatpickr(dateInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j M Y',
            defaultDate: (start && end) ? [parseYMDToLocalDate(start), parseYMDToLocalDate(end)] : null,
            disableMobile: true,
            allowInput: false,
            clickOpens: true,
            showMonths: 2,
            locale: localeEN,
            onClose(selectedDates){
                if (selectedDates.length === 2) {
                    start = ymdLocal(selectedDates[0]);
                    end   = ymdLocal(selectedDates[1]);
                    updateRangeLabel();
                    updateQueryString();
                    updateBackLinkHref();
                    loadAvailability();
                }
            }
        });
    }

    // Primera carga
    loadAvailability();
})();
