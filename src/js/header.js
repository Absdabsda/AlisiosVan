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
