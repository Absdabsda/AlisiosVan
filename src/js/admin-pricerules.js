document.addEventListener('DOMContentLoaded', () => {
    if (!window.bootstrap || typeof FullCalendar === 'undefined') return;

    const PRICERULES_URL = window.ADMIN_PRICERULES_URL || 'admin-pricerules.php';
    const calendars = new Map();

    // Modal y campos (IDs iguales a los del HTML)
    const modalEl = document.getElementById('priceRuleModal');
    if (!modalEl) return;
    const modal   = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, focus: true });

    const fCamper  = document.getElementById('priceRuleCamper');
    const fStart   = document.getElementById('priceRuleStart');
    const fEnd     = document.getElementById('priceRuleEnd');
    const fPrice   = document.getElementById('priceRuleValue');
    const fNote    = document.getElementById('priceRuleNote');
    const fReplace = document.getElementById('priceRuleReplace');
    const btnSave  = document.getElementById('priceRuleSave');

    const ymd  = (d)=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    const dateOnly = (iso)=> (iso || '').slice(0,10);
    const monthBounds = (date)=>({ first:new Date(date.getFullYear(),date.getMonth(),1), last:new Date(date.getFullYear(),date.getMonth()+1,0) });
    const parseJsonOrThrow = async (res)=>{ const t = await res.text(); try { return JSON.parse(t); } catch { throw new Error(t || `HTTP ${res.status}`); } };
    const parsePrice = (v)=> parseFloat(String(v).replace(',','.'));

    // Inicializa/recarga mini-cal de precios cuando se abre el collapse
    document.querySelectorAll('.collapse[id^="rules-"]').forEach(collapseEl => {
        collapseEl.addEventListener('shown.bs.collapse', () => {
            const camperId  = collapseEl.id.replace('rules-','');
            const container = collapseEl.querySelector('.mini-cal-price');
            const btnMonth  = collapseEl.querySelector('.btnApplyMonthPrice');
            if (!container || !camperId) return;

            if (!calendars.has(camperId)) {
                const cal = new FullCalendar.Calendar(container, {
                    initialView: 'dayGridMonth',
                    height: 'auto',
                    firstDay: 1,
                    headerToolbar: { left: 'prev,next', center: 'title', right: '' },
                    weekNumbers: true,
                    selectable: true,
                    selectMirror: true,

                    events: (info, success, failure) => {
                        const start = encodeURIComponent(dateOnly(info.startStr));
                        const end   = encodeURIComponent(dateOnly(info.endStr));
                        const join  = PRICERULES_URL.includes('?') ? '&' : '?';
                        const url   = `${PRICERULES_URL}${join}action=list&camper_id=${encodeURIComponent(camperId)}&start=${start}&end=${end}`;
                        fetch(url, { credentials:'same-origin' })
                            .then(parseJsonOrThrow)
                            .then(data => { if (!data.ok) throw new Error(data.error || 'Error'); success(data.events || []); })
                            .catch(err => failure(err));
                    },

                    select: (info) => {
                        const endInc = new Date(info.endStr + 'T00:00:00'); endInc.setDate(endInc.getDate() - 1);
                        if (fCamper) fCamper.value = camperId;
                        if (fStart)  fStart.value  = dateOnly(info.startStr);
                        if (fEnd)    fEnd.value    = ymd(endInc);
                        if (fPrice)  fPrice.value  = '';
                        if (fNote)   fNote.value   = '';
                        if (fReplace) fReplace.checked = false;
                        modal.show();
                    },

                    dateClick: (info) => {
                        if (fCamper) fCamper.value = camperId;
                        if (fStart)  fStart.value  = info.dateStr;
                        if (fEnd)    fEnd.value    = info.dateStr;
                        if (fPrice)  fPrice.value  = '';
                        if (fNote)   fNote.value   = '';
                        if (fReplace) fReplace.checked = false;
                        modal.show();
                    },

                    eventClick: (info) => {
                        const id = String(info.event.id || '');
                        if (!id.startsWith('pr-')) return;
                        info.jsEvent.preventDefault();
                        if (!confirm('¿Eliminar esta regla de precio?')) return;

                        fetch(PRICERULES_URL, {
                            method:'POST',
                            credentials:'same-origin',
                            headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ action:'delete', id:id.replace('pr-','') })
                        })
                            .then(parseJsonOrThrow)
                            .then(data => { if (!data.ok) throw new Error(data.error || 'Error'); cal.refetchEvents(); })
                            .catch(e => alert('No se pudo eliminar: ' + e.message));
                    }
                });

                calendars.set(camperId, cal);
                cal.render();

                btnMonth?.addEventListener('click', () => {
                    const current = cal.getDate();
                    const { first, last } = monthBounds(current);
                    if (fCamper) fCamper.value = camperId;
                    if (fStart)  fStart.value  = ymd(first);
                    if (fEnd)    fEnd.value    = ymd(last);
                    if (fPrice)  fPrice.value  = '';
                    if (fNote)   fNote.value   = '';
                    if (fReplace) fReplace.checked = false;
                    modal.show();
                });
            } else {
                calendars.get(camperId).refetchEvents();
            }
        });
    });

    // Guardar regla de precio
    btnSave?.addEventListener('click', () => {
        const camperId = fCamper?.value;
        const startStr = fStart?.value;
        const endStr   = fEnd?.value;
        const price    = parsePrice(fPrice?.value);
        const replace  = fReplace?.checked ? '1' : '0';

        if (!camperId || !startStr || !endStr || !Number.isFinite(price) || price <= 0) {
            alert('Datos inválidos.'); return;
        }

        fetch(PRICERULES_URL, {
            method:'POST',
            credentials:'same-origin',
            headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action:'set_range',
                camper_id:camperId,
                start_date:startStr,
                end_date:endStr,
                price_per_night:String(price),
                note:fNote?.value || '',
                replace
            })
        })
            .then(parseJsonOrThrow)
            .then(data => {
                if (!data.ok) throw new Error(data.error || 'Error al guardar');
                modal.hide();
                const cal = calendars.get(camperId);
                if (cal) cal.refetchEvents();
            })
            .catch(e => alert(e.message));
    });
});
