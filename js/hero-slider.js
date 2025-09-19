(function () {
    const slider = document.querySelector('[data-hero-slider]');
    const dots = Array.from(document.querySelectorAll('[data-hero-dot]'));

    if (!slider || dots.length === 0) {
        return;
    }

    let currentIndex = 0;
    let intervalId;

    function goToSlide(index) {
        currentIndex = index;
        const offset = index * -100;
        slider.style.transform = `translateX(${offset}%)`;
        updateDots();
    }

    function updateDots() {
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === currentIndex);
        });
    }

    function nextSlide() {
        const target = (currentIndex + 1) % dots.length;
        goToSlide(target);
    }

    function startAutoPlay() {
        intervalId = setInterval(nextSlide, 5000);
    }

    function stopAutoPlay() {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
    }

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            stopAutoPlay();
            goToSlide(index);
            startAutoPlay();
        });
    });

    slider.addEventListener('mouseenter', stopAutoPlay);
    slider.addEventListener('mouseleave', startAutoPlay);

    goToSlide(0);
    startAutoPlay();
})();
