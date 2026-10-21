(() => {
  const gallery = document.querySelector('.gallery-section');
  if (!gallery) return;

  const track = gallery.querySelector('.gallery-scroll-track');
  const steps = [...gallery.querySelectorAll('[data-gallery-step]')];
  const images = [...gallery.querySelectorAll('[data-gallery-image]')];
  const info = [...gallery.querySelectorAll('[data-gallery-info]')];
  const buttons = [...gallery.querySelectorAll('[data-gallery-button]')];
  const progress = gallery.querySelector('.gallery-progress');
  if (!track || !steps.length || !images.length || !info.length || !progress) return;

  let activeIndex = 0;
  let scheduled = false;

  const activate = (index) => {
    if (index === activeIndex) return;
    activeIndex = index;
    images.forEach((image, i) => {
      const active = i === index;
      image.classList.toggle('is-active', active);
      image.setAttribute('aria-hidden', String(!active));
    });
    info.forEach((item, i) => {
      const active = i === index;
      item.classList.toggle('is-active', active);
      item.setAttribute('aria-hidden', String(!active));
    });
    buttons.forEach((button, i) => button.setAttribute('aria-current', String(i === index)));
  };

  const update = () => {
    scheduled = false;
    const first = steps[0].getBoundingClientRect();
    const last = steps[steps.length - 1].getBoundingClientRect();
    const viewportCenter = window.innerHeight / 2;
    const start = first.top + first.height / 2;
    const end = last.top + last.height / 2;
    const progressValue = end > start
      ? Math.max(0, Math.min(1, (viewportCenter - start) / (end - start)))
      : 0;
    progress.style.setProperty('--gallery-progress', `${(progressValue * 100).toFixed(2)}%`);
    activate(Math.min(steps.length - 1, Math.round(progressValue * (steps.length - 1))));
  };

  const requestUpdate = () => {
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(update);
  };

  buttons.forEach((button, index) => {
    button.addEventListener('click', () => {
      steps[index]?.scrollIntoView({
        behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'center'
      });
    });
  });

  window.addEventListener('scroll', requestUpdate, { passive: true });
  window.addEventListener('resize', requestUpdate, { passive: true });
  update();
})();
