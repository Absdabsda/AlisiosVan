// src/js/ficha-camper.js
(function () {
    'use strict';

    const PREFIX   = '/CanaryVanGit/AlisiosVan';
    const IMG_BASE = `${PREFIX}/src/`;

    // Props desde PHP
    const PROPS = window.__CAMPPER_PAGE_PROPS__ || { id: 0, start: '', end: '' };
    let { id, start, end } = PROPS;

    // ------- Helpers -------
    const ymdLocal   = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    const parseYMD   = s => s ? new Date(...s.split('-').map((n,i)=> i===1? +n-1:+n)) : null;
    const nightsBetween = (a,b) => Math.max(0, Math.round((b-a)/86400000));

    function imgUrl(p){
        if(!p) return '';
        const s = String(p).trim();
        if(/^https?:\/\//i.test(s) || s.startsWith('/')) return s;
        if(s.startsWith('src/')) return `${PREFIX}/${s}`;
        return `${IMG_BASE}${s.replace(/^\.?\//,'')}`;
    }

    // ------- DOM -------
    const hero = document.getElementById('pdHero');
    const titleEl = document.getElementById('pdTitle');
    const priceEl = document.getElementById('price');
    const badges = document.getElementById('badges');
    const amenitiesEl = document.getElementById('amenities');
    const mainWrap  = document.getElementById('galleryMainWrapper');
    const thumbWrap = document.getElementById('galleryThumbsWrapper');
    const nightsText = document.getElementById('nightsText');
    const btnBack   = document.getElementById('btnBack');
    const btnReserve= document.getElementById('btnReserve');
    const rf_camper_id = document.getElementById('rf_camper_id');
    const rf_start = document.getElementById('rf_start');
    const rf_end   = document.getElementById('rf_end');
    const reserveForm = document.getElementById('reserveForm');

    // ------- API camper -------
    async function fetchCamper(){
        const url = new URL(PREFIX + '/api/camper.php', location.origin);
        url.searchParams.set('id', id);
        try {
            const res = await fetch(url);
            if (res.ok) {
                const data = await res.json();
                if (data && data.ok) return data.camper;
            }
        } catch {}
        // fallback
        return {
            id,
            name: 'Camper',
            price_per_night: 100,
            seats: 4,
            images: [
                'img/carousel/t3-azul-mar.webp',
                'img/carousel/t3-azul-playa.webp',
                'img/carousel/t4-sol.webp'
            ],
            badges: ['Manual', 'Diesel', 'Solar', 'Fridge', '2-3 sleeps'],
            amenities: ['Bed 140cm','Portable shower','Kitchen kit','Camping table & chairs','USB chargers','Bluetooth speaker']
        };
    }

    // ------- Galería -------
    let main = null, thumbs = null;

    function svgPlaceholder(text='Image not found'){
        return `data:image/svg+xml;charset=utf8,${encodeURIComponent(
            `<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900">
        <defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#F4F4F4"/><stop offset="1" stop-color="#E9E9E9"/>
        </linearGradient></defs>
        <rect width="100%" height="100%" fill="url(#g)"/>
        <text x="50%" y="50%" fill="#9aa0a6" font-size="28" text-anchor="middle" dominant-baseline="middle"
          font-family="Arial, Helvetica, sans-serif">${text}</text>
      </svg>`
        )}`;
    }

    function addSlide(wrap, src, alt){
        const slide = document.createElement('div');
        slide.className = 'swiper-slide';
        const img = new Image();
        img.alt = alt;
        img.src = imgUrl(src);
        img.addEventListener('error', () => { img.src = svgPlaceholder(); main?.update(); thumbs?.update(); });
        img.addEventListener('load',  () => { main?.update(); thumbs?.update(); });
        slide.appendChild(img);
        wrap.appendChild(slide);
    }

    function initSwipers(){
        // Destruye instancias previas si recargas datos
        if (main)  { try{ main.destroy(true, true); }catch{} main = null; }
        if (thumbs){ try{ thumbs.destroy(true, true);}catch{} thumbs = null; }

        // Thumbs (principalmente para móvil)
        thumbs = new Swiper('#galleryThumbs', {
            slidesPerView: 4,
            spaceBetween: 8,
            freeMode: true,
            watchSlidesProgress: true,
            observer: true, observeParents: true,
            breakpoints: { 0:{slidesPerView:4}, 576:{slidesPerView:5} }
        });

        // Deja que el grid mida antes de iniciar
        requestAnimationFrame(() => {
            main = new Swiper('#galleryMain', {
                slidesPerView: 1,         // ← siempre 1
                spaceBetween: 0,          // ← sin “raja” blanca
                navigation: { nextEl: '#galleryMain .swiper-button-next', prevEl: '#galleryMain .swiper-button-prev' },
                pagination: { el: '#galleryPagination', clickable: true },
                thumbs: { swiper: thumbs },
                preloadImages: false,
                lazy: { loadPrevNext: true },
                watchSlidesProgress: true,
                observer: true,
                observeParents: true
            });

            setTimeout(() => { main.update(); thumbs.update(); }, 60);
            window.addEventListener('resize', () => { main.update(); thumbs.update(); }, { passive:true });
        });
    }

    async function renderCamper(c){
        // Título / precio / hero
        titleEl.textContent = `"${c.name}"`;
        priceEl.textContent = Number(c.price_per_night).toFixed(0);
        document.getElementById('seatsBadge').innerHTML = `<i class="bi bi-people"></i> ${c.seats || 4} seats`;
        hero.style.backgroundImage = `url('${imgUrl((c.images && c.images[0]) || "img/carousel/t3-azul-mar.webp")}')`;

        // Badges
        badges.innerHTML = '';
        (c.badges || []).forEach(b => {
            const span = document.createElement('span');
            span.className = 'badge-soft';
            span.textContent = b;
            badges.appendChild(span);
        });

        // Amenities
        amenitiesEl.innerHTML = '';
        (c.amenities || []).forEach(a => {
            const div = document.createElement('div');
            div.className = 'item';
            div.innerHTML = `<i class="bi bi-check-circle"></i><span>${a}</span>`;
            amenitiesEl.appendChild(div);
        });

        // Slides
        mainWrap.innerHTML = '';
        thumbWrap.innerHTML = '';
        const imgs = (c.images && c.images.length ? c.images : ['img/carousel/t3-azul-mar.webp']);
        imgs.forEach(src => { addSlide(mainWrap, src, c.name); addSlide(thumbWrap, src, `${c.name} thumbnail`); });

        // Inicializa Swiper una única vez y después de tener slides
        initSwipers();
    }

    // ------- Flatpickr -------
    const localeEN = {
        firstDayOfWeek: 1,
        weekdays: { shorthand:['Sun','Mon','Tue','Wed','Thu','Fri','Sat'], longhand:['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] },
        months: { shorthand:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], longhand:['January','February','March','April','May','June','July','August','September','October','November','December'] }
    };
    let fp;
    function initPicker(){
        const input = document.getElementById('pdDateRange');
        fp = flatpickr(input, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j M Y',
            showMonths: 2,
            disableMobile: true,
            allowInput: false,
            locale: localeEN,
            defaultDate: (start && end) ? [parseYMD(start), parseYMD(end)] : null,
            onClose(selectedDates){
                if (selectedDates.length === 2){
                    start = ymdLocal(selectedDates[0]);
                    end   = ymdLocal(selectedDates[1]);
                    rf_start.value = start;
                    rf_end.value   = end;
                    updateTotals();
                    updateBackLink();
                }
            }
        });
    }

    function updateTotals(){
        if (start && end){
            const n = nightsBetween(parseYMD(start), parseYMD(end));
            const price = Number(priceEl.textContent || 0);
            const total = n * price;
            nightsText.innerHTML = n > 0
                ? `<span class="pd-total">${n} night${n>1?'s':''}</span> · Total: <span class="pd-total">${total}€</span>`
                : 'Select dates to see total.';
        } else {
            nightsText.textContent = 'Select dates to see total.';
        }
    }

    function updateBackLink(){
        const url = new URL(PREFIX + '/buscar.php', location.origin);
        if (start && end){ url.searchParams.set('start', start); url.searchParams.set('end', end); }
        btnBack.href = url.toString();
    }

    // ------- Reserva -------
    function hookReserve(){
        const modal = new bootstrap.Modal(document.getElementById('reserveModal'));
        btnReserve.addEventListener('click', () => {
            if (!start || !end) { fp.open(); return; }
            rf_start.value = start;
            rf_end.value   = end;
            modal.show();
        });

        reserveForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                camper_id: +rf_camper_id.value,
                start: rf_start.value,
                end: rf_end.value,
                name: document.getElementById('rf_name').value.trim(),
                email: document.getElementById('rf_email').value.trim(),
                phone: document.getElementById('rf_phone').value.trim()
            };
            try{
                const res = await fetch(PREFIX + '/api/create-checkout.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.ok && data.url) location.href = data.url;
                else alert(data.error || 'Could not start checkout.');
            } catch(err){
                console.error(err);
                alert('Network error.');
            }
        });
    }

    // ------- Init -------
    (async function(){
        updateBackLink();
        const camper = await fetchCamper();
        await renderCamper(camper);   // ← crea slides y luego initSwipers()
        initPicker();
        updateTotals();
        hookReserve();
    })();

})();
