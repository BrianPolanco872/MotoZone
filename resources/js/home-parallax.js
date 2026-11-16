const hero = document.querySelector('[data-home-hero]');
const parallaxBg = hero?.querySelector('[data-home-parallax]');

if (hero && parallaxBg && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    let ticking = false;

    const updateParallax = () => {
        const rect = hero.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        // Only compute if hero is partially or fully visible in viewport
        if (rect.bottom > 0 && rect.top < windowHeight) {
            // How much user has scrolled past the top of the hero:
            const scrollDistance = -rect.top;
            // Move background smoothly at ~35% of scroll speed
            const translateY = Math.max(-120, Math.min(200, scrollDistance * 0.35));
            parallaxBg.style.transform = `translate3d(0, ${translateY}px, 0)`;
        }

        ticking = false;
    };

    const onScrollOrResize = () => {
        if (!ticking) {
            window.requestAnimationFrame(updateParallax);
            ticking = true;
        }
    };

    window.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize, { passive: true });
    updateParallax();
}
