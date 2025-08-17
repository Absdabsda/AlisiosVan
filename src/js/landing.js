// src/js/landing.js
document.addEventListener('DOMContentLoaded', () => {

    /* ===========================
       1) Carruseles (Swiper)
       ---------------------------
       - El "hero" solo se inicializa si existe .swiper-container.
       - El de testimonios usa breakpoints para nº de slides.
       - Si cambias las clases de los botones/paginación,
         actualiza "navigation" y "pagination" aquí.
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
        breakpoints: {
            768:  { slidesPerView: 2 },
            1024: { slidesPerView: 3 }
        }
    });

    /* ===========================
       2) Locale común para Flatpickr
       ---------------------------
       - Si quieres otro idioma, cambia aquí.
       - firstDayOfWeek: 1 => lunes primero.
       =========================== */
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

    /* ===========================
       3) Calendario (Flatpickr)
       ---------------------------
       - appendTo: document.body => evita que lo "corten" contenedores con overflow/transform.
       - position: 'auto' => elige arriba/abajo según espacio.
       - disableMobile: fuerza el mismo UI también en iOS/Android.
       - showMonths: 2 por defecto; onReady lo ajusta según viewport.
       - onOpen: subimos z-index por si hay header fijo, chat, etc.
       * Si cambias el input (#date-range), actualiza el selector.
       =========================== */
    const fp = flatpickr('#date-range', {
        mode: 'range',
        minDate: 'today',
        dateFormat: 'Y-m-d',  // formato "máquina" (para backend/URL)
        altInput: true,
        altFormat: 'j M Y',   // formato "humano" (visible en el input)
        appendTo: document.body,
        position: 'auto',
        disableMobile: true,
        showMonths: 2,
        locale: localeEN,
        onOpen(_, __, inst){
            // Sube el calendario por encima de modales/headers/stickies
            inst.calendarContainer.style.zIndex = '10010';
        },
        onReady(_, __, inst){
            // Responsive: 1 mes en móvil, 2 en desktop
            const apply = () => {
                const small = window.matchMedia('(max-width: 576px)').matches;
                inst.set('showMonths', small ? 1 : 2);
            };
            apply();
            // Nota: si usas SSR o cambias el breakpoint, ajusta aquí también.
            window.addEventListener('resize', apply);
        }
    });

    /* ===========================
       4) Utilidad para formatear fechas (LOCAL)
       ---------------------------
       - No usa UTC: usa la zona local del navegador.
       - Si necesitas zona horaria fija, conviértelo en backend.
       =========================== */
    const toYMD = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth()+1).padStart(2,'0');
        const day = String(d.getDate()).padStart(2,'0');
        return `${y}-${m}-${day}`;
    };

    /* ===========================
       5) Envío del formulario de búsqueda
       ---------------------------
       - Valida que haya 2 fechas antes de redirigir.
       - Construye la URL /buscar.php?start=YYYY-MM-DD&end=YYYY-MM-DD
       - Si cambias la ruta (buscar.php), actualiza aquí.
       =========================== */
    const form = document.querySelector('.search-form');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const dates = fp.selectedDates || [];
        if (dates.length !== 2) {
            alert('Elige un rango de fechas');
            return;
        }
        const start = toYMD(dates[0]);
        const end   = toYMD(dates[1]);
        // Sugerencia: usa URLSearchParams si añades más filtros a futuro
        location.href = `buscar.php?start=${start}&end=${end}`;
    });

    /* ===========================
       6) CTA "Book Now" => scroll + abrir calendario
       ---------------------------
       SI CAMBIA EL HEADER:
       - Ajustar el offset.
       - Alternativa: CSS `scroll-margin-top` en #searchForm/.search-wrapper.
       =========================== */
    const cta = document.getElementById('ctaBook');
    const dateInput = document.getElementById('date-range');
    function scrollToEl(el, offset = 0) {
        // Calcula posición absoluta del elemento menos el offset
        const y = el.getBoundingClientRect().top + window.pageYOffset - offset;

        // Scroll suave al objetivo
        window.scrollTo({ top: y, behavior: 'smooth' });

        // Espera activa con requestAnimationFrame
        return new Promise(resolve => {
            const MAX_WAIT = 1200; // ms: ajustar si hay scroll muy largo
            const start = performance.now();

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

        // Calcula offset real del header (si es sticky/fixed)
        // Suma 12px para dejar un pequeño margen visual
        const header = document.querySelector('header');
        const offset = header ? header.offsetHeight + 12 : 12;

        // 1) Scroll hasta el formulario
        await scrollToEl(target, offset);

        // 2) Evita que el foco provoque otro scroll automático
        dateInput?.focus({ preventScroll: true });

        // 3) Abre el calendario. Si necesitas abrirlo siempre hacia abajo,
        //    podrías jugar con `position`/`static` del contenedor o con CSS del flatpickr.
        fp.open();
    });
});
