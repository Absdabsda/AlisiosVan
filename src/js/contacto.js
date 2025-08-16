document.addEventListener('DOMContentLoaded', () => {
    /* ===========================
       FORMULARIO DE CONTACTO (igual que me pasaste)
       =========================== */
    const form = document.getElementById('contactForm');
    if (form) {
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
    }

    /* ===========================
       WHATSAPP MINI-CHAT
       =========================== */
    const PHONE = '34610136383';           // sin +
    const REDIRECT_AFTER_SEND_MS = 900;     // tiempo de lectura al pulsar enviar
    const GREET_DELAY_MS = 150;

    const launcher = document.getElementById('wa-launcher');
    const panel    = document.getElementById('wa-panel');
    const closeBtn = document.getElementById('wa-close');
    const messages = document.getElementById('wa-messages');
    const input    = document.getElementById('wa-input');
    const sendBtn  = document.getElementById('wa-send');
    const quick    = document.getElementById('wa-quick');

    if (!launcher || !panel) return;

    let greeted = false;

    function addMsg(text, who) {
        const div = document.createElement('div');
        div.className = 'msg ' + (who || 'bot');
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function openPanel() {
        panel.hidden = false;
        if (!greeted) {
            greeted = true;
            setTimeout(() => {
                addMsg('¡Hola! 👋 Somos Alisios Van.');
                addMsg('Elige una opción o escribe tu mensaje y luego pulsa “enviar”.');
            }, GREET_DELAY_MS);
        }
    }
    function closePanel() { panel.hidden = true; }

    function openWhatsApp(text) {
        const msg = text && text.trim() ? text.trim() : 'Hola, me gustaría más información 🙂';
        const url = 'https://wa.me/' + PHONE + '?text=' + encodeURIComponent(msg + '\n\n(Página: ' + window.location.href + ')');
        if (typeof gtag === 'function') {
            gtag('event', 'click', { event_category: 'engagement', event_label: 'whatsapp_mini_chat' });
        } else if (window.dataLayer) {
            window.dataLayer.push({ event: 'whatsapp_click', source: 'mini_chat' });
        }
        window.open(url, '_blank', 'noopener');
    }

    launcher.addEventListener('click', () => { panel.hidden ? openPanel() : closePanel(); });
    closeBtn?.addEventListener('click', closePanel);

    // Enviar → aviso + breve pausa antes de abrir WhatsApp
    sendBtn.addEventListener('click', () => {
        const text = input.value;
        if (!text.trim()) { input.focus(); return; }
        addMsg(text, 'user');
        input.value = '';
        setTimeout(() => {
            addMsg('Abriendo WhatsApp…', 'bot');
            setTimeout(() => openWhatsApp(text), REDIRECT_AFTER_SEND_MS);
        }, 200);
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); sendBtn.click(); }
    });

    // Chips → ya no redirigen: dejan el mensaje preparado
    quick.addEventListener('click', (e) => {
        if (e.target.matches('button[data-text]')) {
            const t = e.target.getAttribute('data-text');
            input.value = t;
            input.focus();
            addMsg('Mensaje preparado. Pulsa “enviar” para abrir WhatsApp 👉', 'bot');
        }
    });

    // Abrir automáticamente una vez por sesión a los 6s
    if (!sessionStorage.getItem('waOpenedOnce')) {
        setTimeout(() => {
            openPanel();
            sessionStorage.setItem('waOpenedOnce', '1');
        }, 6000);
    }
});
