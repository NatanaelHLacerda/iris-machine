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

/* ---------- Etapa na ficha: mostra motivo/data só quando fazem sentido ---------- */
function initStatusForm() {
    const form = $('[data-status-form]');
    if (!form) return;

    const select = form.querySelector('[name="status"]');
    const sync = () => $$('[data-show-when]', form).forEach((field) => (field.hidden = field.dataset.showWhen !== select.value));
    select.addEventListener('change', sync);
    sync();
}

/* ---------- Kanban: arrastar muda a etapa; Perdido pede motivo; Fechado abre a venda ---------- */
function askLostReason() {
    const dialog = $('#lost-dialog');
    const form = $('[data-lost-form]');
    form.reset();
    dialog.showModal();

    return new Promise((resolve) => {
        dialog.addEventListener('close', () => {
            resolve(dialog.returnValue === 'confirm' ? Object.fromEntries(new FormData(form)) : null);
        }, { once: true });
    });
}

function updateCounts(board) {
    $$('[data-column]', board).forEach((column) => {
        $('[data-count]', column).textContent = $$('[data-card]', column).length;
    });
}

function initBoard() {
    const board = $('[data-board]');
    if (!board) return;

    let dragged = null;

    board.addEventListener('dragstart', (event) => {
        dragged = event.target.closest('[data-card]');
        if (!dragged) return;
        dragged.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    board.addEventListener('dragend', () => {
        dragged?.classList.remove('is-dragging');
        $$('.column.is-over', board).forEach((column) => column.classList.remove('is-over'));
    });

    $$('[data-column]', board).forEach((column) => {
        column.addEventListener('dragover', (event) => {
            event.preventDefault();
            column.classList.add('is-over');
        });
        column.addEventListener('dragleave', () => column.classList.remove('is-over'));
        column.addEventListener('drop', async (event) => {
            event.preventDefault();
            column.classList.remove('is-over');
            if (!dragged) return;

            const card = dragged;
            const origin = card.closest('[data-column]');
            const status = column.dataset.column;
            if (origin === column) return;

            if (status === 'fechado') {
                window.location.href = `${card.dataset.showUrl}#venda`;
                return;
            }

            const payload = { status };
            if (status === 'perdido') {
                const answer = await askLostReason();
                if (!answer) return;
                Object.assign(payload, answer);
            }

            $('.column__body', column).prepend(card);
            updateCounts(board);

            try {
                const response = await fetch(card.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                    body: JSON.stringify(payload),
                });
                if (!response.ok) {
                    const body = await response.json().catch(() => ({}));
                    throw new Error(body.message ?? 'Não foi possível mover o lead.');
                }
            } catch (error) {
                $('.column__body', origin).prepend(card);
                updateCounts(board);
                toast(error.message);
            }
        });
    });
}

initStatusForm();
initBoard();
