// js/estado-busqueda.js
(() => {
    const KEY = 'av:lastSearch';
    const TTL_MS = 7 * 24 * 60 * 60 * 1000; // 7 días

    function allowed() {
        try { return !window.cookieConsent || window.cookieConsent.allowed?.('functional') !== false; }
        catch { return true; }
    }
    function now(){ return Date.now(); }

    function saveLastSearch({ from, to, series = '' }) {
        if (!allowed()) return;
        if (!from || !to) return;
        const payload = { from, to, series, ts: now() };
        try { localStorage.setItem(KEY, JSON.stringify(payload)); } catch {}
    }

    function getLastSearch() {
        try {
            const raw = localStorage.getItem(KEY);
            if (!raw) return null;
            const obj = JSON.parse(raw);
            if (!obj || !obj.from || !obj.to || !obj.ts) return null;
            if (now() - obj.ts > TTL_MS) { localStorage.removeItem(KEY); return null; }
            return obj;
        } catch { return null; }
    }

    function clearLastSearch(){ try { localStorage.removeItem(KEY); } catch {} }

    function paramsFromUrl() {
        const u = new URL(location.href);
        const from = u.searchParams.get('start') || u.searchParams.get('from') || '';
        const to   = u.searchParams.get('end')   || u.searchParams.get('to')   || '';
        const series = u.searchParams.get('series') || '';
        return { from, to, series };
    }

    function pushParams({from, to, series}) {
        if (!from || !to) return;
        const u = new URL(location.href);
        u.searchParams.set('start', from);
        u.searchParams.set('end', to);
        if (series !== undefined) u.searchParams.set('series', series);
        u.searchParams.delete('from');
        u.searchParams.delete('to');
        history.replaceState(null, '', u.toString());
    }

    window.SearchState = { saveLastSearch, getLastSearch, clearLastSearch, paramsFromUrl, pushParams };
})();
