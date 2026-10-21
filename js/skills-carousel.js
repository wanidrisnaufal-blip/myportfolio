(() => {
  const viewport = document.querySelector('.skills-grid');
  const track = viewport?.querySelector('.skills-track');
  if (!viewport || !track) return;

  const cards = [...track.children];
  const mobileQuery = window.matchMedia('(max-width: 640px)');
  const toggleButtons = cards.map((card) => card.querySelector('.skill-toggle'));
  const mobileColumns = [0, 1].map(() => {
    const column = document.createElement('div');
    column.className = 'skill-mobile-column';
    return column;
  });
  let resizeObserver;
  let mode = null;

  const clearExpandedState = () => {
    cards.forEach((card, index) => {
      card.classList.remove('is-expanded');
      const button = toggleButtons[index];
      if (button) {
        button.setAttribute('aria-expanded', 'false');
        button.disabled = !mobileQuery.matches;
      }
    });
  };

  const setupMobile = () => {
    track.replaceChildren(...mobileColumns);
    cards.forEach((card, index) => mobileColumns[index % 2].append(card));
    track.classList.remove('is-looping');
    track.style.animationDuration = '';
    track.style.removeProperty('--skill-loop-distance');
    track.style.transform = '';
    viewport.classList.add('is-mobile-grid');
    track.classList.add('is-mobile-grid');
    cards.forEach((card, index) => {
      card.classList.remove('skill-slide-card', 'skill-slide-exit', 'skill-slide-enter');
      const button = toggleButtons[index];
      button.disabled = false;
      button.setAttribute('aria-expanded', 'false');
    });
  };

  const setupDesktop = () => {
    track.replaceChildren(...cards);
    track.classList.remove('is-looping');
    viewport.classList.remove('is-mobile-grid');
    track.classList.remove('is-mobile-grid');
    clearExpandedState();
    cards.forEach((card) => {
      card.classList.add('skill-slide-card');
      const clone = card.cloneNode(true);
      clone.removeAttribute('data-reveal');
      clone.setAttribute('aria-hidden', 'true');
      clone.classList.add('skill-slide-card');
      const cloneButton = clone.querySelector('.skill-toggle');
      if (cloneButton) cloneButton.disabled = true;
      track.append(clone);
    });

    const updateLoop = () => {
      const firstDuplicate = track.children[cards.length];
      if (!firstDuplicate) return;
      const distance = firstDuplicate.offsetLeft - track.firstElementChild.offsetLeft;
      track.style.setProperty('--skill-loop-distance', `${-distance}px`);
      track.style.animationDuration = `${distance / 56}s`;
      track.classList.add('is-looping');
    };
    resizeObserver?.disconnect();
    resizeObserver = new ResizeObserver(updateLoop);
    resizeObserver.observe(viewport);
    updateLoop();
  };

  const setup = () => {
    const nextMode = mobileQuery.matches ? 'mobile' : 'desktop';
    if (mode === nextMode) return;
    mode = nextMode;
    resizeObserver?.disconnect();
    if (mode === 'mobile') setupMobile();
    else setupDesktop();
  };

  cards.forEach((card, index) => {
    card.removeAttribute('data-reveal');
    card.style.setProperty('--skill-index', String(index % Math.max(cards.length, 1)));
    const button = toggleButtons[index];
    const details = card.querySelector('.skill-details');
    if (details) {
      if (!details.id) details.id = `skill-details-${index + 1}`;
      button?.setAttribute('aria-controls', details.id);
    }
    button?.addEventListener('click', () => {
      if (!mobileQuery.matches) return;
      const shouldExpand = !card.classList.contains('is-expanded');
      cards.forEach((otherCard, otherIndex) => {
        otherCard.classList.toggle('is-expanded', otherCard === card && shouldExpand);
        toggleButtons[otherIndex]?.setAttribute('aria-expanded', String(otherCard === card && shouldExpand));
      });
    });
  });

  let dragState = null;
  viewport.addEventListener('pointerdown', (event) => {
    if (mobileQuery.matches || event.button !== 0) return;
    const animation = track.getAnimations().find((candidate) => candidate.effect?.getTiming().iterations === Infinity);
    if (!animation) return;
    const transform = getComputedStyle(track).transform;
    const startX = transform === 'none' ? 0 : new DOMMatrixReadOnly(transform).m41;
    animation.pause();
    dragState = { pointerId: event.pointerId, startPointerX: event.clientX, startX, animation };
    viewport.setPointerCapture(event.pointerId);
    viewport.classList.add('is-dragging');
    event.preventDefault();
  });
  viewport.addEventListener('pointermove', (event) => {
    if (!dragState || dragState.pointerId !== event.pointerId) return;
    track.style.setProperty('transform', `translateX(${dragState.startX + event.clientX - dragState.startPointerX}px)`, 'important');
  });
  const finishDrag = (event) => {
    if (!dragState || dragState.pointerId !== event.pointerId) return;
    const state = dragState;
    dragState = null;
    const movedX = state.startX + event.clientX - state.startPointerX;
    const distance = Math.abs(parseFloat(track.style.getPropertyValue('--skill-loop-distance')) || 0);
    const duration = state.animation.effect.getTiming().duration;
    if (distance && typeof duration === 'number') {
      const progress = ((-movedX % distance) + distance) % distance / distance;
      state.animation.currentTime = progress * duration;
    }
    track.style.removeProperty('transform');
    viewport.classList.remove('is-dragging');
    state.animation.play();
  };
  viewport.addEventListener('pointerup', finishDrag);
  viewport.addEventListener('pointercancel', finishDrag);

  mobileQuery.addEventListener?.('change', setup);
  setup();

  if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(([entry], observer) => {
      if (!entry.isIntersecting) return;
      track.classList.add('is-revealed');
      observer.unobserve(viewport);
    }, { threshold: 0.01, rootMargin: '0px 0px -4% 0px' });
    revealObserver.observe(viewport);
  } else {
    track.classList.add('is-revealed');
  }
})();
