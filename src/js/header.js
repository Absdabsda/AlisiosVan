// js/header.js
document.addEventListener('DOMContentLoaded', () => {
    const nav = document.querySelector('nav.navbar');
    if (!nav) return;
    const onScroll = () => nav.classList.toggle('is-sticky', window.scrollY > 8);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});

// Validación Bootstrap del modal
document.querySelectorAll('form.needs-validation').forEach(form => {
    form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});

// Cerrar el header (navbar colapsable) en móvil al abrir el modal "Manage Booking"
document.addEventListener('DOMContentLoaded', () => {
    const collapseEl = document.getElementById('navbarMain');
    const modalEl = document.getElementById('manageBookingModal');

    if (!collapseEl || !modalEl) return;

    // Enlaces que abren ese modal (por si hay más de uno en el futuro)
    const manageLinks = document.querySelectorAll('a[data-bs-target="#manageBookingModal"]');

    // Función que asegura que la navbar colapsable quede cerrada
    const closeNavbar = () => {
        // Crea/obtiene la instancia sin toggle automático
        const navCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });

        // Solo intenta cerrar si está abierta (clase .show)
        if (collapseEl.classList.contains('show')) {
            navCollapse.hide();

            // Refresca el estado visual/ARIA del botón hamburguesa (por si acaso)
            const toggler = document.querySelector('.navbar-toggler[aria-controls="navbarMain"]');
            toggler?.classList.add('collapsed');
            toggler?.setAttribute('aria-expanded', 'false');
        }
    };

    // 1) Al hacer clic en "Manage Booking" (cierra antes de abrir el modal)
    manageLinks.forEach(a => a.addEventListener('click', closeNavbar));

    // 2) Por redundancia: si otro script abre el modal, cerramos igual
    modalEl.addEventListener('show.bs.modal', closeNavbar);
});
