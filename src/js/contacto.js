// src/js/contacto.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // validación muy básica
        const required = ['name', 'email', 'message'];
        let ok = true;

        required.forEach(id => {
            const fieldWrap = form.querySelector(`#${id}`).closest('.field');
            fieldWrap.classList.remove('error');
            if (!form[id].value.trim()) {
                fieldWrap.classList.add('error'); ok = false;
            }
        });

        const email = form.email.value.trim();
        if (email && !/^\S+@\S+\.\S+$/.test(email)) {
            form.email.closest('.field').classList.add('error');
            ok = false;
        }

        const privacy = document.getElementById('privacy');
        const privacyWrap = privacy.closest('.field');
        privacyWrap.classList.remove('error');
        if (!privacy.checked) { privacyWrap.classList.add('error'); ok = false; }

        if (!ok) return;

        // Simulación de envío (quizá quieras AJAX o un PHP real)
        form.reset();
        form.querySelector('.form-success').hidden = false;
        setTimeout(() => form.querySelector('.form-success').hidden = true, 6000);
    });
});
