document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const successMsg = form.querySelector('.form-success');
    const submitBtn  = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validación
        const required = ['name', 'email', 'message'];
        let ok = true;
        required.forEach(id => {
            const field = form.querySelector('#' + id).closest('.field');
            field.classList.remove('error');
            if (!form[id].value.trim()) { field.classList.add('error'); ok = false; }
        });
        const email = form.email.value.trim();
        if (email && !/^\S+@\S+\.\S+$/.test(email)) {
            form.email.closest('.field').classList.add('error'); ok = false;
        }
        const privacy = document.getElementById('privacy');
        const privacyWrap = privacy.closest('.field');
        privacyWrap.classList.remove('error');
        if (!privacy.checked) { privacyWrap.classList.add('error'); ok = false; }

        if (!ok) return;

        // Envío
        submitBtn.disabled = true;
        const original = submitBtn.textContent;
        submitBtn.textContent = 'Sending…';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.ok) {
                form.reset();
                if (successMsg) {
                    successMsg.hidden = false;
                    setTimeout(() => successMsg.hidden = true, 6000);
                } else {
                    alert('Thanks! We’ve received your message and will reply soon.');
                }
            } else {
                alert(data.error || 'There was a problem sending your message. Please try again.');
            }
        } catch {
            alert('Network error. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = original;
        }
    });
});
