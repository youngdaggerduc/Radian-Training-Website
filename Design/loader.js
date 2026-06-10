/* ============================================================================
   loader.js — Radian H.A. Limited intro reveal
   "From blueprint to built": a steel-blue wireframe of the company's tiered-
   pyramid logo appears first (the drawing), then the five coloured tiers —
   magenta, red, purple, orange and the yellow capstone — are craned in one
   by one. Each lands with a squash-and-stretch thud, weld sparks and a
   shockwave ring; the capstone tops out with a sun flash, the blueprint
   fades, the tiers pulse their colours, and the navy safety sheeting hoists
   away in vertical slats to reveal the site.

   Requires THREE (r128) + gsap, already loaded in <head>.
   Degrades gracefully: timed fallback if a library is missing, instant skip on
   prefers-reduced-motion's exit, once-per-session via sessionStorage
   (override with ?intro=1 or #replay).
============================================================================ */
(function () {
  'use strict';
  const overlay = document.getElementById('radian-loader');
  if (!overlay) return;

  const force = /[?&]intro=1/.test(location.search) || location.hash === '#replay';
  const lockScroll = () => document.documentElement.classList.add('rl-lock');
  const unlockScroll = () => document.documentElement.classList.remove('rl-lock');

  // once per session
  let seen = false;
  try { seen = !!sessionStorage.getItem('radianIntroSeen'); } catch (e) {}
  if (seen && !force) { overlay.parentNode && overlay.parentNode.removeChild(overlay); return; }
  try { sessionStorage.setItem('radianIntroSeen', '1'); } catch (e) {}

  lockScroll();

  const mount   = overlay.querySelector('.rl-canvas');
  const pctEl   = overlay.querySelector('.rl-pct');
  const barEl   = overlay.querySelector('.rl-bar-fill');
  const statusEl = overlay.querySelector('.rl-status');
  const reduced  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const STATUS = [
    [0.00, 'Reading the drawings'],
    [0.14, 'Laying the foundation'],
    [0.30, 'Setting the second tier'],
    [0.46, 'Raising the third tier'],
    [0.62, 'Fitting the fourth tier'],
    [0.78, 'Crowning the capstone'],
    [1.00, 'Structure complete'],
  ];
  function setProgress(p) {
    p = Math.max(0, Math.min(1, p));
    const pct = Math.round(p * 100);
    if (pctEl) pctEl.textContent = pct;
    if (barEl) barEl.style.width = pct + '%';
    if (statusEl) {
      let label = STATUS[0][1];
      for (let i = 0; i < STATUS.length; i++) if (p >= STATUS[i][0]) label = STATUS[i][1];
      if (statusEl.textContent !== label) statusEl.textContent = label;
    }
  }

  function remove() {
    unlockScroll();
    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
  }

  // ---- curtain lift -------------------------------------------------------
  function liftCurtain(onDone) {
    if (reduced || typeof gsap === 'undefined') {
      overlay.style.transition = 'opacity .5s ease';
      overlay.style.opacity = '0';
      setTimeout(() => { remove(); onDone && onDone(); }, 520);
      return;
    }
    const slats = overlay.querySelectorAll('.rl-slat');
    gsap.to('.rl-stage', { opacity: 0, y: -26, duration: 0.5, ease: 'power2.in' });
    gsap.to(slats, {
      yPercent: -100, duration: 0.78, ease: 'power3.inOut', stagger: 0.055, delay: 0.32,
      onComplete: () => { remove(); onDone && onDone(); },
    });
  }

  // ---- graceful fallback --------------------------------------------------
  if (typeof THREE === 'undefined' || typeof gsap === 'undefined') {
    overlay.classList.add('rl-nolib');
    const obj = { p: 0 };
    const tick = () => {
      obj.p += 0.012 + Math.random() * 0.02;
      setProgress(obj.p);
      if (obj.p < 1) requestAnimationFrame(tick); else setTimeout(liftCurtain, 450);
    };
    requestAnimationFrame(tick);
    return;
  }

  // ========================================================================
  //  THREE.JS — crane the brand's five-tier pyramid mark into place
  // ========================================================================

  // Vivid tier colours sampled & boosted from the brand mark (bottom → capstone)
  const TIER_COLORS  = [0xc04080, 0xd83220, 0x7030a0, 0xf07820, 0xf8cc10];
  const OUTLINE      = 0x110820;   // near-black comic outline
  const BLUEPRINT    = 0x4d8fd4;   // drawing-office blue
  const reducedMo    = reduced;

  // ---- renderer -----------------------------------------------------------
  const scene    = new THREE.Scene();
  const camera   = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  // Solid dark clear-color: no transparency gaps that could bleed the site through
  renderer.setClearColor(0x000000, 0);
  renderer.outputEncoding = THREE.sRGBEncoding;
  renderer.toneMapping    = THREE.LinearToneMapping;
  renderer.toneMappingExposure = 1.0;
  mount.appendChild(renderer.domElement);
  renderer.domElement.style.width   = '100%';
  renderer.domElement.style.height  = '100%';
  renderer.domElement.style.display = 'block';

  // ---- lights — neutral white so tier colours render true to the logo ----
  // Flat ambient: colours read exactly as painted
  scene.add(new THREE.AmbientLight(0xffffff, 0.55));
  // Hemisphere: warm white sky, near-black ground (no hue cast)
  scene.add(new THREE.HemisphereLight(0xfff5e8, 0x080c14, 0.45));
  // Soft key from upper-right — adds shape without shifting hue
  const key = new THREE.DirectionalLight(0xffffff, 0.9);
  key.position.set(3, 7, 5); scene.add(key);
  // Gentle fill from the left
  const fill = new THREE.DirectionalLight(0xffffff, 0.35);
  fill.position.set(-4, 2, 3); scene.add(fill);
  // Pulsing point lights in tier colours for a glow-from-within feel
  const pA = new THREE.PointLight(0xf07820, 0.7, 12); pA.position.set(1.6, -0.4, 3.0); scene.add(pA);
  const pB = new THREE.PointLight(0xc04080, 0.6, 12); pB.position.set(-1.6,  0.5, 3.0); scene.add(pB);
  const pC = new THREE.PointLight(0xf8cc10, 0.5,  9); pC.position.set(0,    2.1, 3.2); scene.add(pC);

  // ---- geometry -----------------------------------------------------------
  const root = new THREE.Group();
  scene.add(root);

  // Pyramid parameters — faithful to the logo proportions
  // Equal-width tiers (logo slabs are roughly uniform height), tapering to a point
  const W0     = 1.42;        // base half-width (square footprint)
  const SQ2    = Math.SQRT2;  // square half-width → circumradius
  const yBase  = -1.12;
  // Logo tiers are roughly equal in height; capstone is taller/triangular
  const TIER_H = [0.44, 0.42, 0.40, 0.38, 0.52];
  const GAP    = 0.055;
  const H_TOTAL = TIER_H.reduce((s, h) => s + h, 0) + GAP * (TIER_H.length - 1);
  const wAt = (y) => Math.max(W0 * (1 - y / H_TOTAL), 0.001);

  const tiers = [];
  let yCursor = 0;
  for (let i = 0; i < TIER_H.length; i++) {
    const h  = TIER_H[i];
    const rB = wAt(yCursor) * SQ2;
    const rT = (i === TIER_H.length - 1) ? 0.001 : wAt(yCursor + h) * SQ2;

    const geo = new THREE.CylinderGeometry(rT, rB, h, 4, 1);
    geo.translate(0, h / 2, 0);    // pivot at slab base so drop lands correctly
    geo.rotateY(Math.PI / 4);      // flat faces toward camera

    const mat = new THREE.MeshStandardMaterial({
      color: TIER_COLORS[i],
      metalness: 0.08, roughness: 0.42,
      flatShading: true,
      emissive: TIER_COLORS[i], emissiveIntensity: 0.65,
      transparent: true, opacity: 0,
    });
    const mesh = new THREE.Mesh(geo, mat);

    // thick comic outline — matches the logo artwork
    const line = new THREE.LineSegments(
      new THREE.EdgesGeometry(geo, 10),
      new THREE.LineBasicMaterial({ color: OUTLINE, linewidth: 2, transparent: true, opacity: 0 })
    );

    const group = new THREE.Group();
    group.add(mesh); group.add(line);
    group.position.y = yBase + yCursor;
    root.add(group);
    tiers.push({ group, mesh, line, y: yBase + yCursor, h, color: TIER_COLORS[i] });
    yCursor += h + GAP;
  }

  // Centre of the finished mark in world space
  const markCenterY = yBase + H_TOTAL * 0.5;

  // ---- blueprint: ghost wireframe of the complete mark -------------------
  const bpGeo = new THREE.CylinderGeometry(0.012, W0 * SQ2, H_TOTAL, 4, 1);
  bpGeo.translate(0, H_TOTAL / 2, 0);
  bpGeo.rotateY(Math.PI / 4);
  const blueprint = new THREE.LineSegments(
    new THREE.EdgesGeometry(bpGeo, 10),
    new THREE.LineBasicMaterial({ color: BLUEPRINT, transparent: true, opacity: 0 })
  );
  blueprint.position.y = yBase;
  root.add(blueprint);

  // survey ring on the ground
  const ring = new THREE.Mesh(
    new THREE.TorusGeometry(W0 * 1.5, 0.015, 6, 72),
    new THREE.MeshBasicMaterial({ color: BLUEPRINT, transparent: true, opacity: 0, depthWrite: false })
  );
  ring.rotation.x = Math.PI / 2;
  ring.position.y  = yBase - 0.02;
  root.add(ring);

  // ---- weld sparks -------------------------------------------------------
  const sparks = [];
  function sparkBurst(p, color) {
    if (reduced) return;
    const n = 14;
    const pos = new Float32Array(n * 3); const vel = [];
    for (let i = 0; i < n; i++) {
      pos[i * 3]     = p.x; pos[i * 3 + 1] = p.y; pos[i * 3 + 2] = p.z;
      vel.push(new THREE.Vector3(
        (Math.random() - 0.5) * 2.8,
        Math.random() * 2.4 + 0.8,
        (Math.random() - 0.5) * 2.8
      ));
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    const pts = new THREE.Points(geo, new THREE.PointsMaterial({
      color: color || 0xffe0a0, size: 0.065, transparent: true, opacity: 1,
      blending: THREE.AdditiveBlending, depthWrite: false,
    }));
    pts.userData = { vel: vel, life: 0 };
    root.add(pts); sparks.push(pts);
  }
  function updateSparks(dt) {
    for (let i = sparks.length - 1; i >= 0; i--) {
      const s = sparks[i];
      s.userData.life += dt;
      const k = s.userData.life / 0.6;
      const a = s.geometry.attributes.position;
      for (let j = 0; j < s.userData.vel.length; j++) {
        const v = s.userData.vel[j];
        a.array[j * 3]     += v.x * dt;
        a.array[j * 3 + 1] += v.y * dt;
        a.array[j * 3 + 2] += v.z * dt;
        v.y -= 5.5 * dt;
      }
      a.needsUpdate = true;
      s.material.opacity = Math.max(0, 1 - k);
      if (k >= 1) { root.remove(s); s.geometry.dispose(); s.material.dispose(); sparks.splice(i, 1); }
    }
  }

  // ---- landing shockwave ring (tier-coloured) ----------------------------
  function shockwave(y, radius, color) {
    if (reduced) return;
    const m = new THREE.Mesh(
      new THREE.TorusGeometry(radius, 0.025, 6, 48),
      new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0.95, depthWrite: false })
    );
    m.rotation.x = Math.PI / 2;
    m.position.y  = y + 0.02;
    root.add(m);
    gsap.to(m.scale,    { x: 2.4, y: 2.4, z: 2.4, duration: 0.65, ease: 'power2.out' });
    gsap.to(m.material, { opacity: 0, duration: 0.65, ease: 'power1.out',
      onComplete: () => { root.remove(m); m.geometry.dispose(); m.material.dispose(); } });
  }

  // ---- capstone sun flash ------------------------------------------------
  function makeFlashTexture() {
    const c = document.createElement('canvas'); c.width = c.height = 256;
    const g = c.getContext('2d');
    // inner white core → yellow → transparent
    const r = g.createRadialGradient(128, 128, 2, 128, 128, 128);
    r.addColorStop(0,    'rgba(255,255,240,1)');
    r.addColorStop(0.18, 'rgba(255,238,50,0.95)');
    r.addColorStop(0.55, 'rgba(255,200,0,0.4)');
    r.addColorStop(1,    'rgba(255,200,0,0)');
    g.fillStyle = r; g.fillRect(0, 0, 256, 256);
    // 8 radial spikes
    g.save();
    for (let i = 0; i < 8; i++) {
      g.save();
      g.translate(128, 128); g.rotate((i / 8) * Math.PI * 2);
      const sp = g.createLinearGradient(0, 0, 100, 0);
      sp.addColorStop(0, 'rgba(255,240,100,0.6)');
      sp.addColorStop(1, 'rgba(255,240,100,0)');
      g.fillStyle = sp;
      g.beginPath(); g.moveTo(0, -3); g.lineTo(100, 0); g.lineTo(0, 3);
      g.closePath(); g.fill();
      g.restore();
    }
    g.restore();
    return new THREE.CanvasTexture(c);
  }
  const flash = new THREE.Sprite(new THREE.SpriteMaterial({
    map: makeFlashTexture(), transparent: true, opacity: 0,
    blending: THREE.AdditiveBlending, depthWrite: false,
  }));
  flash.position.set(0, yBase + H_TOTAL + 0.1, 0);
  flash.scale.setScalar(0.2);
  root.add(flash);

  // ---- ambient dust ------------------------------------------------------
  const dust = (function () {
    const N = 90;
    const pos = new Float32Array(N * 3);
    for (let i = 0; i < N; i++) {
      const rad = 1.4 + Math.random() * 2.6;
      const th  = Math.random() * Math.PI * 2;
      pos[i * 3]     = Math.cos(th) * rad;
      pos[i * 3 + 1] = yBase + Math.random() * (H_TOTAL + 1.4);
      pos[i * 3 + 2] = Math.sin(th) * rad;
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    const pts = new THREE.Points(geo, new THREE.PointsMaterial({
      color: 0xffcc88, size: 0.042, transparent: true, opacity: 0,
      blending: THREE.AdditiveBlending, depthWrite: false, sizeAttenuation: true,
    }));
    scene.add(pts);
    return pts;
  })();

  // ---- camera — straight-on with gentle upward angle ---------------------
  // Centred on the mark (x=0), slightly above midpoint, far enough to frame it
  const CAM_END = new THREE.Vector3(0, markCenterY + 0.55, 4.6);
  camera.position.copy(CAM_END).multiplyScalar(1.14);
  camera.lookAt(0, markCenterY, 0);
  if (!reduced) {
    gsap.to(camera.position, {
      x: CAM_END.x, y: CAM_END.y, z: CAM_END.z,
      duration: 4.4, ease: 'power1.inOut', delay: 0.3,
    });
  }

  function resize() {
    const w = mount.clientWidth || 1, h = mount.clientHeight || 1;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
  }
  window.addEventListener('resize', resize);
  resize();
  [80, 250, 600].forEach(ms => setTimeout(resize, ms));

  // ---- assembly timeline -------------------------------------------------
  let spin   = 0.14;
  let shakeY = 0;
  const tl   = gsap.timeline({ delay: 0.2, onUpdate: () => setProgress(tl.progress()) });

  // Phase 1 — blueprint appears, survey ring traces in
  tl.to(blueprint.material, { opacity: 0.7,  duration: 0.6, ease: 'power1.out' }, 0);
  tl.to(ring.material,      { opacity: 0.55, duration: 0.7, ease: 'power1.out' }, 0.1);
  tl.to(dust.material,      { opacity: 0.35, duration: 1.8, ease: 'power1.out' }, 0.3);

  // Phase 2 — crane each tier in, bottom to top
  const DROP = 2.8;
  tiers.forEach((t, i) => {
    const at = 0.9 + i * 0.60;
    t.group.position.y = t.y + DROP;

    tl.to(t.mesh.material, { opacity: 1, duration: 0.14, ease: 'power1.in'  }, at);
    tl.to(t.line.material, { opacity: 1, duration: 0.14, ease: 'power1.in'  }, at);
    tl.to(t.group.position, { y: t.y, duration: 0.48, ease: 'power2.in'     }, at);

    // impact
    tl.add(() => {
      const hw = wAt(t.y - yBase) * 0.85;
      sparkBurst(new THREE.Vector3( hw, t.y,  hw), t.color);
      sparkBurst(new THREE.Vector3(-hw, t.y, -hw), t.color);
      shockwave(t.y, wAt(t.y - yBase) * 1.6 + 0.2, t.color);
      gsap.fromTo(t.group.scale,
        { y: 0.78, x: 1.08, z: 1.08 },
        { y: 1, x: 1, z: 1, duration: 0.5, ease: 'elastic.out(1, 0.42)' }
      );
      gsap.fromTo({ s: 1 }, { s: 1 }, {
        s: 0, duration: 0.28, ease: 'power2.out',
        onStart:  () => { shakeY = 0.055; },
        onUpdate: function () { shakeY = 0.055 * this.targets()[0].s; },
      });
    }, at + 0.48);
  });

  // Phase 3 — topping out: sun flash, blueprint fades
  const topAt = 0.9 + (tiers.length - 1) * 0.60 + 0.52;
  tl.add(() => {
    gsap.fromTo(flash.material, { opacity: 1.2 }, { opacity: 0, duration: 1.0, ease: 'power2.out' });
    gsap.fromTo(flash.scale,    { x: 0.2, y: 0.2, z: 0.2 }, { x: 3.2, y: 3.2, z: 3.2, duration: 1.0, ease: 'power3.out' });
    gsap.to(blueprint.material, { opacity: 0, duration: 0.7, ease: 'power1.in' });
    gsap.to(ring.material,      { opacity: 0, duration: 0.7, ease: 'power1.in' });
  }, topAt);

  // Phase 4 — flourish: vivid tier pulse, spin up, curtain
  tl.add(() => {
    gsap.to({ s: spin }, { s: 0.6, duration: 0.5, onUpdate: function () { spin = this.targets()[0].s; } });
    gsap.to(root.scale, { x: 1.06, y: 1.06, z: 1.06, duration: 0.5, ease: 'power2.out' });
    tiers.forEach((t, i) => {
      gsap.to(t.mesh.material, {
        emissiveIntensity: 1.4, duration: 0.22,
        delay: i * 0.07, yoyo: true, repeat: 1,
      });
    });
  }, topAt + 0.3);
  tl.to({}, { duration: 0.8 });   // hold finished mark
  tl.add(() => liftCurtain());

  if (reducedMo) tl.timeScale(2.2);

  // ---- render loop -------------------------------------------------------
  let raf = 0, running = true, t = 0;
  function loop() {
    if (!running) return;
    raf = requestAnimationFrame(loop);
    t += 0.016;
    root.rotation.y  += spin * 0.016 * (reducedMo ? 0.2 : 1);
    root.position.y   = -shakeY + (reducedMo ? 0 : Math.sin(t * 0.75) * 0.011);
    dust.rotation.y  -= 0.0007;
    pA.intensity = 1.0 + Math.sin(t * 1.7) * 0.22;
    pB.intensity = 0.8 + Math.sin(t * 1.3 + 2) * 0.18;
    pC.intensity = 0.6 + Math.sin(t * 2.1 + 1) * 0.15;
    updateSparks(0.016);
    renderer.render(scene, camera);
  }
  loop();

  // safety: force-exit after 10s
  const safety = setTimeout(() => { if (overlay.parentNode) liftCurtain(); }, 10000);
  const stop = () => {
    running = false; cancelAnimationFrame(raf); clearTimeout(safety);
    window.removeEventListener('resize', resize);
    try { renderer.dispose(); } catch (e) {}
  };
  const mo = new MutationObserver(() => { if (!document.body.contains(overlay)) { stop(); mo.disconnect(); } });
  mo.observe(document.body, { childList: true });
})();
