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
            autoplay: { delay: 3000, disableOnInteraction: false },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
        });
    }

    new Swiper('.testimonials-swiper', {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        breakpoints: { 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
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
       3) Flatpickr (reutiliza instancia)
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
            dateInput?.focus({ preventScroll: true });
            fp?.open();
            return;
        }

        const start = toYMD(dates[0]);
        const end   = toYMD(dates[1]);

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
        window.scrollTo({ top: y, behavior: 'smooth' });
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
        dateInput?.focus({ preventScroll: true });
        fp?.open();
    });
});
