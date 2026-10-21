(() => {
  const items = document.querySelectorAll('[data-reveal]');
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!('IntersectionObserver' in window) || reducedMotion) {
    items.forEach((item) => item.classList.add('is-visible'));
    return;
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
(() => {
  const drop = document.querySelector('.lanyard-drop');
  const pendulum = document.querySelector('.lanyard-pendulum');
  const badgeCard = document.querySelector('.badge-card');
  if (!drop || !pendulum) return;

  drop.addEventListener('animationend', (event) => {
    if (event.animationName === 'lanyard-drop-in') drop.style.animation = 'none';
  });

  let dragging = false;
  let pointerStartX = 0;
  let pointerStartY = 0;
  let lastPointerX = 0;
  let lastPointerTime = 0;
  let angularVelocity = 0;
  let angle = 0;
  let dragOffset = 0;
  let badgeRotation = 0;
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const setPose = () => {
    pendulum.style.transform = `rotate(${angle}deg)`;
    drop.style.setProperty('--drag-y', `${dragOffset}px`);
  };

  const readMatrix = (element) => {
    const value = getComputedStyle(element).transform;
    return value === 'none' ? null : new DOMMatrixReadOnly(value);
  };

  pendulum.addEventListener('pointerdown', (event) => {
    if (event.button !== undefined && event.button !== 0) return;
    const currentPendulum = readMatrix(pendulum);
    const currentDrop = readMatrix(drop);
    const currentBadge = badgeCard ? readMatrix(badgeCard) : null;
    if (currentPendulum) angle = Math.atan2(currentPendulum.b, currentPendulum.a) * 180 / Math.PI;
    if (currentDrop) dragOffset = currentDrop.f;
    if (currentBadge) badgeRotation = Math.atan2(-currentBadge.m13, currentBadge.m11) * 180 / Math.PI;
    [...pendulum.getAnimations(), ...drop.getAnimations()].forEach((animation) => animation.cancel());
    if (badgeCard) {
      badgeCard.getAnimations().forEach((animation) => animation.cancel());
      badgeCard.style.transform = `rotateY(${badgeRotation}deg)`;
    }
    setPose();
    dragging = true;
    pointerStartX = event.clientX;
    pointerStartY = event.clientY;
    lastPointerX = event.clientX;
    lastPointerTime = performance.now();
    angularVelocity = 0;
    pendulum.classList.add('is-dragging');
    pendulum.setPointerCapture(event.pointerId);
    event.preventDefault();
  });

  pendulum.addEventListener('pointermove', (event) => {
    if (!dragging) return;
    const now = performance.now();
    const elapsed = Math.max(1, now - lastPointerTime) / 1000;
    const instantVelocity = ((event.clientX - lastPointerX) * .22) / elapsed;
    angularVelocity = Math.max(-150, Math.min(150, angularVelocity * .55 + instantVelocity * .45));
    lastPointerX = event.clientX;
    lastPointerTime = now;
    // CSS rotation moves the lower end opposite the sign of the angle in screen coordinates.
    angle = Math.max(-32, Math.min(32, -(event.clientX - pointerStartX) * .22));
    dragOffset = Math.max(-110, Math.min(150, (event.clientY - pointerStartY) * .55));
    setPose();
  });

  const settle = () => {
    if (!dragging) return;
    dragging = false;
    pendulum.classList.remove('is-dragging');
    const releaseAngle = angle;
    const pull = dragOffset;
    if (reducedMotion) {
      angle = 0;
      dragOffset = 0;
      setPose();
      return;
    }
    const swingCount = 7;
    const framesPerSwing = 30;
    const duration = 8400;
    const swingFrames = [];
    const dropFrames = [];
    const durationSeconds = duration / 1000;
    const omega = (Math.PI * 2 * swingCount) / durationSeconds;
    const decayRate = 3.2 / durationSeconds;
    const releaseVelocity = angularVelocity;
    for (let step = 0; step < swingCount * framesPerSwing; step += 1) {
      const progress = step / (swingCount * framesPerSwing);
      const time = progress * durationSeconds;
      const decay = Math.exp(-decayRate * time);
      const phase = omega * time;
      const rotation = decay * (releaseAngle * Math.cos(phase) + ((releaseVelocity + decayRate * releaseAngle) / omega) * Math.sin(phase));
      const vertical = pull * decay * Math.cos(phase);
      swingFrames.push({ offset: progress, transform: `rotate(${rotation}deg)` });
      dropFrames.push({ offset: progress, transform: `translateY(${vertical}px)` });
    }
    swingFrames.push({ offset: 1, transform: 'rotate(0deg)' });
    dropFrames.push({ offset: 1, transform: 'translateY(0px)' });
    const timing = { duration, easing: 'linear', iterations: 1 };
    const swing = pendulum.animate(swingFrames, timing);
    const dropSwing = drop.animate(dropFrames, timing);
    if (badgeCard) {
      const turns = Math.max(1, Math.min(3, 1 + Math.round(Math.abs(releaseVelocity) / 90)));
      const spinDirection = Math.sign(releaseVelocity || releaseAngle || 1);
      const endRotation = spinDirection > 0
        ? Math.ceil(badgeRotation / 360) * 360 + turns * 360
        : Math.floor(badgeRotation / 360) * 360 - turns * 360;
      const spin = badgeCard.animate([
        { transform: `rotateY(${badgeRotation}deg)` },
        { transform: `rotateY(${endRotation}deg)` }
      ], { duration: 1300 + turns * 900, easing: 'cubic-bezier(.12,.72,.2,1)', iterations: 1 });
      spin.onfinish = () => {
        badgeRotation = 0;
        badgeCard.style.transform = 'rotateY(0deg)';
      };
    }
    swing.onfinish = () => {
      angle = 0;
      setPose();
    };
    dropSwing.onfinish = () => {
      dragOffset = 0;
      drop.style.setProperty('--drag-y', '0px');
    };
  };

  pendulum.addEventListener('pointerup', settle);
  pendulum.addEventListener('pointercancel', settle);
  let keyboardRelease;
  pendulum.addEventListener('keydown', (event) => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    dragging = true;
    angularVelocity = 0;
    angle = Math.max(-32, Math.min(32, angle + (event.key === 'ArrowLeft' ? 6 : -6)));
    setPose();
    clearTimeout(keyboardRelease);
    keyboardRelease = setTimeout(settle, 250);
  });

  // Replay the intro drop when the lanyard section is revisited after scrolling away.
  const intro = document.querySelector('.lanyard-intro');
  if (intro && 'IntersectionObserver' in window && !reducedMotion) {
    let hasLeftIntro = false;
    const introObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          hasLeftIntro = true;
          return;
        }
        if (!hasLeftIntro) return;
        hasLeftIntro = false;

        dragging = false;
        pendulum.classList.remove('is-dragging');
        [...pendulum.getAnimations(), ...drop.getAnimations()].forEach((animation) => animation.cancel());
        if (badgeCard) {
          badgeCard.getAnimations().forEach((animation) => animation.cancel());
          badgeCard.style.transform = 'rotateY(0deg)';
        }
        angle = 0;
        dragOffset = 0;
        angularVelocity = 0;
        badgeRotation = 0;
        setPose();

        drop.style.animation = 'none';
        void drop.offsetWidth;
        drop.style.animation = 'lanyard-drop-in 2s cubic-bezier(.18,.78,.3,1.15) both';
      });
    }, { threshold: .12 });
    introObserver.observe(intro);
  }
})();

