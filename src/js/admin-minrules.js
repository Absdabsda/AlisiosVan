document.addEventListener('DOMContentLoaded', () => {
    if (!window.bootstrap || typeof FullCalendar === 'undefined') return;

    const MINRULES_URL = window.ADMIN_MINRULES_URL || 'admin-minrules.php';
    const calendars = new Map();

    // Modal + campos
    const modalEl = document.getElementById('minRuleModal');
    const modal   = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, focus: true });

    const fCamper  = document.getElementById('minRuleCamper');
    const fStart   = document.getElementById('minRuleStart');
    const fEnd     = document.getElementById('minRuleEnd');
    const fMin     = document.getElementById('minRuleValue');
    const fNote    = document.getElementById('minRuleNote');
    const fReplace = document.getElementById('minRuleReplace');
    const btnSave  = document.getElementById('minRuleSave');

    const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    const dateOnly = (iso) => (iso || '').slice(0,10);
    const monthBounds = (date) => ({ first: new Date(date.getFullYear(), date.getMonth(), 1), last: new Date(date.getFullYear(), date.getMonth()+1, 0) });

    const parseJsonOrThrow = async (res) => {
        const text = await res.text();
        try { return JSON.parse(text); } catch { throw new Error(text || `HTTP ${res.status}`); }
    };

    // Inicializa/recarga el mini-cal cuando se ABRE el colapsable
    document.querySelectorAll('.collapse[id^="rules-"]').forEach(collapseEl => {
        collapseEl.addEventListener('shown.bs.collapse', () => {
            const camperId  = collapseEl.id.replace('rules-','');
            const container = collapseEl.querySelector('.mini-cal');
            const btnMonth  = collapseEl.querySelector('.btnApplyMonth');
            if (!container || !camperId) return;

            if (!calendars.has(camperId)) {
                const cal = new FullCalendar.Calendar(container, {
                    plugins: (FullCalendar.interactionPlugin ? [ FullCalendar.interactionPlugin ] : []),
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
                        const join  = MINRULES_URL.includes('?') ? '&' : '?';
                        const url   = `${MINRULES_URL}${join}action=list&camper_id=${encodeURIComponent(camperId)}&start=${start}&end=${end}`;
                        fetch(url, { credentials: 'same-origin' })
                            .then(parseJsonOrThrow)
                            .then(data => { if (!data.ok) throw new Error(data.error || 'Error'); success(data.events || []); })
                            .catch(err => failure(err));
                    },

                    select: (info) => {
                        const endInc = new Date(info.endStr + 'T00:00:00'); endInc.setDate(endInc.getDate() - 1);
                        fCamper.value = camperId;
                        fStart.value  = dateOnly(info.startStr);
                        fEnd.value    = ymd(endInc);
                        fMin.value = ''; fNote.value = ''; fReplace.checked = false;
                        modal.show();
                    },

                    dateClick: (info) => {
                        fCamper.value = camperId;
                        fStart.value  = info.dateStr;
                        fEnd.value    = info.dateStr;
                        fMin.value = ''; fNote.value = ''; fReplace.checked = false;
                        modal.show();
                    },

                    eventClick: (info) => {
                        const id = String(info.event.id || '');
                        if (!id.startsWith('mr-')) return;
                        info.jsEvent.preventDefault();
                        if (!confirm('¿Eliminar esta regla?')) return;

                        fetch(MINRULES_URL, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ action: 'delete', id: id.replace('mr-','') })
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
                    fCamper.value = camperId;
                    fStart.value  = ymd(first);
                    fEnd.value    = ymd(last);
                    fMin.value = ''; fNote.value = ''; fReplace.checked = false;
                    modal.show();
                });
            } else {
                calendars.get(camperId).refetchEvents();
            }
        });
    });

    // Guardar regla
    btnSave.addEventListener('click', () => {
        const camperId = fCamper.value;
        const startStr = fStart.value;
        const endStr   = fEnd.value;
        const min      = parseInt(fMin.value, 10);
        const replace  = fReplace.checked ? '1' : '0';

        if (!camperId || !startStr || !endStr || !Number.isInteger(min) || min < 1 || min > 60) {
            alert('Datos inválidos.'); return;
        }

        fetch(MINRULES_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'set_range',
                camper_id: camperId,
                start_date: startStr,
                end_date: endStr,
                min_nights: String(min),
                note: fNote.value || '',
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
