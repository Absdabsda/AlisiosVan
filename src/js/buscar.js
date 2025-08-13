// src/js/buscar.js
(function () {
    const PREFIX = '/CanaryVanGit/AlisiosVan';

    const qs = new URLSearchParams(location.search);
    let start = qs.get('start') || '';
    let end   = qs.get('end')   || '';
    let seriesFilter = '';

    const resultsEl = document.getElementById('results');
    const emptyMsg  = document.getElementById('emptyMsg');
    const rangeLabel = document.getElementById('rangeLabel');

    // Etiqueta de fechas
    if (start && end) rangeLabel.textContent = `From ${start} to ${end}`;

    // Pinta cards
    function render(campers) {
        resultsEl.innerHTML = '';
        if (!campers.length) {
            emptyMsg.style.display = '';
            return;
        }
        emptyMsg.style.display = 'none';
        campers.forEach(c => {
            // Imagen (fallback)
            const img = c.image || 'img/carousel/t3-azul-mar.webp';
            const col = document.createElement('div');
            col.className = 'col-md-4';
            col.innerHTML = `
        <div class="camper-card h-100 d-flex flex-column">
          <img src="${img}" alt="${c.name}" loading="lazy">
          <div class="camper-info">
            <h3>${c.name}</h3>
            <p>${c.series} · ${Number(c.price_per_night).toFixed(2)}€ / night</p>
            <button class="btn btn-primary btn-sm btn-reserve" data-id="${c.id}">Reserve</button>
          </div>
        </div>
      `;
            resultsEl.appendChild(col);
        });
        hookReserveButtons();
    }

    // Carga disponibilidad
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

    // Filtro por modelo
    document.querySelectorAll('.series-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.series-chip').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            seriesFilter = btn.dataset.series || '';
            loadAvailability();
        });
    });

    // Modal + checkout
    function hookReserveButtons() {
        const modal = new bootstrap.Modal(document.getElementById('reserveModal'));
        const rf_camper_id = document.getElementById('rf_camper_id');
        const rf_start = document.getElementById('rf_start');
        const rf_end   = document.getElementById('rf_end');
        const rf_name  = document.getElementById('rf_name');
        const rf_email = document.getElementById('rf_email');
        const rf_phone = document.getElementById('rf_phone');
        const form     = document.getElementById('reserveForm');

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

    // Primera carga
    loadAvailability();
})();
