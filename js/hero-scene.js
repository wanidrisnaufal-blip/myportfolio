(() => {
  const canvas = document.querySelector('.hero-canvas');
  const stage = document.querySelector('.hero-canvas-wrap');
  if (!canvas || !stage) return;

  const gl = canvas.getContext('webgl', { alpha: true, antialias: true, powerPreference: 'low-power' });
  if (!gl) {
    stage.closest('.hero-art')?.classList.add('is-fallback');
    return;
  }

  const vertexSource = `
    attribute vec3 aPosition;
    uniform mat4 uMvp;
    uniform mat4 uModel;
    uniform float uTime;
    varying vec3 vNormal;
    varying vec3 vPosition;
    void main() {
      vec3 p = aPosition;
      float wave = sin(p.y * 4.2 + uTime * 0.9) * cos(p.x * 3.1 - uTime * 0.55);
      wave += sin(p.z * 4.7 - uTime * 0.7) * 0.35;
      p += normalize(p) * wave * 0.075;
      vNormal = normalize(mat3(uModel) * normalize(p));
      vPosition = (uModel * vec4(p, 1.0)).xyz;
      gl_Position = uMvp * vec4(p, 1.0);
    }
  `;
  const fragmentSource = `
    precision mediump float;
    uniform float uTime;
    varying vec3 vNormal;
    varying vec3 vPosition;
    void main() {
      vec3 n = normalize(vNormal);
      vec3 viewDir = normalize(vec3(0.0, 0.0, 5.2) - vPosition);
      vec3 lightA = normalize(vec3(-0.55, 0.72, 0.9));
      vec3 lightB = normalize(vec3(0.8, -0.18, 0.75));
      float diffuse = max(dot(n, lightA), 0.0);
      float glossA = pow(max(dot(reflect(-lightA, n), viewDir), 0.0), 52.0);
      float glossB = pow(max(dot(reflect(-lightB, n), viewDir), 0.0), 24.0) * 0.28;
      float glossC = pow(max(dot(reflect(-lightA, n), viewDir), 0.0), 8.0) * 0.24;
      float fresnel = pow(1.0 - max(dot(n, viewDir), 0.0), 2.8);
      float sheen = pow(max(dot(n, normalize(vec3(-0.25, 0.82, 0.5))), 0.0), 8.0) * 0.24;
      vec3 color = vec3(0.008) + vec3(0.045) * diffuse + vec3(0.7) * glossA + vec3(0.24) * glossB;
      color += vec3(0.2) * fresnel + vec3(0.25) * sheen + vec3(0.6) * glossC;
      gl_FragColor = vec4(color, 1.0);
    }
  `;
  const orbitVertexSource = `
    attribute vec3 aPosition;
    uniform mat4 uMvp;
    uniform float uPointSize;
    void main() {
      gl_Position = uMvp * vec4(aPosition, 1.0);
      gl_PointSize = uPointSize;
    }
  `;
  const orbitFragmentSource = `
    precision mediump float;
    uniform vec4 uColor;
    uniform float uRoundPoint;
    void main() {
      float edge = 1.0;
      if (uRoundPoint > 0.5) {
        float distanceFromCenter = length(gl_PointCoord - vec2(0.5)) * 2.0;
        if (distanceFromCenter > 1.0) discard;
        edge = 1.0 - smoothstep(0.72, 1.0, distanceFromCenter);
      }
      gl_FragColor = vec4(uColor.rgb, uColor.a * edge);
    }
  `;

  const compile = (type, source) => {
    const shader = gl.createShader(type);
    gl.shaderSource(shader, source);
    gl.compileShader(shader);
    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
      console.error('Hero 3D shader error:', gl.getShaderInfoLog(shader));
      gl.deleteShader(shader);
      return null;
    }
    return shader;
  };
  const vertexShader = compile(gl.VERTEX_SHADER, vertexSource);
  const fragmentShader = compile(gl.FRAGMENT_SHADER, fragmentSource);
  if (!vertexShader || !fragmentShader) {
    stage.closest('.hero-art')?.classList.add('is-fallback');
    return;
  }
  const program = gl.createProgram();
  gl.attachShader(program, vertexShader);
  gl.attachShader(program, fragmentShader);
  gl.linkProgram(program);
  if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
    console.error('Hero 3D program error:', gl.getProgramInfoLog(program));
    stage.closest('.hero-art')?.classList.add('is-fallback');
    return;
  }
  const orbitVertexShader = compile(gl.VERTEX_SHADER, orbitVertexSource);
  const orbitFragmentShader = compile(gl.FRAGMENT_SHADER, orbitFragmentSource);
  if (!orbitVertexShader || !orbitFragmentShader) {
    stage.closest('.hero-art')?.classList.add('is-fallback');
    return;
  }
  const orbitProgram = gl.createProgram();
  gl.attachShader(orbitProgram, orbitVertexShader);
  gl.attachShader(orbitProgram, orbitFragmentShader);
  gl.linkProgram(orbitProgram);
  if (!gl.getProgramParameter(orbitProgram, gl.LINK_STATUS)) {
    console.error('Hero orbit shader error:', gl.getProgramInfoLog(orbitProgram));
    stage.closest('.hero-art')?.classList.add('is-fallback');
    return;
  }

  // Build a smooth, high-resolution sphere and let the vertex shader gently distort it.
  const rows = 48;
  const columns = 64;
  const vertices = [];
  const indices = [];
  for (let row = 0; row <= rows; row += 1) {
    const phi = row / rows * Math.PI;
    for (let column = 0; column <= columns; column += 1) {
      const theta = column / columns * Math.PI * 2;
      vertices.push(Math.sin(phi) * Math.cos(theta) * 1.22, Math.cos(phi) * 1.22, Math.sin(phi) * Math.sin(theta) * 1.22);
    }
  }
  for (let row = 0; row < rows; row += 1) {
    for (let column = 0; column < columns; column += 1) {
      const a = row * (columns + 1) + column;
      const b = a + columns + 1;
      indices.push(a, a + 1, b, a + 1, b + 1, b);
    }
  }
  const vertexBuffer = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, vertexBuffer);
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(vertices), gl.STATIC_DRAW);
  const indexBuffer = gl.createBuffer();
  gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, indexBuffer);
  gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, new Uint16Array(indices), gl.STATIC_DRAW);

  const makeOrbitBuffer = (radius, segments) => {
    const points = [];
    for (let i = 0; i < segments; i += 1) {
      const angle = i / segments * Math.PI * 2;
      points.push(Math.cos(angle) * radius, Math.sin(angle) * radius, 0);
    }
    const buffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(points), gl.STATIC_DRAW);
    return { buffer, count: segments };
  };
  const orbitA = makeOrbitBuffer(2.78, 240);
  const orbitB = makeOrbitBuffer(3.08, 240);
  const makeOrbitDots = (radius, count) => {
    const points = [];
    for (let i = 0; i < count; i += 1) {
      const angle = i / count * Math.PI * 2;
      points.push(Math.cos(angle) * radius, Math.sin(angle) * radius, 0);
    }
    const buffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(points), gl.STATIC_DRAW);
    return { buffer, count };
  }
  const compactViewport = matchMedia('(max-width: 640px)').matches;
  const orbitDotsA = makeOrbitDots(2.78, compactViewport ? 8 : 12);
  const orbitDotsB = makeOrbitDots(3.08, compactViewport ? 4 : 8);

  const positionLocation = gl.getAttribLocation(program, 'aPosition');
  const mvpLocation = gl.getUniformLocation(program, 'uMvp');
  const modelLocation = gl.getUniformLocation(program, 'uModel');
  const timeLocation = gl.getUniformLocation(program, 'uTime');
  const orbitPositionLocation = gl.getAttribLocation(orbitProgram, 'aPosition');
  const orbitMvpLocation = gl.getUniformLocation(orbitProgram, 'uMvp');
  const orbitPointSizeLocation = gl.getUniformLocation(orbitProgram, 'uPointSize');
  const orbitColorLocation = gl.getUniformLocation(orbitProgram, 'uColor');
  const orbitRoundPointLocation = gl.getUniformLocation(orbitProgram, 'uRoundPoint');
  const identity = () => [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1];
  const multiply = (a, b) => {
    const out = new Array(16);
    for (let col = 0; col < 4; col += 1) for (let row = 0; row < 4; row += 1) {
      out[col * 4 + row] = a[row] * b[col * 4] + a[4 + row] * b[col * 4 + 1] + a[8 + row] * b[col * 4 + 2] + a[12 + row] * b[col * 4 + 3];
    }
    return out;
  };
  const rotateX = (angle) => { const c = Math.cos(angle), s = Math.sin(angle); return [1, 0, 0, 0, 0, c, s, 0, 0, -s, c, 0, 0, 0, 0, 1]; };
  const rotateY = (angle) => { const c = Math.cos(angle), s = Math.sin(angle); return [c, 0, -s, 0, 0, 1, 0, 0, s, 0, c, 0, 0, 0, 0, 1]; };
  const rotateZ = (angle) => { const c = Math.cos(angle), s = Math.sin(angle); return [c, s, 0, 0, -s, c, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1]; };
  const translate = (x, y, z) => [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, x, y, z, 1];
  const scale = (x, y, z) => [x, 0, 0, 0, 0, y, 0, 0, 0, 0, z, 0, 0, 0, 0, 1];
  const projection = (aspect) => {
    const near = 0.1, far = 100, f = 1 / Math.tan(42 * Math.PI / 360);
    return [f / aspect, 0, 0, 0, 0, f, 0, 0, 0, 0, (far + near) / (near - far), -1, 0, 0, (2 * far * near) / (near - far), 0];
  };
  const view = identity();
  // Extra canvas overscan lets the 3D paths travel beyond the art panel edges.
  view[14] = -8.4;

  let width = 1;
  let height = 1;
  let rotationX = -0.12;
  let rotationY = 0;
  let targetX = rotationX;
  let targetY = rotationY;
  let dragging = false;
  let pointerX = 0;
  let pointerY = 0;
  let orbitX = 0;
  let orbitY = 0;
  let orbitRotation = 0;
  let targetOrbitX = 0;
  let targetOrbitY = 0;
  let orbitTiltX = 0;
  let orbitTiltY = 0;
  let orbitSpin = 0;
  let targetOrbitTiltX = 0;
  let targetOrbitTiltY = 0;
  let targetOrbitSpin = 0;
  let lastFrame = 0;
  let visible = true;
  const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const resize = () => {
    const rect = canvas.getBoundingClientRect();
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    width = Math.max(1, Math.round(rect.width * dpr));
    height = Math.max(1, Math.round(rect.height * dpr));
    if (canvas.width !== width || canvas.height !== height) {
      canvas.width = width;
      canvas.height = height;
      gl.viewport(0, 0, width, height);
      render(lastFrame / 1000, 0);
    }
  };
  const render = (time, dt) => {
    if (!visible) return;
    const ease = reduceMotion ? 1 : 1 - Math.exp(-2.8 * dt);
    rotationX += (targetX - rotationX) * ease;
    rotationY += (targetY - rotationY) * ease;
    orbitX += (targetOrbitX - orbitX) * ease;
    orbitY += (targetOrbitY - orbitY) * ease;
    orbitTiltX += (targetOrbitTiltX - orbitTiltX) * ease;
    orbitTiltY += (targetOrbitTiltY - orbitTiltY) * ease;
    orbitSpin += (targetOrbitSpin - orbitSpin) * ease;
    const model = multiply(rotateY(rotationY), rotateX(rotationX));
    const translated = multiply(view, model);
    const mvp = multiply(projection(width / height), translated);
    gl.clearColor(0, 0, 0, 0);
    gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
    gl.enable(gl.DEPTH_TEST);
    gl.useProgram(program);
    gl.bindBuffer(gl.ARRAY_BUFFER, vertexBuffer);
    gl.enableVertexAttribArray(positionLocation);
    gl.vertexAttribPointer(positionLocation, 3, gl.FLOAT, false, 0, 0);
    gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, indexBuffer);
    gl.uniformMatrix4fv(mvpLocation, false, new Float32Array(mvp));
    gl.uniformMatrix4fv(modelLocation, false, new Float32Array(model));
    gl.uniform1f(timeLocation, time);
    gl.drawElements(gl.TRIANGLES, indices.length, gl.UNSIGNED_SHORT, 0);

    const liquidAsteroids = [
      { phase: 0.35, radius: 3.16, size: [0.14, 0.105, 0.12] },
      { phase: 2.65, radius: 3.22, size: [0.10, 0.15, 0.12] },
      { phase: 4.8, radius: 3.18, size: [0.12, 0.105, 0.16] },
    ];
    liquidAsteroids.forEach(({ phase, radius, size }, index) => {
      const angle = time * (index % 2 ? -0.16 : 0.13) + phase;
      const local = multiply(translate(Math.cos(angle) * radius, Math.sin(angle) * radius * 0.7, Math.sin(angle * 1.3) * 0.55), multiply(rotateY(angle * 0.4), scale(...size)));
      const asteroidModel = multiply(model, local);
      gl.uniformMatrix4fv(mvpLocation, false, new Float32Array(multiply(projection(width / height), multiply(view, asteroidModel))));
      gl.uniformMatrix4fv(modelLocation, false, new Float32Array(asteroidModel));
      gl.drawElements(gl.TRIANGLES, indices.length, gl.UNSIGNED_SHORT, 0);
    });

    const orbitModelA = multiply(translate(orbitX, orbitY, 0), multiply(rotateZ(orbitSpin), multiply(rotateY(orbitTiltY + 0.2), rotateX(orbitTiltX + 1.2))));
    const orbitModelB = multiply(translate(orbitX, orbitY, 0), multiply(rotateZ(orbitSpin + 0.4), multiply(rotateY(orbitTiltY - 0.5), rotateX(orbitTiltX + 1.9))));
    gl.useProgram(orbitProgram);
    gl.enable(gl.BLEND);
    gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);
    gl.depthMask(false);
    gl.bindBuffer(gl.ARRAY_BUFFER, orbitA.buffer);
    gl.enableVertexAttribArray(orbitPositionLocation);
    gl.vertexAttribPointer(orbitPositionLocation, 3, gl.FLOAT, false, 0, 0);
    gl.uniformMatrix4fv(orbitMvpLocation, false, new Float32Array(multiply(projection(width / height), multiply(view, orbitModelA))));
    gl.uniform1f(orbitPointSizeLocation, 1);
    gl.uniform1f(orbitRoundPointLocation, 0);
    gl.uniform4f(orbitColorLocation, 0.08, 0.08, 0.08, 0.64);
    gl.drawArrays(gl.LINE_LOOP, 0, orbitA.count);
    gl.bindBuffer(gl.ARRAY_BUFFER, orbitB.buffer);
    gl.vertexAttribPointer(orbitPositionLocation, 3, gl.FLOAT, false, 0, 0);
    gl.uniformMatrix4fv(orbitMvpLocation, false, new Float32Array(multiply(projection(width / height), multiply(view, orbitModelB))));
    gl.uniform4f(orbitColorLocation, 0.08, 0.08, 0.08, 0.44);
    gl.drawArrays(gl.LINE_LOOP, 0, orbitB.count);
    gl.bindBuffer(gl.ARRAY_BUFFER, orbitDotsA.buffer);
    gl.vertexAttribPointer(orbitPositionLocation, 3, gl.FLOAT, false, 0, 0);
    gl.uniformMatrix4fv(orbitMvpLocation, false, new Float32Array(multiply(projection(width / height), multiply(view, orbitModelA))));
    gl.uniform1f(orbitPointSizeLocation, Math.max(4, Math.min(7, width / 75)));
    gl.uniform1f(orbitRoundPointLocation, 1);
    gl.uniform4f(orbitColorLocation, 0.04, 0.04, 0.04, 1.0);
    gl.drawArrays(gl.POINTS, 0, orbitDotsA.count);
    gl.bindBuffer(gl.ARRAY_BUFFER, orbitDotsB.buffer);
    gl.vertexAttribPointer(orbitPositionLocation, 3, gl.FLOAT, false, 0, 0);
    gl.uniformMatrix4fv(orbitMvpLocation, false, new Float32Array(multiply(projection(width / height), multiply(view, orbitModelB))));
    gl.uniform1f(orbitPointSizeLocation, Math.max(3, Math.min(5, width / 100)));
    gl.uniform4f(orbitColorLocation, 0.04, 0.04, 0.04, 0.88);
    gl.drawArrays(gl.POINTS, 0, orbitDotsB.count);
    gl.depthMask(true);
    gl.disable(gl.BLEND);
  };

  const animate = (timestamp) => {
    const dt = Math.min((timestamp - lastFrame) / 1000 || 0, 0.05);
    lastFrame = timestamp;
    render(timestamp / 1000, dt);
    if (!reduceMotion) requestAnimationFrame(animate);
  };
  const observer = new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; if (visible && reduceMotion) render(lastFrame / 1000, 0); });
  observer.observe(stage);
  new ResizeObserver(resize).observe(stage);
  resize();
  requestAnimationFrame(animate);

  stage.addEventListener('pointerdown', (event) => {
    if (event.button !== undefined && event.button !== 0) return;
    dragging = true;
    pointerX = event.clientX;
    pointerY = event.clientY;
    stage.setPointerCapture(event.pointerId);
    event.preventDefault();
  });
  stage.addEventListener('pointermove', (event) => {
    const rect = stage.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    const x = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
    const y = Math.max(0, Math.min(1, (event.clientY - rect.top) / rect.height));
    if (dragging) {
      const dx = event.clientX - pointerX;
      const dy = event.clientY - pointerY;
      targetY += (dx / rect.width) * Math.PI * 2;
      targetX = Math.max(-1.1, Math.min(1.1, targetX - dy * 0.008));
      targetOrbitTiltX += (dy / rect.height) * Math.PI * 2;
      targetOrbitTiltY += (dx / rect.width) * Math.PI * 2;
      targetOrbitSpin += (dx / rect.width) * Math.PI * 2;
    } else {
      targetY = (x - 0.5) * 0.9;
      targetX = Math.max(-0.55, Math.min(0.55, -(y - 0.5) * 0.7 - 0.12));
      targetOrbitTiltX = (y - 0.5) * 0.8;
      targetOrbitTiltY = (x - 0.5) * 0.9;
      targetOrbitSpin = (x - 0.5) * 0.32;
    }
    targetOrbitX = (x - 0.5) * 0.2;
    targetOrbitY = (y - 0.5) * 0.16;
    pointerX = event.clientX;
    pointerY = event.clientY;
    if (reduceMotion) render(lastFrame / 1000, 0);
  });
  const releaseDrag = () => { dragging = false; };
  stage.addEventListener('pointerup', releaseDrag);
  stage.addEventListener('pointercancel', releaseDrag);
  stage.addEventListener('pointerleave', () => {
    dragging = false;
  });
  stage.addEventListener('keydown', (event) => {
    const step = event.shiftKey ? 0.18 : 0.08;
    if (event.key === 'ArrowLeft') targetY -= step;
    else if (event.key === 'ArrowRight') targetY += step;
    else if (event.key === 'ArrowUp') targetX = Math.max(-1.1, targetX - step);
    else if (event.key === 'ArrowDown') targetX = Math.min(1.1, targetX + step);
    else return;
    event.preventDefault();
    if (reduceMotion) render(lastFrame / 1000, 0);
  });
})();
