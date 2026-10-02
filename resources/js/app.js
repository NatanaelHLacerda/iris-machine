import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

// Assinatura de movimento do site: uma curva, três durações.
const EASE = 'power3.out';
const DUR = { quick: 0.25, standard: 0.6, slow: 0.9 };

const root = document.documentElement;
const motion = root.classList.contains('motion');
const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];

window.__irisReady = true;

initNav();
initFaq();
initPlanLinks();
initLeadForm();

if (motion) {
    initGlow();
    initMagnetic();
    initIntro();
    initScrollReveals();
    initGalleryParallax();
    initTerminal();
}

/* ---------- Nav: fundo ao rolar + link da seção ativa ---------- */
function initNav() {
    const nav = document.querySelector('[data-nav]');
    if (!nav) return;

    ScrollTrigger.create({
        start: 24,
        end: 'max',
        onToggle: (self) => nav.classList.toggle('is-scrolled', self.isActive),
    });

    // Depois do hero o CTA ganha destaque; enquanto o formulário está na tela a barra do celular some.
    ScrollTrigger.create({
        trigger: '.hero',
        start: 'bottom top',
        onEnter: () => root.classList.add('is-past-hero'),
        onLeaveBack: () => root.classList.remove('is-past-hero'),
    });
    ScrollTrigger.create({
        trigger: '#agendar',
        start: 'top bottom',
        end: 'bottom top',
        onToggle: (self) => root.classList.toggle('is-at-lead', self.isActive),
    });

    $$('.nav__links a').forEach((link) => {
        const section = document.querySelector(link.getAttribute('href'));
        if (!section) return;
        ScrollTrigger.create({
            trigger: section,
            start: 'top 50%',
            end: 'bottom 50%',
            onToggle: (self) => link.classList.toggle('is-active', self.isActive),
        });
    });
}

/* ---------- Entrada da página ---------- */
function splitWords(element) {
    const wrap = (text) => {
        const fragment = document.createDocumentFragment();
        text.split(/(\s+)/).forEach((part) => {
            if (!part) return;
            if (/^\s+$/.test(part)) return fragment.append(' ');
            const outer = document.createElement('span');
            const inner = document.createElement('span');
            outer.className = 'word';
            inner.textContent = part;
            outer.append(inner);
            fragment.append(outer);
        });
        return fragment;
    };

    const walk = (node) => {
        [...node.childNodes].forEach((child) => {
            if (child.nodeType === Node.TEXT_NODE) child.replaceWith(wrap(child.textContent));
            else walk(child);
        });
    };

    element.setAttribute('aria-label', element.textContent.replace(/\s+/g, ' ').trim());
    walk(element);
    return $$('.word > span', element);
}

function initIntro() {
    const title = document.querySelector('[data-split]');
    const words = title ? splitWords(title) : [];
    const curtain = document.querySelector('.curtain');

    gsap.set(title, { autoAlpha: 1 });

    const tl = gsap.timeline({ defaults: { ease: EASE } });

    if (curtain) {
        tl.from(curtain.firstElementChild, { autoAlpha: 0, letterSpacing: '0.7em', duration: DUR.standard })
            .to(curtain, { yPercent: -100, duration: 0.7, ease: 'power3.inOut' }, '+=0.1')
            .set(curtain, { display: 'none' });
    }

    tl.from('.nav', { y: -24, autoAlpha: 0, duration: DUR.standard }, '-=0.35')
        .from(words, { yPercent: 110, duration: DUR.slow, stagger: 0.045 }, '<')
        .fromTo('[data-hero-item]', { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: DUR.standard, stagger: 0.09 }, '<0.25')
        .fromTo(
            '[data-gallery-item]',
            { autoAlpha: 0, y: 40, clipPath: 'inset(100% 0% 0% 0% round 18px)' },
            { autoAlpha: 1, y: 0, clipPath: 'inset(0% 0% 0% 0% round 18px)', duration: 1.1, stagger: 0.12, ease: 'power4.out', clearProps: 'clipPath' },
            '<',
        );
}

/* ---------- Reveals no scroll ---------- */
function initScrollReveals() {
    $$('[data-reveal]').forEach((el) => {
        gsap.fromTo(
            el,
            { autoAlpha: 0, y: 28 },
            { autoAlpha: 1, y: 0, duration: DUR.slow, ease: EASE, scrollTrigger: { trigger: el, start: 'top 85%', once: true } },
        );
    });

    gsap.set('[data-reveal-batch]', { autoAlpha: 0, y: 32 });
    ScrollTrigger.batch('[data-reveal-batch]', {
        start: 'top 88%',
        once: true,
        onEnter: (batch) =>
            gsap.to(batch, { autoAlpha: 1, y: 0, duration: DUR.standard, ease: EASE, stagger: 0.08, overwrite: true, clearProps: 'transform' }),
    });
}

/* ---------- Parallax da galeria (só desktop) ---------- */
function initGalleryParallax() {
    gsap.matchMedia().add('(min-width: 901px)', () => {
        $$('[data-gallery-item]').forEach((item) => {
            gsap.to(item.querySelector('img'), {
                yPercent: Number(item.dataset.speed) || 0,
                scale: 1.12,
                ease: 'none',
                scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: 0.6 },
            });
        });
    });
}

/* ---------- Terminal: digita os comandos, revela as saídas ---------- */
function initTerminal() {
    const terminal = document.querySelector('[data-terminal]');
    if (!terminal) return;

    const lines = $$('[data-line]', terminal);
    const tl = gsap.timeline({ paused: true });

    gsap.set(lines, { autoAlpha: 0 });

    lines.forEach((line) => {
        const target = line.querySelector('[data-text]');
        const text = target.textContent;

        if (line.dataset.line === 'cmd') {
            const state = { chars: 0 };
            tl.set(line, { autoAlpha: 1 })
                .call(() => (target.textContent = ''))
                .to(state, {
                    chars: text.length,
                    duration: Math.max(0.3, text.length * 0.05),
                    ease: 'none',
                    onUpdate: () => (target.textContent = text.slice(0, Math.round(state.chars))),
                })
                .to({}, { duration: 0.35 });
        } else {
            tl.fromTo(line, { autoAlpha: 0, x: -8 }, { autoAlpha: 1, x: 0, duration: 0.3, ease: EASE }).to({}, { duration: 0.4 });
        }
    });

    ScrollTrigger.create({ trigger: terminal, start: 'top 75%', once: true, onEnter: () => tl.play() });
}

/* ---------- Micro-interações ---------- */
function initGlow() {
    $$('[data-glow]').forEach((card) => {
        card.addEventListener('pointermove', (event) => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
            card.style.setProperty('--my', `${event.clientY - rect.top}px`);
        });
    });
}

function initMagnetic() {
    if (!matchMedia('(hover: hover) and (pointer: fine)').matches) return;

    $$('[data-magnetic]').forEach((el) => {
        const xTo = gsap.quickTo(el, 'x', { duration: 0.4, ease: EASE });
        const yTo = gsap.quickTo(el, 'y', { duration: 0.4, ease: EASE });

        el.addEventListener('pointermove', (event) => {
            const rect = el.getBoundingClientRect();
            xTo((event.clientX - rect.left - rect.width / 2) * 0.25);
            yTo((event.clientY - rect.top - rect.height / 2) * 0.35);
        });
        el.addEventListener('pointerleave', () => {
            xTo(0);
            yTo(0);
        });
    });
}

/* ---------- FAQ: abre/fecha com altura animada ---------- */
function initFaq() {
    $$('.faq__item').forEach((item) => {
        const summary = item.querySelector('summary');
        const answer = item.querySelector('.faq__answer');

        summary.addEventListener('click', (event) => {
            if (!motion) return; // <details> nativo já resolve
            event.preventDefault();

            if (item.open) {
                gsap.to(answer, { height: 0, duration: DUR.quick, ease: 'power2.in', onComplete: () => (item.open = false) });
            } else {
                item.open = true;
                gsap.fromTo(answer, { height: 0 }, { height: 'auto', duration: 0.4, ease: EASE, onComplete: () => ScrollTrigger.refresh() });
            }
        });
    });
}

/* ---------- "Quero esse" já deixa o formato selecionado no formulário ---------- */
function initPlanLinks() {
    const select = document.querySelector('#lead-plan');
    if (!select) return;

    $$('[data-plan]').forEach((link) => {
        link.addEventListener('click', () => (select.value = link.dataset.plan));
    });
}

/* ---------- Formulário só visual: o envio será ligado ao backend depois ---------- */
function initLeadForm() {
    const form = document.querySelector('[data-lead-form]');
    if (!form) return;

    form.addEventListener('submit', (event) => event.preventDefault());
}
