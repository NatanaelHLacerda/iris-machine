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
