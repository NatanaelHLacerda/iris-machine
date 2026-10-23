/* Gestão CRM — comportamento das telas (sem dependências). */

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

export const csrfToken = () => $('meta[name="csrf-token"]')?.content ?? '';

/* ---------- Menu mobile ---------- */
function initMenu() {
    const shell = $('[data-shell]');
    const scrim = $('[data-menu-close]');
    if (!shell || !scrim) return;

    const toggle = (open) => {
        shell.classList.toggle('is-menu-open', open);
        scrim.hidden = !open;
    };

    $$('[data-menu-open]').forEach((button) => button.addEventListener('click', () => toggle(true)));
    scrim.addEventListener('click', () => toggle(false));
    document.addEventListener('keydown', (event) => event.key === 'Escape' && toggle(false));
}

/* ---------- Diálogos: [data-dialog-open="id"] abre, [data-dialog-close] fecha ---------- */
function initDialogs() {
    $$('[data-dialog-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => document.getElementById(trigger.dataset.dialogOpen)?.showModal());
    });
    $$('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });
}

/* ---------- Confirmação antes de enviar formulários destrutivos ---------- */
function initConfirm() {
    $$('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
}

/* ---------- Selects que enviam o formulário ao mudar (filtros) ---------- */
function initAutoSubmit() {
    $$('[data-autosubmit]').forEach((field) => field.addEventListener('change', () => field.form?.requestSubmit()));
}

/* ---------- Copiar texto ---------- */
function initCopy() {
    $$('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText($(button.dataset.copy)?.textContent.trim() ?? '');
                const label = button.textContent;
                button.textContent = 'Copiado';
                setTimeout(() => (button.textContent = label), 1500);
            } catch {
                /* sem permissão de clipboard: o texto continua visível para copiar à mão */
            }
        });
    });
}

export function toast(message) {
    const element = document.createElement('div');
    element.className = 'toast';
    element.setAttribute('role', 'alert');
    element.textContent = message;
    document.body.append(element);
    setTimeout(() => element.remove(), 4000);
}

initMenu();
initDialogs();
initConfirm();
initAutoSubmit();
initCopy();
