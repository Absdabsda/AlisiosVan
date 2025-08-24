document.addEventListener('DOMContentLoaded', function () {
    const btnRefresh = document.getElementById('btnRefreshCal');
    const calendarEl = document.getElementById('adminCalendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') return;

    const blkModalEl = document.getElementById('blkModal');
    let blkModal = null;
    function openBlkModal(){ if (!blkModalEl || !window.bootstrap) return alert('No se pudo abrir el modal.'); blkModal = bootstrap.Modal.getOrCreateInstance(blkModalEl, {backdrop:true, focus:true}); blkModal.show(); }

    const fCamper = document.getElementById('blkCamper');
    const fStart  = document.getElementById('blkStart');
    const fEnd    = document.getElementById('blkEnd');
    const fReason = document.getElementById('blkReason');
    const btnCreate = document.getElementById('blkCreate');

    const addDays = (dStr, n) => { const d = new Date(dStr+'T00:00:00'); d.setDate(d.getDate()+n); return d.toISOString().slice(0,10); };

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView:'dayGridMonth', height:'auto', firstDay:1,
        headerToolbar:{left:'prev,next today',center:'title',right:'dayGridMonth,listMonth'},
        selectable:true, selectMirror:true, selectOverlap:true,
        events:{ url: window.ADMIN_EVENTS_URL, extraParams:()=>({_ts:Date.now()}), failure:()=>alert('No se pudieron cargar los eventos.') },
        select(info){
            const endInc = addDays(info.endStr, -1);
            // Prefijar camper con el selector superior
            const selectCard = document.getElementById('blockCamper');
            if (selectCard && selectCard.value) fCamper.value = selectCard.value;
            fStart.value = info.startStr; fEnd.value = endInc; fReason.value = ''; openBlkModal();
        },
        eventClick: async function (info) {
            const id = String(info.event.id || '');
            if (id.startsWith('blk-')) {
                info.jsEvent.preventDefault();
                if (!confirm('¿Eliminar este bloqueo?')) return;
                try {
                    const res = await fetch('admin-blocks.php', {
                        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({action:'delete', key: window.ADMIN_KEY, id: id.replace('blk-','')})
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.error||'Error');
                    calendar.refetchEvents();
                } catch(e){ alert('No se pudo eliminar: '+e.message); }
                return;
            }
            if (info.event.url) { info.jsEvent.preventDefault(); window.open(info.event.url,'_blank','noopener'); }
        },
        eventDisplay:'block'
    });

    btnCreate?.addEventListener('click', async () => {
        const camperId = fCamper.value, startStr = fStart.value, endStr = fEnd.value, reason = fReason.value || '';
        if (!camperId || !startStr || !endStr) { alert('Completa camper y rangos.'); return; }
        try {
            const res = await fetch('admin-blocks.php', {
                method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:new URLSearchParams({action:'create', key:window.ADMIN_KEY, camper_id:camperId, start_date:startStr, end_date:endStr, reason})
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error||'Error');
            blkModal && blkModal.hide();
            calendar.unselect(); calendar.refetchEvents();
        } catch(e){ alert('Error creando el bloqueo: ' + e.message); }
    });

    calendar.on('loading', (isLoading) => { if (!btnRefresh) return; btnRefresh.disabled = isLoading; btnRefresh.classList.toggle('disabled', isLoading); });
    calendar.render();
    btnRefresh?.addEventListener('click', () => calendar.refetchEvents());
});
