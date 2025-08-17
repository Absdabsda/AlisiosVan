// contacto.js
document.addEventListener('DOMContentLoaded', () => {
    /* ===========================
       FORMULARIO DE CONTACTO
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
       WHATSAPP MINI-CHAT (desktop)
       / DEEP-LINK DIRECTO (móvil)
       =========================== */

    // --- Config ---
    const PHONE = '34610136383';        // sin "+" (formato E.164 sin signo)
    const REDIRECT_AFTER_SEND_MS = 900; // pequeña pausa antes de abrir WA tras "enviar"
    const GREET_DELAY_MS = 150;         // retardo para mensajes de bienvenida

    // --- Elementos del widget ---
    const launcher = document.getElementById('wa-launcher');
    const panel    = document.getElementById('wa-panel');
    const closeBtn = document.getElementById('wa-close');
    const messages = document.getElementById('wa-messages');
    const input    = document.getElementById('wa-input');
    const sendBtn  = document.getElementById('wa-send');
    const quick    = document.getElementById('wa-quick');

    // Si no existe el widget en esta página, no hacemos nada con WhatsApp
    if (!launcher || !panel) return;

    // --- Detector robusto de móvil ---
    function isMobileDevice() {
        if (navigator.userAgentData && typeof navigator.userAgentData.mobile === 'boolean') {
            return navigator.userAgentData.mobile;
        }
        const ua = navigator.userAgent || navigator.vendor || window.opera;
        const mobileUA = /Android|iPhone|iPad|iPod|IEMobile|BlackBerry|Opera Mini/i.test(ua);
        const touch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
        const smallSide = Math.min(window.innerWidth, window.innerHeight) <= 820;
        return (mobileUA && touch) || (touch && smallSide);
    }

    // --- Utilidades del mini-chat ---
    let greeted = false;

    function addMsg(text, who) {
        if (!messages) return;
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

    // --- Abrir WhatsApp ---
    // sameTab=true en móvil: intenta app nativa con fallback a wa.me (evita bloqueos de popups)
    function openWhatsApp(text, sameTab = false) {
        const msg = text && text.trim() ? text.trim() : 'Hola, me gustaría más información 🙂';
        const page = '\n\n(Página: ' + window.location.href + ')';
        const waUrl = 'https://wa.me/' + PHONE + '?text=' + encodeURIComponent(msg + page);

        // Analítica opcional
        if (typeof gtag === 'function') {
            gtag('event', 'click', { event_category: 'engagement', event_label: 'whatsapp_mini_chat' });
        } else if (window.dataLayer) {
            window.dataLayer.push({ event: 'whatsapp_click', source: 'mini_chat' });
        }

        if (sameTab) {
            const deep = 'whatsapp://send?phone=' + PHONE + '&text=' + encodeURIComponent(msg);
            const fallbackTimer = setTimeout(() => { window.location.href = waUrl; }, 600);
            window.location.href = deep;
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) clearTimeout(fallbackTimer);
            }, { once: true });
        } else {
            window.open(waUrl, '_blank', 'noopener');
        }
    }

    // --- Comportamiento del botón flotante ---
    launcher.addEventListener('click', () => {
        if (isMobileDevice()) {
            // Móvil: directo a WhatsApp (misma pestaña con deep-link)
            const text = (input?.value || '').trim();
            openWhatsApp(text, /* sameTab */ true);
            return;
        }
        // Desktop: abrir/cerrar panel
        panel.hidden ? openPanel() : closePanel();
    });

    // Botón cerrar del panel
    closeBtn?.addEventListener('click', closePanel);

    // --- Enviar desde el mini-chat ---
    // Desktop: muestra aviso y luego abre WA en nueva pestaña
    // Móvil (si alguien llega a abrir el panel): forzar sameTab=true
    sendBtn?.addEventListener('click', () => {
        const text = input?.value || '';
        if (!text.trim()) { input?.focus(); return; }

        addMsg(text, 'user');
        if (input) input.value = '';

        setTimeout(() => {
            addMsg('Abriendo WhatsApp…', 'bot');
            setTimeout(() => openWhatsApp(text, isMobileDevice() /* sameTab on mobile */), REDIRECT_AFTER_SEND_MS);
        }, 200);
    });

    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); sendBtn?.click(); }
    });

    // --- Respuestas rápidas: rellenan input (no abren WA directamente) ---
    quick?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-text]');
        if (!btn) return;
        const t = btn.getAttribute('data-text') || '';
        if (input) {
            input.value = t;
            input.focus();
        }
        addMsg('Mensaje preparado. Pulsa “enviar” para abrir WhatsApp 👉', 'bot');
    });

    // --- Auto-abrir SOLO en escritorio (no en móvil) ---
    if (!sessionStorage.getItem('waOpenedOnce') && !isMobileDevice()) {
        setTimeout(() => {
            openPanel();
            sessionStorage.setItem('waOpenedOnce', '1');
        }, 6000);
    }
});
