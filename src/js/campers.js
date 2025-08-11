// src/js/campers.js
document.addEventListener('DOMContentLoaded', () => {
    const chips = document.querySelectorAll('.model-chip');
    const cols  = document.querySelectorAll('.camper-col'); // contenedores de columnas
    const cards = document.querySelectorAll('.camper-card');

    const apply = (series) => {
        let visible = 0;
        cards.forEach(card => {
            const s = (card.dataset.series || '').toUpperCase();
            const show = !series || s === series.toUpperCase();
            const col = card.closest('.camper-col');
            if (col) col.classList.toggle('d-none', !show);
            if (show) visible++;
        });
    };

    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            apply(chip.dataset.series || '');
        });
    });

    // primera pasada (All)
    apply('');
});

document.addEventListener('DOMContentLoaded', async () => {
    const params = new URLSearchParams(location.search);
    const start = params.get('start');
    const end   = params.get('end');
    const results = document.getElementById('results');

    if (!start || !end) {
        results.innerHTML = `<p>Please pick your dates on the home page.</p>`;
        return;
    }

    try {
        const url = `../api/availability.php?start=${start}&end=${end}`;
        const res = await fetch(url);
        const data = await res.json();

        if (!data.ok) throw new Error(data.error || 'Error');

        if (data.count === 0) {
            results.innerHTML = `<p>No campers available for those dates.</p>`;
            return;
        }

        // pinta tarjetas
        results.innerHTML = data.campers.map(c => `
      <div class="col-md-4">
        <div class="camper-card">
          <img src="img/${c.image || 'carousel/t3-azul-mar.webp'}" alt="${c.name}">
          <div class="camper-info">
            <h3>${c.name}</h3>
            <p>${c.series} · ${Number(c.price_per_night).toFixed(2)}€ / night</p>
            <button class="btn btn-primary btn-reserve" 
                    data-id="${c.id}" data-name="${c.name}">
              Reserve
            </button>
          </div>
        </div>
      </div>
    `).join('');

        // manejar “Reserve” -> crear checkout
        results.querySelectorAll('.btn-reserve').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    const payload = {
                        camper_id: Number(btn.dataset.id),
                        start, end,
                        // de momento mete datos dummy; luego los pedimos en un form
                        name: 'Guest',
                        email: 'demo@example.com',
                        phone: ''
                    };

                    const r = await fetch('../api/create-checkout.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const raw = await r.text();
                    let d; try { d = JSON.parse(raw) } catch(e){ throw new Error(raw); }

                    if (d.ok && d.url) location.href = d.url;
                    else alert(d.error || 'No se pudo iniciar el pago.');
                } catch (err) {
                    console.error(err);
                    alert('Error al iniciar el pago');
                }
            });
        });

    } catch (err) {
        console.error(err);
        results.innerHTML = `<p>Couldn’t load availability.</p>`;
    }
});


