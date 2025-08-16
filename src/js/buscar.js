// src/js/buscar.js
(function () {
    // Ajusta si tu app vive en otra ruta
    const PREFIX = '/CanaryVanGit/AlisiosVan';

    // ---------- Helpers de imágenes ----------
    const IMG_PREFIX = PREFIX + '/src/';
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

    // ---------- Fechas: SIEMPRE local (nada de toISOString) ----------
    const ymdLocal = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    function parseYMDToLocalDate(s) {
        if (!s) return null;
        const [y, m, d] = s.split('-').map(Number);
        return new Date(y, m - 1, d); // medianoche local
    }

    // ---------- Locale común para Flatpickr ----------
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
            <button class="btn btn-primary btn-sm btn-reserve" data-id="${c.id}">Reserve</button>
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

        const url = new URL(PREFIX + '/api/availability.php', location.origin);
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

    // ---------- Modal + checkout ----------
    function hookReserveButtons() {
        const modal = new bootstrap.Modal(document.getElementById('reserveModal'));
        const rf_camper_id = document.getElementById('rf_camper_id');
        const rf_start     = document.getElementById('rf_start');
        const rf_end       = document.getElementById('rf_end');
        const rf_name      = document.getElementById('rf_name');
        const rf_email     = document.getElementById('rf_email');
        const rf_phone     = document.getElementById('rf_phone');
        const form         = document.getElementById('reserveForm');

        document.querySelectorAll('.btn-reserve').forEach(b => {
            b.addEventListener('click', () => {
                rf_camper_id.value = b.dataset.id;
                rf_start.value = start;
                rf_end.value   = end;
                rf_name.value  = '';
                rf_email.value = '';
                rf_phone.value = '';
                modal.show();
            });
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                camper_id: +rf_camper_id.value,
                start: rf_start.value,
                end: rf_end.value,
                name: rf_name.value.trim(),
                email: rf_email.value.trim(),
                phone: rf_phone.value.trim()
            };
            try {
                const res = await fetch(PREFIX + '/api/create-checkout.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.ok && data.url) {
                    location.href = data.url;
                } else {
                    alert(data.error || 'Could not start checkout.');
                }
            } catch (err) {
                console.error(err);
                alert('Network error.');
            }
        });
    }

    // ---------- Inicializar UI ----------
    updateRangeLabel();
    updateBackLinkHref();

    // Calendario (idéntico criterio que en landing)
    flatpickr(dateInput, {
        mode: 'range',
        dateFormat: 'Y-m-d',   // formato máquina
        altInput: true,
        altFormat: 'j M Y',    // formato humano
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

    // Primera carga
    loadAvailability();
})();
