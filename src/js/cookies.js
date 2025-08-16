// js/cookies.js
(() => {
    const KEY = "cc_v1"; // storage key

    const $ = sel => document.querySelector(sel);
    const banner = $("#cc-banner");
    const modal  = $("#cc-modal");
    const chkPref = $("#cc-pref");
    const chkAnalytics = $("#cc-analytics");

    // Public API
    const api = {
        openSettings(){ openModal(); },
        has(cat){ const c = read(); return !!(c && c[cat]); },
        onChange(fn){ listeners.push(fn); }
    };
    window.cookieConsent = api;

    const listeners = [];

    function read(){
        try { return JSON.parse(localStorage.getItem(KEY) || ""); } catch { return null; }
    }
    function save(consent){
        localStorage.setItem(KEY, JSON.stringify({ ...consent, ts: Date.now() }));
        apply(consent);
        listeners.forEach(fn => { try{ fn(consent); }catch{} });
    }

    function showBanner(){ banner.hidden = false; }
    function hideBanner(){ banner.hidden = true; }
    function openModal(){
        // precarga switches desde storage
        const c = read() || {};
        chkPref.checked = !!c.preferences;
        chkAnalytics.checked = !!c.analytics;
        modal.hidden = false;
    }
    function closeModal(){ modal.hidden = true; }

    // Activa scripts con type="text/plain" y data-cc="analytics|preferences"
    function enableScripts(category){
        document.querySelectorAll(`script[type="text/plain"][data-cc="${category}"]`).forEach(s => {
            const scr = document.createElement("script");
            // Copia atributos relevantes
            [...s.attributes].forEach(a => {
                if (a.name === "type" || a.name === "data-cc" || a.name === "data-src") return;
                scr.setAttribute(a.name, a.value);
            });
            // src: del propio src o de data-src
            const src = s.getAttribute("src") || s.getAttribute("data-src");
            if (src) scr.src = src;
            if (s.textContent && !src) scr.textContent = s.textContent;
            s.replaceWith(scr);
        });
    }

    function apply(consent){
        hideBanner();
        // activa según categorías
        if (consent.preferences) enableScripts("preferences");
        if (consent.analytics)   enableScripts("analytics");
    }

    // Botones banner
    banner?.addEventListener("click", (e) => {
        const a = e.target.closest("[data-cc]");
        if (!a) return;
        const action = a.dataset.cc;
        if (action === "accept") {
            save({ preferences:true, analytics:true });
        } else if (action === "reject") {
            save({ preferences:false, analytics:false });
        } else if (action === "customize") {
            openModal();
        }
    });

    // Botones modal
    modal?.addEventListener("click", (e) => {
        const a = e.target.closest("[data-cc]");
        if (!a) return;
        if (a.dataset.cc === "modal-cancel") { closeModal(); return; }
        if (a.dataset.cc === "modal-save") {
            save({ preferences: chkPref.checked, analytics: chkAnalytics.checked });
            closeModal();
        }
    });

    // Inicio
    document.addEventListener("DOMContentLoaded", () => {
        const consent = read();
        if (consent) apply(consent);
        else showBanner();
    });
})();
