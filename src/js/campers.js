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

    // --- Navegación a la ficha ---
    // Arrastra fechas si las hay
    const qs = new URLSearchParams(location.search);
    const start = qs.get('start') || '';
    const end   = qs.get('end')   || '';

    function buildDetailsHref(id) {
        const params = new URLSearchParams({ id: String(id) });
        if (start && end) { params.set('start', start); params.set('end', end); }
        // relativo al directorio actual (funciona en /.../campers.php -> /.../camper.php)
        return `ficha-camper.php?${params.toString()}`;
    }

    // Haz cada card clicable + accesible
    cards.forEach(card => {
        const id = Number(card.dataset.id) || 0;
        if (!id) return; // asegúrate de tener data-id en el HTML

        const href = buildDetailsHref(id);

        card.style.cursor = 'pointer';
        card.setAttribute('tabindex', '0');
        if (card.dataset.name) {
            card.setAttribute('aria-label', `View ${card.dataset.name} details`);
        }

        // Clic
        card.addEventListener('click', (e) => {
            // Evita que algún enlace interno (si lo hubiera) duplique navegación
            if (e.target.closest('a')) return;
            location.href = href;
        });

        // Teclado
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                location.href = href;
            }
        });
    });
});
