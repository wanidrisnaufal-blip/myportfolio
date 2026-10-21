(() => {
  const items = [...document.querySelectorAll('[data-reveal]')].filter((item) => !item.closest('.timeline-section'));
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const journeySection = document.querySelector('.timeline-section');
  if (!('IntersectionObserver' in window) || reducedMotion) {
    items.forEach((item) => item.classList.add('is-visible'));
    journeySection?.classList.add('is-background-visible');
    journeySection?.querySelectorAll('[data-reveal]').forEach((item) => item.classList.add('is-visible'));
    return;
  }

  if (journeySection) {
    const journeyText = journeySection.querySelectorAll('.section-heading[data-reveal], .timeline-item[data-reveal]');
    journeyText.forEach((item, index) => {
      if (item.classList.contains('timeline-item')) item.style.setProperty('--journey-reveal-delay', `${index * 140}ms`);
    });
    const journeyTextObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        journeyTextObserver.unobserve(entry.target);
      });
    }, { rootMargin: '-10% 0px -24% 0px', threshold: .05 });
    journeyText.forEach((item) => journeyTextObserver.observe(item));

    const backgroundObserver = new IntersectionObserver(([entry]) => {
      journeySection.classList.toggle('is-background-visible', entry.isIntersecting);
      if (!entry.isIntersecting) {
        journeyText.forEach((item) => {
          item.classList.remove('is-visible');
          journeyTextObserver.unobserve(item);
          journeyTextObserver.observe(item);
        });
      }
    }, { threshold: .02 });
    backgroundObserver.observe(journeySection);
  }

  // Reveal each section on entry and clear it on exit so it can replay on return.
  // The orbit animation is independent and keeps its current rotation.
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      entry.target.classList.toggle('is-visible', entry.isIntersecting);
    });
  }, { threshold: .08 });
  items.forEach((item) => observer.observe(item));
})();

(() => {
  const surfaces = document.querySelectorAll('.about-content, .skill-group, .timeline-item, .contact-section');
  surfaces.forEach((surface) => {
    surface.classList.add('interaction-surface');
    surface.addEventListener('pointermove', (event) => {
      if (event.pointerType === 'touch') return;
      const bounds = surface.getBoundingClientRect();
      surface.style.setProperty('--surface-x', `${event.clientX - bounds.left}px`);
      surface.style.setProperty('--surface-y', `${event.clientY - bounds.top}px`);
    });
    surface.addEventListener('pointerleave', () => {
      surface.style.setProperty('--surface-x', '50%');
      surface.style.setProperty('--surface-y', '50%');
    });
  });
})();

(() => {
  const items = document.querySelectorAll('.timeline-item');
  if (!items.length || !('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      entry.target.classList.toggle('is-current', entry.isIntersecting);
    });
  }, { rootMargin: '-38% 0px -38% 0px', threshold: .01 });
  items.forEach((item) => observer.observe(item));
})();
