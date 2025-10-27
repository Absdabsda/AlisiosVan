// src/js/campers.js
document.addEventListener('DOMContentLoaded', () => {
    // --- Filtros por modelo (como tenías) ---
    const chips = document.querySelectorAll('.model-chip');
    const cards = document.querySelectorAll('.camper-card');

    function apply(series) {
        cards.forEach(card => {
            const s = (card.dataset.series || '').toUpperCase();
            const show = !series || s === series.toUpperCase();
            const col = card.closest('.camper-col');
            if (col) col.classList.toggle('d-none', !show);
        });
    }

    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            apply(chip.dataset.series || '');
        });
    });

    // Primera pasada (All)
    apply('');

    // --- Navegación a la ficha (rutas bonitas) ---
    const qs = new URLSearchParams(location.search);
    const start = qs.get('start') || '';
    const end   = qs.get('end')   || '';
    const lang  = (document.documentElement.lang || 'es').split('-')[0];

// Mapea ID → slug canónico (mantén esto sincronizado con tu camper-detail/router)
    const slugById = { 1: 'matcha', 2: 'skye', 3: 'rusty' };

    function buildDetailsHref(id) {
        const slug = slugById[id] || String(id);
        const base = `/${encodeURIComponent(lang)}/camper/${encodeURIComponent(slug)}/`;
        const url  = new URL(base, location.origin);
        if (start && end) {
            url.searchParams.set('start', start);
            url.searchParams.set('end', end);
        }
        // Devolvemos path + query (sin origin) para que funcione igual en localhost/hostinger
        return url.pathname + (url.search || '');
    }

// Haz cada card clicable solo si NO hay <a> envolviendo (si hay <a>, delega en ese href)
    cards.forEach(card => {
        const id = Number(card.dataset.id) || 0;
        if (!id) return;

        const href = buildDetailsHref(id);

        // Si ya la has envuelto en <a class="camper-card"> en el PHP, solo ajusta el href y listo
        if (card.tagName === 'A') {
            card.setAttribute('href', href);
            return;
        }
        const anchorInside = card.querySelector('a');
        if (anchorInside) {
            anchorInside.setAttribute('href', href);
            return;
        }

        // Si NO hay <a>, hacemos la card clicable/accessible por teclado
        card.style.cursor = 'pointer';
        card.setAttribute('tabindex', '0');
        if (card.dataset.name) {
            card.setAttribute('aria-label', `View ${card.dataset.name} details`);
        }

        card.addEventListener('click', (e) => {
            if (e.target.closest('a')) return; // por si algún hijo es <a>
            location.assign(href);
        });

        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                location.assign(href);
            }
        });
    });

});
