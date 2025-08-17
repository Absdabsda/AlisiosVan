// src/js/buscar.js
(function () {
    // Detecta la raíz del proyecto según estés en /src/...
    // Ej: en local => "/CanaryVanGit/AlisiosVan", en prod => ""
    const ROOT = location.pathname.includes('/src/')
        ? location.pathname.split('/src/')[0]
        : '';

    // Helpers para construir URLs
    const apiUrl = (file) => new URL(`${ROOT}/api/${file}`, location.origin).toString();
    const IMG_PREFIX = `${ROOT}/src/`;

    // ---------- Helpers de imágenes ----------
    function resolveImage(path, fallback) {
        const p = (path || '').trim();
        if (!p) return IMG_PREFIX + (fallback || 'img/carousel/t3-azul-mar.webp');
        if (/^https?:\/\//i.test(p) || p.startsWith('/')) return p;
        return IMG_PREFIX + p.replace(/^\.?\//, '');
    }
    const IMAGE_BY_ID = {
        1: 'img/carousel/matcha-surf.34.32.jpeg',
        2: 'img/carousel/t3-azul-playa.webp',
        3: 'img/carousel/t4-sol.webp'
    };
    function guessImageFromName(name) {
        const n = (name || '').toLowerCase();
        if (n.includes('matcha')) return 'img/carousel/t3-azul-mar.webp';
        if (n.includes('skye'))   return 'img/carousel/t3-azul-playa.webp';
        if (n.includes('rusty'))  return 'img/carousel/t4-sol.webp';
        return 'img/carousel/t3-azul-mar.webp';
    }

    // ---------- Estado inicial ----------
    const qs = new URLSearchParams(location.search);
    let start = qs.get('start') || '';
    let end   = qs.get('end')   || '';
    let seriesFilter = '';

    // ---------- Elementos del DOM ----------
    const resultsEl  = document.getElementById('results');
    const emptyMsg   = document.getElementById('emptyMsg');
    const rangeLabel = document.getElementById('rangeLabel');
    const dateInput  = document.getElementById('dateRange');
    const backLink   = document.getElementById('backLink');

    // ---------- Overlay checkout ----------
    function showCheckoutOverlay(msg){
        const el = document.getElementById('checkoutOverlay');
        if (!el) return; // si no existe en el HTML, simplemente no mostramos overlay
        const p = el.querySelector('p');
        if (p && msg) p.textContent = msg;
        el.classList.add('show');
        el.removeAttribute('hidden');
    }
    function hideCheckoutOverlay(){
        const el = document.getElementById('checkoutOverlay');
        if (el) el.classList.remove('show');
    }

    // ---------- Fechas: SIEMPRE local ----------
    const ymdLocal = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    function parseYMDToLocalDate(s) {
        if (!s) return null;
        const [y, m, d] = s.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    // ---------- Locale común para Flatpickr (arreglado Oct/Nov) ----------
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

    // ---------- UI helpers ----------
    function updateRangeLabel() {
        if (!rangeLabel) return;
        rangeLabel.textContent = (start && end) ? `From ${start} to ${end}` : '';
    }
    function updateQueryString() {
        const url = new URL(location.href);
        if (start && end) {
            url.searchParams.set('start', start);
            url.searchParams.set('end', end);
        } else {
            url.searchParams.delete('start');
            url.searchParams.delete('end');
        }
        history.replaceState(null, '', url);
    }
    function updateBackLinkHref() {
        if (!backLink) return;
        const url = new URL(backLink.href, location.origin);
        if (start && end) {
            url.searchParams.set('start', start);
            url.searchParams.set('end', end);
        } else {
            url.searchParams.delete('start');
            url.searchParams.delete('end');
        }
        backLink.href = url.toString();
    }

    // ---------- Render de cards ----------
    function render(campers) {
        resultsEl.innerHTML = '';
        if (!campers.length) {
            emptyMsg.textContent = 'No campers available for these dates.';
            emptyMsg.style.display = '';
            return;
        }
        emptyMsg.style.display = 'none';

        campers.forEach(c => {
            const img = resolveImage(c.image || IMAGE_BY_ID[c.id] || guessImageFromName(c.name));
            const q = (start && end) ? `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}` : '';
            const detailsHref = `campers.php?id=${encodeURIComponent(c.id)}${q}`;

            const col = document.createElement('div');
            col.className = 'col-md-4 camper-col';
            col.innerHTML = `
        <div class="camper-card">
          <a href="${detailsHref}">
            <img src="${img}" alt="${c.name}" loading="lazy">
          </a>
          <div class="camper-info">
            <h3>"${c.name}"</h3>
            <p>${Number(c.price_per_night).toFixed(0)}€ per night.</p>
            <div class="d-flex align-items-center mt-2">
              <button class="btn btn-primary btn-sm js-reserve" data-id="${c.id}">Reserve</button>
              <a class="btn btn-outline-secondary btn-sm ms-auto" href="${detailsHref}">View camper</a>
            </div>
          </div>
        </div>
      `;
            resultsEl.appendChild(col);
        });

        hookReserveButtons();
    }

    // ---------- Cargar disponibilidad ----------
    async function loadAvailability() {
        resultsEl.innerHTML = '';
        emptyMsg.style.display = 'none';

        if (!start || !end) {
            emptyMsg.textContent = 'Missing dates.';
            emptyMsg.style.display = '';
            return;
        }

        const url = new URL(apiUrl('availability.php'));
        url.searchParams.set('start', start);
        url.searchParams.set('end', end);
        if (seriesFilter) url.searchParams.set('series', seriesFilter);

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Error');
            render(data.campers);
        } catch (e) {
            console.error(e);
            emptyMsg.textContent = "Couldn't load availability.";
            emptyMsg.style.display = '';
        }
    }

    // ---------- Filtro por modelo ----------
    document.querySelectorAll('.model-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.model-chip').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            seriesFilter = btn.dataset.series || '';
            loadAvailability();
        });
    });

    // ---------- Checkout directo (SIN modal) ----------
    function hookReserveButtons() {
        document.querySelectorAll('.js-reserve').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!start || !end) {
                    alert('Select the dates before reserving.');
                    return;
                }

                const camperId = Number(btn.dataset.id || 0);
                if (!camperId) return;

                const old = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = 'Redirecting...';

                // Muestra overlay (si está añadido en el HTML)
                showCheckoutOverlay('Redirecting you to a safe checkout…');

                try {
                    const res = await fetch(apiUrl('create-checkout.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ camper_id: camperId, start, end })
                    });
                    const data = await res.json();
                    if (data.ok && data.url) {
                        // dejamos el overlay visible mientras salta a Stripe
                        window.location.href = data.url;
                    } else {
                        hideCheckoutOverlay();
                        alert(data.error || 'Could not initialize checkout.');
                    }
                } catch (err) {
                    console.error(err);
                    hideCheckoutOverlay();
                    alert('Error de red.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = old;
                }
            });
        });
    }

    // ---------- Inicializar UI ----------
    updateRangeLabel();
    updateBackLinkHref();

    // Calendario

    if (window.flatpickr && dateInput) {
        const isMobile = () => window.matchMedia('(max-width: 576px)').matches;

        const fp = flatpickr(dateInput, {
            mode: 'range',
            minDate: 'today',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j M Y',
            defaultDate: (start && end) ? [parseYMDToLocalDate(start), parseYMDToLocalDate(end)] : null,
            disableMobile: true,
            allowInput: false,
            clickOpens: true,

            showMonths: isMobile() ? 1 : 2,
            static: isMobile(),
            appendTo: isMobile() ? undefined : document.body,
            position: 'auto',
            locale: localeEN,

            onReady(_, __, inst){
                // En desktop asegúrate de que flote por encima de todo
                if (!isMobile()) inst.calendarContainer.style.zIndex = '10010';
                updateRangeLabel();
            },
            onOpen(_, __, inst){
                if (!isMobile()) inst.calendarContainer.style.zIndex = '10010';
            },
            onClose(selectedDates){
                if (selectedDates.length === 2) {
                    start = ymdLocal(selectedDates[0]);
                    end   = ymdLocal(selectedDates[1]);
                    updateRangeLabel();
                    updateQueryString();
                    updateBackLinkHref();
                    loadAvailability();
                }
            }
        });

        // Abrir tocando toda la “pill” de fechas
        dateInput.closest('.date-chip')?.addEventListener('click', () => fp.open());

        // Si cambia el ancho, actualiza meses y anclaje (se aplica al reabrir)
        window.addEventListener('resize', () => {
            const m = isMobile();
            fp.set('showMonths', m ? 1 : 2);
            fp.set('static', m);
            fp.set('appendTo', m ? undefined : document.body);
        });
    }


    // Primera carga
    loadAvailability();

    /* ===========================
   WHATSAPP (mini-chat en desktop / WhatsApp directo en móvil)
   =========================== */
    const PHONE = '34610136383';           // sin +
    const REDIRECT_AFTER_SEND_MS = 900;     // pequeña pausa antes de abrir WA tras "enviar"
    const GREET_DELAY_MS = 150;

    const launcher = document.getElementById('wa-launcher');
    const panel    = document.getElementById('wa-panel');
    const closeBtn = document.getElementById('wa-close');
    const messages = document.getElementById('wa-messages');
    const input    = document.getElementById('wa-input');
    const sendBtn  = document.getElementById('wa-send');
    const quick    = document.getElementById('wa-quick');

    // Si la página no tiene el widget, sal del bloque sin romper nada
    if (!launcher || !panel) return;

    // Detector robusto de móvil (UA-CH + heurísticas)
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

    // Mensaje por defecto: intenta incluir las fechas seleccionadas
    function buildDefaultMsg() {
        // 1) Si Flatpickr creó altInput visible, úsalo
        const visibleAlt = document.querySelector('.date-chip input.flatpickr-input')?.value?.trim();
        // 2) O usa ?start=YYYY-MM-DD&end=YYYY-MM-DD de la URL
        let urlDates = '';
        try {
            const ps = new URLSearchParams(location.search);
            const s = ps.get('start'); const e = ps.get('end');
            if (s && e) urlDates = `\nFechas: ${s} → ${e}`;
        } catch {}
        const base = 'Hola, me gustaría consultar disponibilidad 🙂';
        return visibleAlt ? `${base}\nFechas: ${visibleAlt}` : (urlDates ? `${base}${urlDates}` : base);
    }

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
                // Prefill con fechas si no hay texto
                const pre = buildDefaultMsg();
                if (pre && input && !input.value) input.value = pre;
            }, GREET_DELAY_MS);
        }
    }
    function closePanel() { panel.hidden = true; }

    // Abre WhatsApp (móvil: misma pestaña con deep-link + fallback; desktop: nueva pestaña)
    function openWhatsApp(text, sameTab = false) {
        const msg  = (text && text.trim()) ? text.trim() : buildDefaultMsg();
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

    // Botón flotante: móvil → WhatsApp directo; desktop → mini-chat
    launcher.addEventListener('click', () => {
        if (isMobileDevice()) {
            const text = (input?.value || '');
            openWhatsApp(text, /* sameTab */ true);
            return;
        }
        panel.hidden ? openPanel() : closePanel();
    });

    closeBtn?.addEventListener('click', closePanel);

    // Enviar desde mini-chat
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

    // Chips: rellenan input y añaden fechas si existen
    quick?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-text]');
        if (!btn) return;
        const t = btn.getAttribute('data-text') || '';
        const def = buildDefaultMsg();
        let datesPart = '';
        if (def.includes('Fechas:')) {
            const lines = def.split('\n').slice(1).join('\n'); // todo salvo la línea base
            datesPart = '\n' + lines;
        }
        if (input) {
            input.value = t + datesPart;
            input.focus();
        }
        addMsg('Mensaje preparado. Pulsa “enviar” para abrir WhatsApp 👉', 'bot');
    });

    // Auto-abrir solo en escritorio (no en móvil)
    if (!sessionStorage.getItem('waOpenedOnce') && !isMobileDevice()) {
        setTimeout(() => {
            openPanel();
            sessionStorage.setItem('waOpenedOnce', '1');
        }, 6000);
    }
})();

