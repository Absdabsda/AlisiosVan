// js/header.js
document.addEventListener('DOMContentLoaded', () => {
    const nav = document.querySelector('nav.navbar');
    if (!nav) return;
    const onScroll = () => nav.classList.toggle('is-sticky', window.scrollY > 8);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});
