(() => {
  const scene = document.querySelector('.lp-scene');
  const strap = scene?.querySelector('.lp-strap');
  const strapBand = scene?.querySelector('.lp-strap-band');
  const stitch = scene?.querySelector('.lp-strap-stitch');
  const hook = scene?.querySelector('.lp-hook');
  const card = scene?.querySelector('.lp-card');
  const face = card?.querySelector('.badge-card');
  if (!scene || !strap || !strapBand || !stitch || !hook || !card || !face) return;

  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const links = 14;
  const dt = 1 / 120;
  const gravity = 2600;
  let width = 276, height = 330, segment = 21, hookLength = 26;
  let anchor = { x: 0, y: -24 };
  let points = [];
  const badge = { x: 0, y: 0, px: 0, py: 0 };
  const target = { x: 0, y: 0 };
  const pointerVelocity = { x: 0, y: 0 };
  let grabOffset = { x: 0, y: 0 };
  let lastPointer = { x: 0, y: 0, time: 0 };
  let spin = 0, spinVelocity = 0;
  let grabbed = false, active = false, leftScene = false;
  let raf = 0, previousTime = 0, accumulator = 0;

  const measure = () => {
    const small = window.innerWidth < 700;
    width = small ? Math.min(276, Math.max(154, scene.clientWidth - 8)) : 276;
    height = small ? width * 1.25 : 330;
    segment = (small ? 188 : 300) / links;
    anchor = { x: scene.clientWidth / 2, y: -24 };
    strap.setAttribute('viewBox', `0 0 ${scene.clientWidth} ${scene.clientHeight}`);
    card.style.width = `${width}px`;
    card.style.height = `${height}px`;
  };

  const reset = (drop) => {
    measure();
    points = [];
    for (let i = 0; i <= links; i += 1) {
      const x = anchor.x + (drop ? i * 4 : 0);
      const y = drop ? anchor.y - i * segment * .9 : anchor.y + i * segment;
      points.push({ x, y, px: x, py: y });
    }
    const end = points[links];
    const x = end.x + (drop ? 22 : 0);
    const y = end.y + hookLength + height / 2;
    Object.assign(badge, { x, y, px: x, py: y });
    spin = 0;
    spinVelocity = drop ? 260 : 0;
  };

  const constrain = (a, b, length, weightA, weightB) => {
    const dx = b.x - a.x, dy = b.y - a.y;
    const distance = Math.hypot(dx, dy) || .0001;
    const difference = (distance - length) / distance;
    a.x += dx * difference * weightA;
    a.y += dy * difference * weightA;
    b.x -= dx * difference * weightB;
    b.y -= dy * difference * weightB;
  };

  const step = () => {
    const damping = .992;
    for (let i = 1; i <= links; i += 1) {
      const point = points[i];
      const vx = (point.x - point.px) * damping;
      const vy = (point.y - point.py) * damping;
      point.px = point.x;
      point.py = point.y;
      point.x += vx;
      point.y += vy + gravity * dt * dt;
    }

    const oldX = badge.x;
    if (grabbed) {
      badge.px = badge.x;
      badge.py = badge.y;
      badge.x += (target.x - badge.x) * .5;
      badge.y += (target.y - badge.y) * .5;
    } else {
      const vx = (badge.x - badge.px) * .995;
      const vy = (badge.y - badge.py) * .995;
      badge.px = badge.x;
      badge.py = badge.y;
      badge.x += vx;
      badge.y += vy + gravity * dt * dt;
    }

    const cardLength = hookLength + height / 2;
    for (let pass = 0; pass < 16; pass += 1) {
      points[0].x = anchor.x;
      points[0].y = anchor.y;
      for (let i = 0; i < links; i += 1) {
        constrain(points[i], points[i + 1], segment, i === 0 ? 0 : .5, i === 0 ? 1 : .5);
      }
      constrain(points[links], badge, cardLength, grabbed ? 1 : .8, grabbed ? 0 : .2);
    }

    const cardVelocityX = (badge.x - oldX) / dt;
    spinVelocity += cardVelocityX * .018;
    const restingTurn = Math.round(spin / 360) * 360;
    spinVelocity += (restingTurn - spin) * 14 * dt;
    spinVelocity *= 1 - 2.2 * dt;
    spinVelocity = Math.max(-1400, Math.min(1400, spinVelocity));
    spin += spinVelocity * dt;
  };

  const render = () => {
    let d = `M${points[0].x.toFixed(1)} ${points[0].y.toFixed(1)}`;
    for (let i = 1; i < points.length - 1; i += 1) {
      const mx = (points[i].x + points[i + 1].x) / 2;
      const my = (points[i].y + points[i + 1].y) / 2;
      d += ` Q${points[i].x.toFixed(1)} ${points[i].y.toFixed(1)} ${mx.toFixed(1)} ${my.toFixed(1)}`;
    }
    const end = points[points.length - 1];
    d += ` L${end.x.toFixed(1)} ${end.y.toFixed(1)}`;
    strapBand.setAttribute('d', d);
    stitch.setAttribute('d', d);

    const angle = Math.atan2(badge.x - end.x, badge.y - end.y);
    const degrees = -angle * 180 / Math.PI;
    hook.style.transform = `translate(${(end.x - 20).toFixed(1)}px, ${(end.y - 4).toFixed(1)}px) rotate(${degrees.toFixed(2)}deg)`;
    card.style.transform = `translate(${(badge.x - width / 2).toFixed(1)}px, ${(badge.y - height / 2).toFixed(1)}px) rotate(${degrees.toFixed(2)}deg)`;
    face.style.transform = `rotateY(${spin.toFixed(2)}deg)`;
  };

  const loop = (time) => {
    if (!active) return;
    accumulator += Math.min(.05, (time - previousTime) / 1000);
    previousTime = time;
    while (accumulator >= dt) {
      step();
      accumulator -= dt;
    }
    render();
    raf = requestAnimationFrame(loop);
  };
  const start = () => {
    if (active || reduced) return;
    active = true;
    previousTime = performance.now();
    raf = requestAnimationFrame(loop);
  };
  const stop = () => {
    active = false;
    cancelAnimationFrame(raf);
  };
  const localPoint = (event) => {
    const rect = scene.getBoundingClientRect();
    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
  };

  reset(!reduced);
  if (reduced) {
    for (let i = 0; i < 400; i += 1) step();
    render();
  } else start();

  const down = (event) => {
    if (event.button !== 0) return;
    const point = localPoint(event);
    grabbed = true;
    grabOffset = { x: badge.x - point.x, y: badge.y - point.y };
    target.x = badge.x;
    target.y = badge.y;
    lastPointer = { ...point, time: performance.now() };
    pointerVelocity.x = pointerVelocity.y = 0;
    card.setPointerCapture(event.pointerId);
    card.classList.add('is-dragging');
    start();
    event.preventDefault();
  };
  const move = (event) => {
    if (!grabbed) return;
    const point = localPoint(event);
    const now = performance.now();
    const elapsed = Math.max(1, now - lastPointer.time) / 1000;
    pointerVelocity.x = pointerVelocity.x * .5 + ((point.x - lastPointer.x) / elapsed) * .5;
    pointerVelocity.y = pointerVelocity.y * .5 + ((point.y - lastPointer.y) / elapsed) * .5;
    lastPointer = { ...point, time: now };
    target.x = point.x + grabOffset.x;
    target.y = point.y + grabOffset.y;
  };
  const up = () => {
    if (!grabbed) return;
    grabbed = false;
    card.classList.remove('is-dragging');
    const vx = Math.max(-2400, Math.min(2400, pointerVelocity.x));
    const vy = Math.max(-2400, Math.min(2400, pointerVelocity.y));
    badge.px = badge.x - vx * dt;
    badge.py = badge.y - vy * dt;
  };
  const key = (event) => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    badge.px += event.key === 'ArrowLeft' ? 6 : -6;
    start();
  };
  card.addEventListener('pointerdown', down);
  card.addEventListener('pointermove', move);
  card.addEventListener('pointerup', up);
  card.addEventListener('pointercancel', up);
  card.addEventListener('keydown', key);

  const resize = () => {
    const oldX = anchor.x;
    measure();
    const shift = anchor.x - oldX;
    points.forEach((point) => { point.x += shift; point.px += shift; });
    badge.x += shift;
    badge.px += shift;
    render();
  };
  window.addEventListener('resize', resize);

  const observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) {
      if (leftScene && !reduced) {
        reset(true);
        leftScene = false;
      }
      start();
    } else {
      leftScene = true;
      stop();
    }
  }, { threshold: .05 });
  observer.observe(scene);

  window.addEventListener('pagehide', stop, { once: true });
})();
