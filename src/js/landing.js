// src/js/landing.js
document.addEventListener('DOMContentLoaded', () => {

    // Swiper testimonios
    new Swiper('.testimonials-swiper', {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        breakpoints: { 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
    });

    // Datepicker
    const fp = flatpickr('#date-range', {
        mode: 'range',
        minDate: 'today',
        dateFormat: 'd/m/Y',
        showMonths: 2,
        locale: {
            firstDayOfWeek: 1,
            weekdays: {
                shorthand: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                longhand:  ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
            },
            months: {
                shorthand: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                longhand:  ['January','February','March','April','May','June','July','August','September','October','November','December'],
            },
        },
    });

    // Solo REDIRIGE con las fechas a buscar.php
    const form = document.querySelector('.search-form');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const dates = fp.selectedDates || [];
        if (dates.length !== 2) { alert('Elige un rango de fechas'); return; }
        const toYMD = d => d.toISOString().slice(0,10);
        const start = toYMD(dates[0]);
        const end   = toYMD(dates[1]);
        location.href = `buscar.php?start=${start}&end=${end}`;
    });
});

const cta = document.getElementById('ctaBook');
if (cta) {
    cta.addEventListener('click', () => {
        document.getElementById('date-range')?.scrollIntoView({behavior:'smooth'});
        // Abre el calendario
        try { fp.open(); } catch {}
    });
}
