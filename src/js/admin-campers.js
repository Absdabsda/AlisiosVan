document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btnSaveCamper').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const tr = e.currentTarget.closest('tr[data-id]');
            if (!tr) return;

            const id    = tr.getAttribute('data-id');
            const price = tr.querySelector('input[name="price_per_night"]').value;
            const min   = tr.querySelector('input[name="min_nights"]').value;

            try {
                const res = await fetch('admin-campers.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'update',
                        id,
                        price_per_night: price,
                        min_nights: min
                    })
                });
                const data = await res.json();
                if (!data.ok) throw new Error(data.error || 'Error');

                // feedback visual
                btn.classList.remove('btn-primary'); btn.classList.add('btn-success');
                btn.innerHTML = '<i class="bi bi-check2"></i> Guardado';
                setTimeout(() => {
                    btn.classList.remove('btn-success'); btn.classList.add('btn-primary');
                    btn.innerHTML = '<i class="bi bi-save"></i> Guardar';
                }, 1000);
            } catch (err) {
                alert('No se pudo guardar: ' + err.message);
            }
        });
    });
});
