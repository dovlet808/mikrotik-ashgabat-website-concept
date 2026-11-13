/*
 | Progressive enhancement only: every page works without JavaScript.
 | No frameworks on the public site — this file is the whole client bundle.
 */

document.documentElement.classList.add('js');

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initMobileMenu() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const panel = document.getElementById('mobile-menu');
    if (!toggle || !panel) return;

    const closeButtons = panel.querySelectorAll('[data-menu-close]');

    const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', String(open));
        panel.hidden = !open;
        document.body.classList.toggle('no-scroll', open);
        if (open) {
            panel.querySelector('a, button')?.focus();
        } else {
            toggle.focus();
        }
    };

    toggle.addEventListener('click', (event) => {
        event.preventDefault();
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });
    closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
    panel.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) setOpen(false);
    });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (query) => {
        if (query.matches && !panel.hidden) setOpen(false);
    });
}

function initHeader() {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;
    const update = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    update();
    window.addEventListener('scroll', update, { passive: true });
}

function initGallery() {
    const gallery = document.querySelector('[data-gallery]');
    if (!gallery) return;

    const main = gallery.querySelector('[data-gallery-main]');
    const counter = gallery.querySelector('[data-gallery-counter]');
    const thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', () => {
            main.src = thumb.dataset.src;
            main.srcset = thumb.dataset.srcset || '';
            main.alt = thumb.dataset.alt || '';
            if (thumb.dataset.width && thumb.dataset.height) {
                main.width = Number(thumb.dataset.width);
                main.height = Number(thumb.dataset.height);
            }
            thumbs.forEach((other) => other.setAttribute('aria-current', String(other === thumb)));
            if (counter) counter.textContent = String(index + 1).padStart(2, '0');
        });
    });
}

function initAutoSubmit() {
    document.querySelectorAll('[data-autosubmit]').forEach((form) => {
        form.querySelectorAll('select, input[type="radio"]').forEach((input) => {
            input.addEventListener('change', () => form.requestSubmit());
        });
        form.querySelectorAll('[data-autosubmit-hide]').forEach((el) => {
            el.hidden = true;
        });
    });
}

function initCollapsibleFilters() {
    if (window.matchMedia('(min-width: 1024px)').matches) return;
    document.querySelectorAll('details[data-collapse-mobile]:not([data-keep-open])').forEach((details) => {
        details.open = false;
    });
}

function initReveal() {
    const items = document.querySelectorAll('[data-reveal]');
    if (!items.length) return;

    if (reduceMotion || !('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );

    items.forEach((item) => observer.observe(item));
}

function initForms() {
    document.querySelectorAll('form[data-inquiry-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;
            window.setTimeout(() => {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
            }, 0);
        });
    });

    document.querySelectorAll('[data-contact-choice]').forEach((group) => {
        const email = document.getElementById(group.dataset.contactChoice);
        if (!email) return;
        const sync = () => {
            const chosen = group.querySelector('input:checked')?.value;
            email.required = chosen === 'email';
        };
        group.addEventListener('change', sync);
        sync();
    });
}

initMobileMenu();
initHeader();
initGallery();
initAutoSubmit();
initCollapsibleFilters();
initReveal();
initForms();
