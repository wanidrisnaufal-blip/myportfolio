(() => {
  const carousel = document.querySelector('[data-project-carousel]');
  if (!carousel) return;

  const slides = [...carousel.querySelectorAll('[data-project-slide]')];
  if (!slides.length) return;

  const detail = {
    category: carousel.querySelector('[data-project-category]'),
    name: carousel.querySelector('[data-project-name]'),
    status: carousel.querySelector('[data-project-status]'),
    description: carousel.querySelector('[data-project-description]'),
    role: carousel.querySelector('[data-project-role]'),
    tags: carousel.querySelector('[data-project-tags]'),
  };
  const pagination = carousel.querySelector('[data-project-pagination]');
  const stage = carousel.querySelector('.project-stage');
  const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  let active = 0;
  let touchStart = null;
  let suppressClickUntil = 0;
  const dots = slides.map((slide, index) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'project-dot';
    dot.setAttribute('aria-label', `Show project ${index + 1}: ${slide.dataset.name || 'Project'}`);
    dot.addEventListener('click', () => select(index));
    pagination.append(dot);
    return dot;
  });

  function renderDetails(slide) {
    detail.category.textContent = slide.dataset.category || 'Project';
    detail.name.textContent = slide.dataset.name || '';
    detail.status.textContent = slide.dataset.status || '';
    detail.description.textContent = slide.dataset.description || '';
    detail.role.hidden = !slide.dataset.role;
    detail.role.querySelector('span').textContent = slide.dataset.role || '';

    detail.tags.replaceChildren();
    (slide.dataset.technologies || '').split(',').map((tag) => tag.trim()).filter(Boolean).forEach((tag) => {
      const chip = document.createElement('span');
      chip.textContent = tag;
      detail.tags.append(chip);
    });

    const openLink = slide.querySelector('[data-project-open]');
    const href = slide.dataset.demo || '';
    const safeHref = /^(https?:\/\/|\/|\.\.?\/|#)/i.test(href) && !/^javascript:/i.test(href);
    openLink.hidden = !safeHref;
    if (safeHref) openLink.href = href;
    else openLink.removeAttribute('href');
  }

  function select(index) {
    active = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      const distance = (i - active + slides.length) % slides.length;
      const state = distance === 0 ? 'active' : distance === 1 ? 'next' : distance === slides.length - 1 ? 'previous' : 'hidden';
      slide.dataset.state = reduceMotion && state !== 'active' ? 'hidden' : state;
      slide.classList.toggle('is-active', state === 'active');
      slide.setAttribute('aria-current', state === 'active' ? 'true' : 'false');
      slide.tabIndex = reduceMotion ? (state === 'active' ? 0 : -1) : (state === 'hidden' ? -1 : 0);
    });
    dots.forEach((dot, i) => {
      const selected = i === active;
      dot.classList.toggle('is-active', selected);
      dot.setAttribute('aria-pressed', selected ? 'true' : 'false');
      dot.tabIndex = selected ? 0 : -1;
    });
    renderDetails(slides[active]);
    detail.name.classList.remove('detail-enter');
    if (!reduceMotion) requestAnimationFrame(() => detail.name.classList.add('detail-enter'));
  }

  slides.forEach((slide, i) => {
    slide.addEventListener('click', (event) => {
      if (Date.now() < suppressClickUntil) { event.preventDefault(); return; }
      if (!event.target.closest('[data-project-open]')) select(i);
    });
    slide.addEventListener('keydown', (event) => {
      if (event.target.closest('[data-project-open]')) return;
      if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); select(i); }
    });
  });
  carousel.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') { event.preventDefault(); select(active - 1); }
    if (event.key === 'ArrowRight') { event.preventDefault(); select(active + 1); }
  });
  if (stage && ('ontouchstart' in window || navigator.maxTouchPoints > 0)) {
    stage.addEventListener('touchstart', (event) => {
      const touch = event.changedTouches[0];
      touchStart = { x: touch.clientX, y: touch.clientY, id: touch.identifier };
    }, { passive: true });
    document.addEventListener('touchend', (event) => {
      if (!touchStart) return;
      const touch = [...event.changedTouches].find((item) => item.identifier === touchStart.id);
      if (!touch) return;
      const dx = touch.clientX - touchStart.x;
      const dy = touch.clientY - touchStart.y;
      touchStart = null;
      if (Math.abs(dx) < 28 || Math.abs(dx) < Math.abs(dy) * 1.1) return;
      suppressClickUntil = Date.now() + 650;
      select(active + (dx < 0 ? 1 : -1));
    }, { passive: true });
    document.addEventListener('touchcancel', () => { touchStart = null; }, { passive: true });
  } else if (stage && window.PointerEvent) {
    stage.addEventListener('pointerdown', (event) => {
      if (event.pointerType === 'mouse') return;
      touchStart = { x: event.clientX, y: event.clientY, id: event.pointerId };
    }, { passive: true });
    document.addEventListener('pointerup', (event) => {
      if (!touchStart || event.pointerId !== touchStart.id) return;
      const dx = event.clientX - touchStart.x;
      const dy = event.clientY - touchStart.y;
      touchStart = null;
      if (Math.abs(dx) < 28 || Math.abs(dx) < Math.abs(dy) * 1.1) return;
      suppressClickUntil = Date.now() + 650;
      select(active + (dx < 0 ? 1 : -1));
    }, { passive: true });
    document.addEventListener('pointercancel', () => { touchStart = null; }, { passive: true });
  }
  if (slides.length < 2) {
    pagination.hidden = true;
  }

  // Return the gallery to its opening project when the user comes back to it.
  const projectSection = document.querySelector('.projects-section');
  if (projectSection && 'IntersectionObserver' in window) {
    let hasLeftProjects = false;
    const projectObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          hasLeftProjects = true;
        } else if (hasLeftProjects) {
          hasLeftProjects = false;
          select(0);
          slides.forEach((slide) => {
            slide.style.setProperty('--tilt-x', '0deg');
            slide.style.setProperty('--tilt-y', '0deg');
          });
        }
      });
    }, { threshold: .08 });
    projectObserver.observe(projectSection);
  }

  if (matchMedia('(hover: hover) and (pointer: fine)').matches && !reduceMotion) {
    slides.forEach((slide) => {
      slide.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'mouse') return;
        const rect = slide.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width - 0.5;
        const y = (event.clientY - rect.top) / rect.height - 0.5;
        slide.style.setProperty('--tilt-x', `${-y * 7}deg`);
        slide.style.setProperty('--tilt-y', `${x * 9}deg`);
      });
      slide.addEventListener('pointerleave', () => {
        slide.style.setProperty('--tilt-x', '0deg');
        slide.style.setProperty('--tilt-y', '0deg');
      });
    });
  }

  select(0);
})();
