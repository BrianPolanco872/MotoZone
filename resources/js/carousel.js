document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    if (!track) return;
    const slides = Array.from(track.children);
    if (!slides.length) return;

    const prefersReducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

    const getCurrentIndex = () => {
        const slideWidth = track.clientWidth || 1;
        return Math.round(track.scrollLeft / slideWidth);
    };

    const moveTo = (targetIndex) => {
        const slideWidth = track.clientWidth;
        const clampedIndex = (targetIndex + slides.length) % slides.length;
        track.scrollTo({ left: clampedIndex * slideWidth, behavior: 'smooth' });
    };

    carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => {
        moveTo(getCurrentIndex() - 1);
    });

    carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => {
        moveTo(getCurrentIndex() + 1);
    });

    if (carousel.dataset.carouselEffect === 'depth' && !prefersReducedMotion) {
        const updateDepth = () => {
            const slideWidth = track.clientWidth;
            if (!slideWidth) return;

            slides.forEach((slide, index) => {
                const distance = (index * slideWidth - track.scrollLeft) / slideWidth;
                const rotation = Math.max(-14, Math.min(14, distance * 14));
                const scale = 1 - Math.min(Math.abs(distance) * 0.035, 0.035);

                slide.style.setProperty('--carousel-rotate-y', `${rotation}deg`);
                slide.style.setProperty('--carousel-scale', scale);
            });
        };

        updateDepth();
        track.addEventListener('scroll', updateDepth, { passive: true });
        window.addEventListener('resize', updateDepth);
    }

    const delay = Number(carousel.dataset.carouselAuto);
    if (!delay || slides.length < 2 || prefersReducedMotion) return;

    let timer = null;

    const advance = () => {
        const nextIndex = (getCurrentIndex() + 1) % slides.length;
        moveTo(nextIndex);
    };

    const startAutoPlay = () => {
        if (!timer) {
            timer = setInterval(advance, delay);
        }
    };

    const stopAutoPlay = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    };

    startAutoPlay();

    carousel.addEventListener('pointerenter', stopAutoPlay);
    carousel.addEventListener('pointerleave', startAutoPlay);
    carousel.addEventListener('focusin', stopAutoPlay);
    carousel.addEventListener('focusout', startAutoPlay);
    carousel.addEventListener('touchstart', stopAutoPlay, { passive: true });
    carousel.addEventListener('touchend', startAutoPlay, { passive: true });
});
