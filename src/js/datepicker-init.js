// js/datepicker-init.js
(function () {
    const input = document.getElementById('date-range') || document.getElementById('dateRange');
    if (!window.flatpickr || !input) return;

    const raw = (window.APP_LANG || 'en').toLowerCase();
    const key = raw.split('-')[0];
    // Usa la tabla de locales de flatpickr si está; si no, default
    const l10nTable = (window.flatpickr && window.flatpickr.l10ns) || {};
    const locale = l10nTable[key] || l10nTable[raw] || l10nTable.default || {};

    const isMobile = () => window.matchMedia('(max-width: 576px)').matches;

    const fp = flatpickr(input, {
        mode: 'range',
        minDate: 'today',
        dateFormat: 'Y-m-d',     // para URL/backend
        altInput: true,
        altFormat: key === 'es' ? "j \\de F Y" : 'j M Y', // visible
        disableMobile: true,
        showMonths: isMobile() ? 1 : 2,
        static: isMobile(),
        appendTo: isMobile() ? undefined : document.body,
        position: 'auto',
        locale,
        onOpen(_, __, inst){ if (!isMobile()) inst.calendarContainer.style.zIndex = '10010'; }
    });

    // Deja la instancia accesible para landing.js
    window.__fp = fp;

    // Ajusta meses/anchaje al cambiar viewport
    window.addEventListener('resize', () => {
        const mob = isMobile();
        fp.set('showMonths', mob ? 1 : 2);
        fp.set('static', mob);
        fp.set('appendTo', mob ? undefined : document.body);
    });
})();
