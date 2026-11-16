document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    if (!track) return;
    const move = (direction) => track.scrollBy({ left: direction * track.clientWidth, behavior: 'smooth' });
    carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => move(-1));
    carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => move(1));

    const delay = Number(carousel.dataset.carouselAuto);
    const slides = track.children.length;
    if (!delay || slides < 2 || matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let index = 0;
    const advance = () => {
        index = (index + 1) % slides;
        track.scrollTo({ left: index * track.clientWidth, behavior: 'smooth' });
    };

    let timer = setInterval(advance, delay);
    const stop = () => clearInterval(timer);
    const start = () => {
        stop();
        timer = setInterval(advance, delay);
    };

    carousel.addEventListener('pointerenter', stop);
    carousel.addEventListener('pointerleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);
});
