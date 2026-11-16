const hero = document.querySelector('[data-home-hero]');
const visual = hero?.querySelector('[data-home-parallax]');

if (hero && visual && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    let framePending = false;

    const update = () => {
        const { top, bottom } = hero.getBoundingClientRect();

        if (bottom > 0 && top < window.innerHeight) {
            const offset = Math.max(-64, Math.min(96, -top * 0.18));
            visual.style.transform = `translate3d(0, ${offset}px, 0) scale(1.04)`;
        }

        framePending = false;
    };

    const scheduleUpdate = () => {
        if (!framePending) {
            window.requestAnimationFrame(update);
            framePending = true;
        }
    };

    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate, { passive: true });
    scheduleUpdate();
}
