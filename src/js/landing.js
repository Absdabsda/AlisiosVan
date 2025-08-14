// src/js/landing.js
document.addEventListener('DOMContentLoaded', () => {
    // Swipers (igual que tenías)
    const hero = document.querySelector('.swiper-container');
    if (hero) {
        new Swiper(hero, {
            spaceBetween: 30, effect: 'fade', loop: true,
            autoplay: { delay: 3000, disableOnInteraction: false },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
        });
    }
    new Swiper('.testimonials-swiper', {
        slidesPerView: 1, spaceBetween: 20, loop: true,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        breakpoints: { 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
    });

    // Locale común para todos
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

    // Calendario (máquina Y-m-d, humano con alt)
    const fp = flatpickr('#date-range', {
        mode: 'range',
        minDate: 'today',
        dateFormat: 'Y-m-d',    // <-- máquina
        altInput: true,
        altFormat: 'j M Y',     // <-- humano
        showMonths: 2,
        locale: localeEN,
    });

    // Helper: Y-m-d en LOCAL (sin UTC)
    const toYMD = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth()+1).padStart(2,'0');
        const day = String(d.getDate()).padStart(2,'0');
        return `${y}-${m}-${day}`;
    };

    // Enviar a buscar.php
    const form = document.querySelector('.search-form');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const dates = fp.selectedDates || [];
        if (dates.length !== 2) { alert('Elige un rango de fechas'); return; }
        const start = toYMD(dates[0]);
        const end   = toYMD(dates[1]);
        location.href = `buscar.php?start=${start}&end=${end}`;
    });

    // CTA abre el calendario
    document.getElementById('ctaBook')?.addEventListener('click', () => {
        document.getElementById('date-range')?.scrollIntoView({behavior:'smooth'});
        fp.open();
    });
});
