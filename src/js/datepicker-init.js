// js/datepicker-init.js
(function () {
    const lang = (window.APP_LANG || 'es').toLowerCase().split('-')[0];
    const l10nTable = (window.flatpickr && window.flatpickr.l10ns) || {};
    const locale = l10nTable[lang] || l10nTable.default || {};
    if (!window.flatpickr) return;

    // Soporta ambos IDs (index y buscar)
    const inputs = document.querySelectorAll('#dateRange, #date-range');
    if (!inputs.length) return;

    const mqDesktop = window.matchMedia('(min-width: 768px)');

    inputs.forEach((el) => {
        const wrap = el.closest('.date-chip') || el.parentElement;

        const fp = flatpickr(el, {
            mode: 'range',
            minDate: 'today',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: lang === 'es' ? "j \\de F Y" : 'j M Y',
            // Mismo widget en todos los dispositivos (evita saltos)
            disableMobile: true,
            // Siempre pegado al <body> para que nada lo recorte
            appendTo: document.body,
            position: 'auto center',
            // Solo cambiamos showMonths según breakpoint
            showMonths: mqDesktop.matches ? 2 : 1,
            locale,
            // Haz que el alt-input herede Bootstrap (+ nuestra clase)
            altInputClass: 'form-control flatpickr-alt-input',
            onReady() {
                if (wrap) wrap.classList.add('fp-ready');
            }
        });

        // Solo reajusta el número de meses; NO toques appendTo/static
        mqDesktop.addEventListener('change', (e) => {
            fp.set('showMonths', e.matches ? 2 : 1);
        });
    });
})();
