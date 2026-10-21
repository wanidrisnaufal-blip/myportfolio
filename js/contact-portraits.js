(() => {
  const stack = document.querySelector('[data-contact-portrait]');
  if (!stack) return;

  const photos = Array.from(stack.querySelectorAll('[data-contact-photo]'));
  if (photos.length < 2) {
    stack.removeAttribute('role');
    stack.removeAttribute('tabindex');
    stack.removeAttribute('aria-label');
    const hint = stack.querySelector('.contact-photo-hint');
    if (hint) hint.hidden = true;
    return;
  }

  let activeIndex = 0;
  const paint = () => {
    photos.forEach((photo, index) => {
      const depth = (index - activeIndex + photos.length) % photos.length;
      const shown = depth < 3;
      photo.classList.toggle('is-active', depth === 0);
      photo.style.setProperty('--photo-depth', String(depth));
      photo.style.setProperty('--stack-x', `${depth * -8}px`);
      photo.style.setProperty('--stack-y', `${depth * 5}px`);
      photo.style.setProperty('--stack-rotate', depth === 0 ? '5deg' : `${-3 - (depth - 1) * 5}deg`);
      photo.style.setProperty('--stack-opacity', shown ? '1' : '0');
      photo.style.setProperty('--stack-visibility', shown ? 'visible' : 'hidden');
      photo.setAttribute('aria-hidden', depth === 0 ? 'false' : 'true');
    });
    stack.setAttribute('aria-label', `Portrait ${activeIndex + 1} of ${photos.length}. Activate to show the next photo.`);
  };

  const advance = () => {
    activeIndex = (activeIndex + 1) % photos.length;
    paint();
  };

  stack.addEventListener('click', advance);
  stack.addEventListener('keydown', event => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      advance();
    } else if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
      event.preventDefault();
      activeIndex = (activeIndex + (event.key === 'ArrowRight' ? 1 : photos.length - 1)) % photos.length;
      paint();
    }
  });

  paint();
})();
