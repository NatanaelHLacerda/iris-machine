/* ==========================================================================
   Origem do visitante (REQ-002) e eventos do funil (REQ-003).
   Tudo de primeira parte e anônimo: nenhum cookie, nenhum dado pessoal.
   ========================================================================== */

const ATTRIBUTION_KEY = 'iris_ft';
const VISITOR_KEY = 'iris_vid';
const FIRST_TOUCH_DAYS = 30;
const UTM_FIELDS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid'];

const memory = {};

function read(storage, key) {
    try {
        return window[storage].getItem(key);
    } catch {
        return memory[`${storage}:${key}`] ?? null;
    }
}

function write(storage, key, value) {
    try {
        window[storage].setItem(key, value);
    } catch {
        memory[`${storage}:${key}`] = value;
    }
}

function uuid() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

export function getVisitorId() {
    let id = read('localStorage', VISITOR_KEY);
    if (!id) {
        id = uuid();
        write('localStorage', VISITOR_KEY, id);
    }
    return id;
}

function externalReferrer() {
    try {
        const referrer = document.referrer && new URL(document.referrer);
        return referrer && referrer.host !== location.host ? referrer.href : null;
    } catch {
        return null;
    }
}

/**
 * Origem first-touch: a primeira visita com UTM (ou a primeira de todas) vale por 30 dias.
 * Uma nova chegada com UTM substitui a anterior quando ela já expirou ou não tinha UTM.
 */
export function getAttribution() {
    const params = new URLSearchParams(location.search);
    const fromUrl = Object.fromEntries(UTM_FIELDS.map((field) => [field, params.get(field)]).filter(([, value]) => value));
    const hasUtm = Object.keys(fromUrl).length > 0;

    let saved = null;
    try {
        saved = JSON.parse(read('localStorage', ATTRIBUTION_KEY) ?? 'null');
    } catch {
        saved = null;
    }

    const expired = saved && Date.now() - saved.at > FIRST_TOUCH_DAYS * 864e5;
    const savedHasUtm = saved && UTM_FIELDS.some((field) => saved[field]);

    if (!saved || expired || (hasUtm && !savedHasUtm)) {
        saved = { ...fromUrl, referrer: externalReferrer(), landing_path: location.pathname, at: Date.now() };
        write('localStorage', ATTRIBUTION_KEY, JSON.stringify(saved));
    }

    return saved;
}

/** Preenche os campos ocultos do formulário de lead com a origem e o visitante. */
export function fillAttributionFields(form) {
    const attribution = { ...getAttribution(), visitor_id: getVisitorId() };
    form.querySelectorAll('[data-attribution]').forEach((input) => {
        input.value = attribution[input.dataset.attribution] ?? '';
    });
}

/* ---------- Eventos do funil (REQ-003) ---------- */

const SESSION_KEY = 'iris_sid';
const ENDPOINT = '/e';
const queue = [];

function getSessionId() {
    let id = read('sessionStorage', SESSION_KEY);
    if (!id) {
        id = uuid();
        write('sessionStorage', SESSION_KEY, id);
    }
    return id;
}

function shouldTrack() {
    return !(navigator.globalPrivacyControl === true || navigator.doNotTrack === '1' || window.doNotTrack === '1');
}

function flush() {
    if (queue.length === 0) return;

    const { utm_source, utm_campaign, utm_content, utm_term, fbclid, referrer } = getAttribution();
    const body = JSON.stringify({
        visitor_id: getVisitorId(),
        session_id: getSessionId(),
        attribution: { utm_source, utm_campaign, utm_content, utm_term, fbclid, referrer },
        events: queue.splice(0, 20),
    });

    try {
        const blob = new Blob([body], { type: 'application/json' });
        if (!navigator.sendBeacon?.(ENDPOINT, blob)) {
            fetch(ENDPOINT, { method: 'POST', body, keepalive: true, headers: { 'Content-Type': 'application/json' } }).catch(() => {});
        }
    } catch {
        /* tracking nunca pode quebrar a página */
    }

    if (queue.length > 0) flush();
}

/** Registra um evento; `once` limita a uma vez por sessão. */
export function track(type, target = null, { once = false } = {}) {
    if (!shouldTrack()) return;

    if (once) {
        const key = `iris_once_${type}`;
        if (read('sessionStorage', key)) return;
        write('sessionStorage', key, '1');
    }

    queue.push({ type, target, path: location.pathname });
}

export function initTracking() {
    if (!shouldTrack()) return;

    track('page_view');

    document.addEventListener('click', (event) => {
        const cta = event.target.closest('[data-track], [data-plan]');
        if (!cta) return;
        track('cta_click', cta.dataset.track ?? `plan:${cta.dataset.plan}`);
    });

    const form = document.querySelector('[data-lead-form]');
    const section = document.querySelector('#agendar');

    if (section && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                track('form_view', null, { once: true });
                observer.disconnect();
            }
        }, { threshold: 0.35 });
        observer.observe(section);
    }

    form?.addEventListener('focusin', () => track('form_start', null, { once: true }));
    form?.addEventListener('lead:sent', () => {
        track('form_submit');
        flush();
    });

    document.addEventListener('visibilitychange', () => document.visibilityState === 'hidden' && flush());
    window.addEventListener('pagehide', flush);
    setInterval(flush, 5000);
}
