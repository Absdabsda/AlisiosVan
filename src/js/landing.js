// src/js/landing.js
document.addEventListener('DOMContentLoaded', () => {
    /* ===========================
       1) Carruseles (Swiper)
       =========================== */
    const hero = document.querySelector('.swiper-container');
    if (hero) {
        new Swiper(hero, {
            spaceBetween: 30,
            effect: 'fade',
            loop: true,
            autoplay: {delay: 3000, disableOnInteraction: false},
            navigation: {nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev'}
        });
    }

    new Swiper('.testimonials-swiper', {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        pagination: {el: '.swiper-pagination', clickable: true},
        navigation: {nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev'},
        breakpoints: {768: {slidesPerView: 2}, 1024: {slidesPerView: 3}}
    });

    /* ===========================
       2) Helpers
       =========================== */
    const toYMD = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    /* ===========================
       3) Flatpickr
       =========================== */
    const dateInput = document.getElementById('date-range') || document.getElementById('dateRange');
    const fp = window.__fp || dateInput?._flatpickr || null;
    if (!fp) {
        console.warn('Flatpickr no está inicializado. Revisa js/datepicker-init.js y el orden de <script>.');
    }

    /* ===========================
       4) Submit del buscador
       =========================== */
    const form = document.querySelector('.search-form');
    form?.addEventListener('submit', (e) => {
        e.preventDefault();

        const dates = fp?.selectedDates ?? [];
        if (dates.length !== 2) {
            alert('Elige un rango de fechas');
            dateInput?.focus({preventScroll: true});
            fp?.open();
            return;
        }

        const start = toYMD(dates[0]);
        const end = toYMD(dates[1]);

        // Guardar última búsqueda en localStorage
        if (window.SearchState) {
            window.SearchState.saveLastSearch({from: start, to: end, series: ''});
        }

        const url = new URL('buscar.php', location.href);
        url.searchParams.set('start', start);
        url.searchParams.set('end', end);
        location.href = url.toString();
    });

    /* ===========================
       5) CTA "Book Now"
       =========================== */
    const cta = document.getElementById('ctaBook');

    function scrollToEl(el, offset = 0) {
        const y = el.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({top: y, behavior: 'smooth'});
        return new Promise(resolve => {
            const MAX_WAIT = 1200, start = performance.now();
            const step = (now) => {
                const reached = Math.abs(window.pageYOffset - y) < 2;
                const timeout = (now - start) > MAX_WAIT;
                if (reached || timeout) return resolve();
                requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        });
    }

    cta?.addEventListener('click', async (e) => {
        e.preventDefault();
        const target = document.getElementById('searchForm') || dateInput;

        const header = document.querySelector('header');
        const offset = header ? header.offsetHeight + 12 : 12;

        await scrollToEl(target, offset);
        dateInput?.focus({preventScroll: true});
        fp?.open();
    });

    /* ===========================
       6) Banner: reserva pendiente
       =========================== */
    try {
        const data = JSON.parse(localStorage.getItem('av:lastPending') || 'null');
        if (data && data.rid) {
            const banner = document.getElementById('pendingBanner');
            const a = document.getElementById('btnResumePay');
            const dismiss = document.getElementById('btnDismissPending');

            a.href = 'src/checkout/retry.php?rid=' + encodeURIComponent(data.rid) +
                '&t=' + encodeURIComponent(data.token || '');

            banner.style.display = 'block';
            dismiss.addEventListener('click', () => {
                banner.style.display = 'none';
            });
        }
    } catch {
    }

    /* ===========================
   7) Banner: última búsqueda
   =========================== */
    const search = window.SearchState?.getLastSearch?.();
    if (search) {
        const banner = document.getElementById('searchBanner');
        const text = document.getElementById('searchText');
        const btn = document.getElementById('btnResumeSearch');
        const dismiss = document.getElementById('btnDismissSearch');

        // Plantilla traducida que viene desde el HTML (PHP) en data-tpl
        const tpl = banner?.dataset?.tpl || 'Do you want to continue your search from %s to %s?';

        // Inserta fechas en los %s (orden: from, to)
        text.textContent = tpl.replace('%s', search.from).replace('%s', search.to);

        // Link directo a buscar.php
        let url = 'buscar.php?start=' + encodeURIComponent(search.from) +
            '&end=' + encodeURIComponent(search.to);
        if (search.series) url += '&series=' + encodeURIComponent(search.series);
        btn.href = url;

        // Mostrar y manejar cierre
        banner.style.display = 'block';
        dismiss.addEventListener('click', () => {
            banner.style.display = 'none';
        });
    }
});
