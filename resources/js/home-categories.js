const pills = [...document.querySelectorAll('[data-home-category-pill]')];
const cards = [...document.querySelectorAll('[data-home-category-card]')];
const resetButton = document.querySelector('[data-home-category-reset]');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const highlight = (index) => {
    cards.forEach((card) => card.classList.toggle('home-category-hit', card.dataset.homeCategoryCard === index));
    pills.forEach((pill) => pill.setAttribute('aria-pressed', String(pill.dataset.homeCategoryPill === index)));
    resetButton?.classList.toggle('hidden', index === null);
};

pills.forEach((pill) => {
    pill.addEventListener('click', () => {
        const index = pill.dataset.homeCategoryPill;

        if (pill.getAttribute('aria-pressed') === 'true') {
            highlight(null);

            return;
        }

        highlight(index);
        cards.find((card) => card.dataset.homeCategoryCard === index)?.scrollIntoView({ behavior: prefersReducedMotion.matches ? 'auto' : 'smooth', block: 'center' });
    });
});

resetButton?.addEventListener('click', () => highlight(null));
