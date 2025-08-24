// src/js/buscar.js
(function () {
    // Detecta la raíz del proyecto según estés en /src/...
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
        if (!el) return;
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
    function nightsBetweenYmd(s1, s2) {
        const d1 = parseYMDToLocalDate(s1);
        const d2 = parseYMDToLocalDate(s2);
        if (!d1 || !d2) return 0;
        return Math.round((d2 - d1) / 86400000);
    }

    // ---------- Locale común para Flatpickr ----------
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
    function setDatesAndReload(newStart, newEnd) {
        start = newStart;
        end   = newEnd;
        updateRangeLabel();
        updateQueryString();
        updateBackLinkHref();
        if (window.__datePicker) {
            window.__datePicker.setDate([parseYMDToLocalDate(start), parseYMDToLocalDate(end)], true);
        }
        loadAvailability();
    }

    // ---------- Render de cards ----------
    function render(campers) {
        resultsEl.innerHTML = '';
        if (!campers.length) {
            // El mensaje se pinta en loadAvailability() usando meta
            emptyMsg.style.display = '';
            return;
        }
        emptyMsg.style.display = 'none';

        campers.forEach(c => {
            const img = resolveImage(c.image || IMAGE_BY_ID[c.id] || guessImageFromName(c.name));
            const q = (start && end)
                ? `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`
                : '';
            const detailsHref = `ficha-camper.php?id=${encodeURIComponent(c.id)}&from=buscar${q}`;

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

            if (data.campers && data.campers.length) {
                render(data.campers);
                return;
            }

            // ----- Sin resultados: construimos mensajes útiles -----
            const meta = data.meta || {};
            const nights = meta.nights ?? nightsBetweenYmd(start, end);
            const PHONE = '34610136383';
            const waText = `Hola, estoy viendo disponibilidad (${start} a ${end}, ${nights} noche/s) y no me aparecen campers. ¿Podemos ver opciones?`;
            const waUrl  = `https://wa.me/${PHONE}?text=` + encodeURIComponent(waText);

            if (meta.no_results_reason === 'min_nights' && meta.min_required) {
                const btnId = 'btnAdjustToMin';
                emptyMsg.innerHTML = `
          <div class="alert alert-info" role="alert" style="line-height:1.45">
            <strong>No hay resultados</strong> para ${nights} noche${nights===1?'':'s'}.
            Para estas fechas, el <b>mínimo requerido</b> es <b>${meta.min_required}</b> noche${meta.min_required===1?'':'s'}.
            <div class="mt-2 d-flex gap-2 flex-wrap">
              <button id="${btnId}" class="btn btn-primary btn-sm">
                Ajustar a ${meta.min_required} noche${meta.min_required===1?'':'s'}
              </button>
              <a class="btn btn-outline-success btn-sm" href="${waUrl}" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Escríbenos por WhatsApp
              </a>
            </div>
            <div class="text-muted small mt-2">
              No es posible mostrar otras opciones porque no cumplen el mínimo de noches necesario.
            </div>
          </div>
        `;
                emptyMsg.style.display = '';

                document.getElementById(btnId)?.addEventListener('click', () => {
                    let suggestedEnd = meta.suggested_end;
                    if (!suggestedEnd) {
                        const base = parseYMDToLocalDate(start);
                        const e = new Date(base.getFullYear(), base.getMonth(), base.getDate() + meta.min_required);
                        suggestedEnd = ymdLocal(e); // end exclusivo
                    }
                    setDatesAndReload(start, suggestedEnd);
                });

                return;
            }

            // Ocupado / bloqueos
            emptyMsg.innerHTML = `
        <div class="alert alert-warning" role="alert" style="line-height:1.45">
          <strong>Sin disponibilidad</strong> para ${start} → ${end}.
          Puede deberse a reservas confirmadas o bloqueos de calendario.
          <div class="mt-2 d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-primary btn-sm" href="#" id="btnTryShift">Probar +1 día</a>
            <a class="btn btn-outline-success btn-sm" href="${waUrl}" target="_blank" rel="noopener">
              <i class="bi bi-whatsapp"></i> WhatsApp
            </a>
          </div>
          <div class="text-muted small mt-2">Cuéntanos tus fechas y vemos alternativas.</div>
        </div>
      `;
            emptyMsg.style.display = '';

            document.getElementById('btnTryShift')?.addEventListener('click', (e) => {
                e.preventDefault();
                const s = parseYMDToLocalDate(start);
                const e2 = parseYMDToLocalDate(end);
                s.setDate(s.getDate() + 1);
                e2.setDate(e2.getDate() + 1);
                setDatesAndReload(ymdLocal(s), ymdLocal(e2));
            });

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

                showCheckoutOverlay('Redirecting you to a safe checkout…');

                try {
                    const res = await fetch(apiUrl('create-checkout.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ camper_id: camperId, start, end })
                    });
                    const data = await res.json();
                    if (data.ok && data.url) {
                        window.location.href = data.url;
                    } else {
                        hideCheckoutOverlay();
                        alert(data.error || 'Could not initialize checkout.');
                    }
                } catch (err) {
                    console.error(err);
                    hideCheckoutOverlay();
                    alert('Network error.');
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

        // Guarda referencia para poder ajustar fechas desde los CTA
        window.__datePicker = fp;

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
       WHATSAPP MINI-CHAT (desktop) / Deep-link directo (móvil)
       =========================== */
    const PHONE = '34610136383';           // sin +
    const REDIRECT_AFTER_SEND_MS = 900;
    const GREET_DELAY_MS = 150;

    const launcher = document.getElementById('wa-launcher');
    const panel    = document.getElementById('wa-panel');
    const closeBtn = document.getElementById('wa-close');
    const messages = document.getElementById('wa-messages');
    const input    = document.getElementById('wa-input');
    const sendBtn  = document.getElementById('wa-send');
    const quick    = document.getElementById('wa-quick');

    if (!launcher || !panel) return;

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

    function openWhatsApp(text, sameTab = false) {
        const msg = text && text.trim() ? text.trim() : 'Hola, me gustaría más información 🙂';
        const page = '\n\n(Página: ' + window.location.href + ')';
        const waUrl = 'https://wa.me/' + PHONE + '?text=' + encodeURIComponent(msg + page);

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

    launcher.addEventListener('click', () => {
        if (isMobileDevice()) {
            const text = (input?.value || '').trim();
            openWhatsApp(text, true);
            return;
        }
        panel.hidden ? openPanel() : closePanel();
    });
    closeBtn?.addEventListener('click', closePanel);

    sendBtn?.addEventListener('click', () => {
        const text = input.value;
        if (!text.trim()) { input.focus(); return; }
        addMsg(text, 'user');
        input.value = '';
        setTimeout(() => {
            addMsg('Abriendo WhatsApp…', 'bot');
            setTimeout(() => openWhatsApp(text, isMobileDevice()), REDIRECT_AFTER_SEND_MS);
        }, 200);
    });
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); sendBtn.click(); }
    });

    quick?.addEventListener('click', (e) => {
        if (e.target.matches('button[data-text]')) {
            const t = e.target.getAttribute('data-text');
            input.value = t;
            input.focus();
            addMsg('Mensaje preparado. Pulsa “enviar” para abrir WhatsApp 👉', 'bot');
        }
    });

    if (!sessionStorage.getItem('waOpenedOnce') && !isMobileDevice()) {
        setTimeout(() => {
            openPanel();
            sessionStorage.setItem('waOpenedOnce', '1');
        }, 6000);
    }
})();
