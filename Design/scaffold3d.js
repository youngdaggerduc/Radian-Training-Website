/* ============================================================================
   scaffold3d.js  —  Radian H.A. Limited
   A self-building, rotating, click-to-inspect 3D scaffold built on Three.js (r128)
   + GSAP for the assembly choreography.

   Interactions: drag to rotate (inertia), hover to glow, click to pop a part
   out with an info card + safety tip, Esc / click-away to release, double-
   click to zoom (pinch on touch). Weld sparks fire as the build lands, dust
   drifts in the floodlight, and rendering pauses while offscreen.

   Usage:
     const rig = createScaffold3D({
       container,            // DOM node to fill with the canvas
       variant,              // 'structure' (default) | 'rescue'
       bays, lifts,          // structure size (auto-reduced on small screens)
       accent,               // hex highlight colour (default brand orange)
       onReady,              // callback once built
     });
     rig.destroy();          // tear down

   Requires globals: THREE, gsap  (loaded via CDN before this file).
   Degrades gracefully: if THREE is missing the container keeps its CSS fallback.
============================================================================ */
(function () {
  'use strict';

  const ORANGE = 0xe8890a;
  const ORANGE_L = 0xffb547;
  const STEEL = 0x9fb4d0;
  const WOOD = 0xc69a5b;
  const RESCUE = 0xff7a7a;

  // ---- component copy ------------------------------------------------------
  const INFO = {
    standard:  { label: 'Standard',      tag: 'Vertical tube', desc: 'The upright tube that carries every load straight down to the base. Standards are the backbone of the scaffold.', tip: 'Inspect: plumb, undamaged, fully seated in its coupler.' },
    ledger:    { label: 'Ledger',        tag: 'Horizontal tie', desc: 'Runs horizontally along the face, tying the standards together and supporting the transoms above.', tip: 'Inspect: level, couplers locked, no excessive spans.' },
    transom:   { label: 'Transom',       tag: 'Cross member', desc: 'Spans across the ledgers from front to back, carrying the boards and squaring the bay.', tip: 'Spacing must support every board end — no overhangs past limits.' },
    brace:     { label: 'Diagonal brace', tag: 'Stiffener', desc: 'Resists sway and racking. Bracing keeps the whole structure rigid, square and stable under load.', tip: 'An unbraced scaffold is a scaffold waiting to move.' },
    baseplate: { label: 'Base plate',    tag: 'Foundation', desc: 'Spreads the load of each standard onto the sole board, keeping the scaffold level and grounded.', tip: 'Never pack with bricks — use sole boards on firm ground.' },
    board:     { label: 'Scaffold board', tag: 'Platform', desc: 'The timber working platform the operative stands on. Boards must be supported, level and free of defects.', tip: 'Reject split, warped or paint-hidden boards on sight.' },
    guardrail: { label: 'Guard rail',    tag: 'Edge protection', desc: 'Fitted at the platform edge to prevent operatives from falling. A core requirement for any working lift.', tip: 'Main rail ≈950mm; gap to mid rail under 470mm.' },
    toeboard:  { label: 'Toe board',     tag: 'Edge protection', desc: 'Stops tools, materials and debris being kicked off the edge of the platform onto people below.', tip: 'Minimum 150mm high — first defence against dropped tools.' },
    coupler:   { label: 'Coupler',       tag: 'Fitting', desc: 'The fitting that locks two tubes together. The right coupler in the right place makes the joint safe.' },
    // rescue variant
    anchor:    { label: 'Anchor point',  tag: 'Attachment', desc: 'The certified point the lifeline connects to. A sound anchor is the first link in the fall-arrest chain.', tip: 'Rated and inspected — never tie off to a guard rail.' },
    lifeline:  { label: 'Lifeline & lanyard', tag: 'Fall arrest', desc: 'The energy-absorbing line between the anchor and the harness. It arrests a fall and limits the shock load.', tip: 'The energy absorber needs clearance below to deploy.' },
    harness:   { label: 'Full-body harness', tag: 'Worn PPE', desc: 'Distributes arrest forces across the thighs, pelvis and chest, holding the casualty upright for rescue.', tip: 'Pre-use check: webbing, stitching, D-rings, buckles.' },
    casualty:  { label: 'Suspended worker', tag: 'The rescue', desc: 'After a fall, suspension trauma starts within minutes. A fast, practised rescue is the difference that saves a life.', tip: 'Plan to reach a suspended casualty in under 10 minutes.' },
  };

  function createScaffold3D(opts) {
    opts = opts || {};
    const container = opts.container;
    if (!container) return null;

    // graceful bail-out if libraries failed to load
    if (typeof THREE === 'undefined') {
      container.classList.add('s3d-nolib');
      return { destroy() {} };
    }

    const variant = opts.variant || 'structure';
    const accentHex = opts.accent || ORANGE;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const small = window.innerWidth < 760;
    const tiny = window.innerWidth < 480;

    // structure size, scaled down on small screens
    let BAYS = opts.bays || (variant === 'rescue' ? 1 : 2);
    let LIFTS = opts.lifts || (variant === 'rescue' ? 3 : 3);
    if (small) { BAYS = 1; LIFTS = variant === 'rescue' ? 3 : 2; }

    const BAY_W = 2.5, BAY_D = 1.9, LIFT_H = 1.9, TUBE_R = 0.052;

    // -- scene --------------------------------------------------------------
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    const renderer = new THREE.WebGLRenderer({ antialias: !tiny, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, small ? 1.6 : 2));
    renderer.setClearColor(0x000000, 0);
    renderer.outputEncoding = THREE.sRGBEncoding;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.18;
    container.appendChild(renderer.domElement);
    renderer.domElement.style.display = 'block';
    renderer.domElement.style.width = '100%';
    renderer.domElement.style.height = '100%';
    renderer.domElement.style.cursor = 'grab';
    renderer.domElement.style.touchAction = 'pan-y';
    // fade the canvas in once the first frames are up
    renderer.domElement.style.opacity = '0';
    renderer.domElement.style.transition = 'opacity 0.9s ease';
    requestAnimationFrame(() => { renderer.domElement.style.opacity = '1'; });

    // -- lights -------------------------------------------------------------
    scene.add(new THREE.AmbientLight(0x6c84ad, 0.55));
    scene.add(new THREE.HemisphereLight(0x44608c, 0x0a1220, 0.55));
    const key = new THREE.DirectionalLight(0xfff0d8, 1.3);
    key.position.set(6, 9, 7); scene.add(key);
    const rim = new THREE.DirectionalLight(0x4f7fd0, 0.9);
    rim.position.set(-7, 3, -6); scene.add(rim);
    const glow = new THREE.PointLight(accentHex, 0.9, 26);
    glow.position.set(-2, 1, 4); scene.add(glow);

    // -- environment: tiny procedural gradient cubemap so the steel reflects -
    function makeEnv() {
      const faceCanvases = [];
      for (let i = 0; i < 6; i++) {
        const c = document.createElement('canvas'); c.width = c.height = 64;
        const g = c.getContext('2d');
        const grad = g.createLinearGradient(0, 0, 0, 64);
        if (i === 2)      { grad.addColorStop(0, '#42587a'); grad.addColorStop(1, '#243a5c'); } // +Y sky
        else if (i === 3) { grad.addColorStop(0, '#0a1422'); grad.addColorStop(1, '#05090f'); } // -Y ground
        else              { grad.addColorStop(0, '#31466a'); grad.addColorStop(0.55, '#17273f'); grad.addColorStop(1, '#0a1628'); }
        g.fillStyle = grad; g.fillRect(0, 0, 64, 64);
        if (i === 0) { // +X — warm "floodlight" hotspot, gives the tubes a bright streak
          const r = g.createRadialGradient(32, 18, 2, 32, 18, 30);
          r.addColorStop(0, 'rgba(255,214,158,0.95)'); r.addColorStop(1, 'rgba(255,214,158,0)');
          g.fillStyle = r; g.fillRect(0, 0, 64, 64);
        }
        faceCanvases.push(c);
      }
      const tex = new THREE.CubeTexture(faceCanvases);
      tex.needsUpdate = true;
      return tex;
    }
    scene.environment = makeEnv();

    // -- materials ----------------------------------------------------------
    const matSteel = new THREE.MeshStandardMaterial({ color: STEEL, metalness: 0.85, roughness: 0.3, envMapIntensity: 0.9 });
    const matSteelDark = new THREE.MeshStandardMaterial({ color: 0x6a7f9e, metalness: 0.8, roughness: 0.45, envMapIntensity: 0.7 });
    const matWood = new THREE.MeshStandardMaterial({ color: WOOD, metalness: 0.05, roughness: 0.85 });
    const matOrange = new THREE.MeshStandardMaterial({ color: accentHex, metalness: 0.5, roughness: 0.4, emissive: accentHex, emissiveIntensity: 0.18, envMapIntensity: 0.6 });
    const matCoupler = new THREE.MeshStandardMaterial({ color: 0x3f4a5c, metalness: 0.7, roughness: 0.5, envMapIntensity: 0.6 });

    // -- groups -------------------------------------------------------------
    const root = new THREE.Group();          // spun by controls
    scene.add(root);
    const pieces = [];                       // pickable meshes
    let buildOrder = [];                     // {mesh, order}

    // shared geometries (lower poly on small screens)
    const radSeg = small ? 8 : 12;
    const tubeGeoCache = {};
    function tubeGeo(len) {
      const k = len.toFixed(3);
      if (!tubeGeoCache[k]) tubeGeoCache[k] = new THREE.CylinderGeometry(TUBE_R, TUBE_R, len, radSeg, 1, false);
      return tubeGeoCache[k];
    }
    const UP = new THREE.Vector3(0, 1, 0);
    const _a = new THREE.Vector3(), _b = new THREE.Vector3(), _d = new THREE.Vector3();

    let order = 0;
    function addTube(ax, ay, az, bx, by, bz, mat, kind) {
      _a.set(ax, ay, az); _b.set(bx, by, bz);
      const len = _a.distanceTo(_b);
      const mesh = new THREE.Mesh(tubeGeo(len), mat.clone());
      mesh.position.copy(_a).add(_b).multiplyScalar(0.5);
      _d.copy(_b).sub(_a).normalize();
      mesh.quaternion.setFromUnitVectors(UP, _d);
      register(mesh, kind);
      return mesh;
    }
    function addMesh(geo, mat, x, y, z, kind) {
      const mesh = new THREE.Mesh(geo, mat.clone());
      mesh.position.set(x, y, z);
      register(mesh, kind);
      return mesh;
    }
    function register(mesh, kind) {
      mesh.userData.kind = kind;
      mesh.userData.info = INFO[kind] || null;
      mesh.userData.baseScale = 1;
      mesh.userData.pickable = !!INFO[kind] && kind !== 'coupler';
      buildOrder.push({ mesh, order: order });
      if (mesh.userData.pickable) pieces.push(mesh);
      root.add(mesh);
    }

    // ---- BUILD THE SCAFFOLD ----------------------------------------------
    const totalW = BAYS * BAY_W;
    const totalH = LIFTS * LIFT_H;
    const x0 = -totalW / 2, z0 = -BAY_D / 2, y0 = -totalH / 2;
    const colX = []; for (let i = 0; i <= BAYS; i++) colX.push(x0 + i * BAY_W);
    const rowZ = [z0, z0 + BAY_D];

    function buildScaffold() {
      // base plates
      order = 0;
      colX.forEach(cx => rowZ.forEach(cz => {
        addMesh(new THREE.BoxGeometry(0.34, 0.12, 0.34), matOrange, cx, y0 - 0.02, cz, 'baseplate');
        addMesh(new THREE.CylinderGeometry(0.05, 0.05, 0.18, 8), matSteelDark, cx, y0 + 0.12, cz, 'coupler');
      }));

      for (let l = 0; l < LIFTS; l++) {
        const yb = y0 + l * LIFT_H;
        const yt = yb + LIFT_H;

        // standards (one segment per lift)
        order = 10 + l * 10;
        colX.forEach(cx => rowZ.forEach(cz => addTube(cx, yb, cz, cx, yt, cz, matSteel, 'standard')));

        // ledgers along X (front + back) at the top of the lift
        order = 12 + l * 10;
        rowZ.forEach(cz => {
          for (let i = 0; i < BAYS; i++) addTube(colX[i], yt, cz, colX[i + 1], yt, cz, matSteel, 'ledger');
        });

        // transoms across Z at the top of the lift
        order = 14 + l * 10;
        colX.forEach(cx => addTube(cx, yt, rowZ[0], cx, yt, rowZ[1], matSteelDark, 'transom'));

        // couplers at the joints (decorative)
        order = 15 + l * 10;
        colX.forEach(cx => rowZ.forEach(cz => {
          const c = new THREE.Mesh(new THREE.TorusGeometry(0.11, 0.04, 6, 10), matCoupler.clone());
          c.position.set(cx, yt, cz);
          register(c, 'coupler');
        }));

        // face brace (zig-zag on the front, alternating direction per lift)
        order = 16 + l * 10;
        const z = rowZ[0] - 0.01;
        if (l % 2 === 0) addTube(colX[0], yb, z, colX[Math.min(1, BAYS)], yt, z, matOrange, 'brace');
        else addTube(colX[Math.min(1, BAYS)], yb, z, colX[0], yt, z, matOrange, 'brace');
        if (BAYS > 1) {
          if (l % 2 === 0) addTube(colX[1], yb, z, colX[2], yt, z, matSteel, 'brace');
          else addTube(colX[2], yb, z, colX[1], yt, z, matSteel, 'brace');
        }
      }

      // ---- TOP WORKING LIFT: boards, guard rails, toe boards --------------
      const yDeck = y0 + totalH;
      order = 10 + LIFTS * 10 + 2;
      const boardCount = small ? 3 : 4;
      const boardW = BAY_D / boardCount * 0.92;
      for (let b = 0; b < boardCount; b++) {
        const bz = z0 + (b + 0.5) * (BAY_D / boardCount);
        addMesh(new THREE.BoxGeometry(totalW * 0.99, 0.05, boardW), matWood, 0, yDeck + 0.04, bz, 'board');
      }
      // guard rails (front + back) + mid rail, one lift up
      order += 2;
      const yRail = yDeck + LIFT_H * 0.55, yMid = yDeck + LIFT_H * 0.28;
      rowZ.forEach(cz => {
        addTube(colX[0], yRail, cz, colX[BAYS], yRail, cz, matSteel, 'guardrail');
        addTube(colX[0], yMid, cz, colX[BAYS], yMid, cz, matSteelDark, 'guardrail');
      });
      // short standards that carry the guard rails
      colX.forEach(cx => rowZ.forEach(cz => addTube(cx, yDeck, cz, cx, yRail, cz, matSteel, 'standard')));
      // toe boards
      order += 2;
      rowZ.forEach((cz, i) => {
        const off = i === 0 ? 0.06 : -0.06;
        addMesh(new THREE.BoxGeometry(totalW * 0.98, 0.22, 0.04), matWood, 0, yDeck + 0.16, cz + off, 'toeboard');
      });
    }

    // ---- RESCUE VARIANT EXTRAS -------------------------------------------
    let swayRoot = null;
    function buildRescue() {
      const hx = colX[0] + BAY_W * 0.5;        // hang in front of the first bay
      const yTop = y0 + totalH;
      const zAnchor = rowZ[0];
      const zHang = rowZ[0] - 1.1;             // worker hangs out in front

      // certified anchor point on the top ledger
      order = 200;
      const anchor = addMesh(new THREE.SphereGeometry(0.14, 14, 12), matOrange, hx, yTop, zAnchor, 'anchor');
      anchor.userData.explodeDir = new THREE.Vector3(0.4, 0.7, 1).normalize();

      // sway group: line + worker swing gently together, pivoting at the anchor
      swayRoot = new THREE.Group();
      swayRoot.position.set(hx, yTop, zAnchor);
      root.add(swayRoot);

      const hangLen = totalH * 0.6;
      const dz = zHang - zAnchor;

      // lifeline / lanyard (anchor -> harness) — a gently sagging rope, not a rod
      const ropeCurve = new THREE.CatmullRomCurve3([
        new THREE.Vector3(0, 0, 0),
        new THREE.Vector3(0, -hangLen * 0.45, dz * 0.32 - 0.12),
        new THREE.Vector3(0, -hangLen, dz),
      ]);
      const line = new THREE.Mesh(
        new THREE.TubeGeometry(ropeCurve, 22, 0.028, 6, false),
        new THREE.MeshStandardMaterial({ color: 0x55657e, metalness: 0.35, roughness: 0.7 })
      );
      line.userData.kind = 'lifeline'; line.userData.info = INFO.lifeline;
      line.userData.pickable = true; line.userData.baseScale = 1;
      line.userData.explodeDir = new THREE.Vector3(1, 0.2, 0.6).normalize();
      buildOrder.push({ mesh: line, order: 206 }); pieces.push(line); swayRoot.add(line);

      // worker figure: harness (torso) + head + limbs, hanging upright
      const figure = new THREE.Group();
      figure.position.set(0, -hangLen, dz);
      const matBody = new THREE.MeshStandardMaterial({ color: 0x2f4a78, metalness: 0.1, roughness: 0.8 });
      const matSkin = new THREE.MeshStandardMaterial({ color: 0xd8a07a, metalness: 0.05, roughness: 0.9 });
      const matHat = new THREE.MeshStandardMaterial({ color: accentHex, metalness: 0.3, roughness: 0.5 });

      const torso = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.62, 0.26), matBody.clone());
      torso.position.y = 0; figure.add(torso);
      // harness straps (orange) — the pickable PPE
      const harness = new THREE.Mesh(new THREE.BoxGeometry(0.46, 0.66, 0.3), new THREE.MeshStandardMaterial({ color: accentHex, metalness: 0.4, roughness: 0.4, emissive: accentHex, emissiveIntensity: 0.15, transparent: true, opacity: 1 }));
      harness.position.y = 0;
      harness.userData.kind = 'harness'; harness.userData.info = INFO.harness;
      harness.userData.pickable = true; harness.userData.baseScale = 1;
      harness.userData.explodeDir = new THREE.Vector3(-1, 0.2, 0.7).normalize();
      buildOrder.push({ mesh: harness, order: 208 }); pieces.push(harness); figure.add(harness);
      // head + hard hat
      const head = new THREE.Mesh(new THREE.SphereGeometry(0.15, 12, 10), matSkin.clone());
      head.position.y = 0.46; figure.add(head);
      const hat = new THREE.Mesh(new THREE.SphereGeometry(0.17, 12, 8, 0, Math.PI * 2, 0, Math.PI / 2), matHat.clone());
      hat.position.y = 0.5; figure.add(hat);
      // legs
      [-0.12, 0.12].forEach(x => {
        const leg = new THREE.Mesh(new THREE.BoxGeometry(0.14, 0.55, 0.16), matBody.clone());
        leg.position.set(x, -0.58, 0); figure.add(leg);
      });
      // arms
      [-0.31, 0.31].forEach(x => {
        const arm = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.5, 0.13), matBody.clone());
        arm.position.set(x, -0.05, 0); arm.rotation.z = x < 0 ? 0.25 : -0.25; figure.add(arm);
      });

      // register figure body parts in the build sequence (non-pickable except harness)
      figure.traverse(o => {
        if (o.isMesh && o !== harness && !o.userData.kind) {
          o.userData.kind = 'casualty'; o.userData.info = INFO.casualty;
          o.userData.pickable = false; o.userData.baseScale = 1;
          buildOrder.push({ mesh: o, order: 209 });
        }
      });
      swayRoot.add(figure);

      // pulsing safety ring around the anchor
      const ring = new THREE.Mesh(new THREE.TorusGeometry(0.4, 0.02, 8, 28), new THREE.MeshBasicMaterial({ color: accentHex, transparent: true, opacity: 0.5 }));
      ring.position.set(hx, yTop, zAnchor); ring.rotation.x = Math.PI / 2;
      ring.userData.kind = 'casualty'; ring.userData.baseScale = 1;
      ring.userData.isRing = true;
      buildOrder.push({ mesh: ring, order: 210 }); root.add(ring);
      rescueRing = ring;
    }

    let rescueRing = null;
    buildScaffold();
    if (variant === 'rescue') buildRescue();

    // ---- recompute pick list & order list ---------------------------------
    buildOrder.sort((a, b) => a.order - b.order);

    // remember each piece's resting emissive so hover/select can restore it
    buildOrder.forEach(o => {
      const m = o.mesh.material;
      if (m && m.emissive) {
        o.mesh.userData.baseEmissiveHex = m.emissive.getHex();
        o.mesh.userData.baseEmissiveI = m.emissiveIntensity || 0;
      }
    });

    // ---- framing the camera ----------------------------------------------
    const box = new THREE.Box3().setFromObject(root);
    const sphere = box.getBoundingSphere(new THREE.Sphere());
    const fitDist = sphere.radius / Math.sin((camera.fov * Math.PI / 180) / 2);
    const camDist = fitDist * (small ? 1.15 : 1.0);
    camera.position.set(camDist * 0.55, sphere.radius * 0.35, camDist * 0.95);
    camera.lookAt(0, 0, 0);

    // structural centre for the explode direction
    const CENTER = new THREE.Vector3(0, 0, 0);

    // ---- SITE GROUND: contact shadow + surveyor's ring --------------------
    const ground = (function () {
      const c = document.createElement('canvas'); c.width = c.height = 256;
      const g = c.getContext('2d');
      let r = g.createRadialGradient(128, 128, 10, 128, 128, 124);
      r.addColorStop(0, 'rgba(2,6,12,0.55)'); r.addColorStop(0.55, 'rgba(2,6,12,0.22)'); r.addColorStop(1, 'rgba(2,6,12,0)');
      g.fillStyle = r; g.fillRect(0, 0, 256, 256);
      g.strokeStyle = 'rgba(232,137,10,0.35)'; g.lineWidth = 1.6;
      g.beginPath(); g.arc(128, 128, 96, 0, Math.PI * 2); g.stroke();
      g.strokeStyle = 'rgba(138,164,200,0.14)';
      g.beginPath(); g.arc(128, 128, 116, 0, Math.PI * 2); g.stroke();
      g.strokeStyle = 'rgba(232,137,10,0.4)';
      for (let i = 0; i < 24; i++) {
        const a = (i / 24) * Math.PI * 2;
        g.beginPath();
        g.moveTo(128 + Math.cos(a) * 92, 128 + Math.sin(a) * 92);
        g.lineTo(128 + Math.cos(a) * 100, 128 + Math.sin(a) * 100);
        g.stroke();
      }
      const mesh = new THREE.Mesh(
        new THREE.CircleGeometry(sphere.radius * 1.3, 48),
        new THREE.MeshBasicMaterial({ map: new THREE.CanvasTexture(c), transparent: true, opacity: 0, depthWrite: false })
      );
      mesh.rotation.x = -Math.PI / 2;
      mesh.position.y = y0 - 0.09;
      root.add(mesh);
      return mesh;
    })();

    // ---- DUST MOTES: slow-drifting atmosphere ------------------------------
    const dust = (function () {
      const N = small ? 50 : 110;
      const pos = new Float32Array(N * 3);
      for (let i = 0; i < N; i++) {
        const rad = sphere.radius * (0.5 + Math.random() * 1.05);
        const th = Math.random() * Math.PI * 2;
        pos[i * 3] = Math.cos(th) * rad;
        pos[i * 3 + 1] = (Math.random() - 0.35) * totalH * 1.3;
        pos[i * 3 + 2] = Math.sin(th) * rad;
      }
      const geo = new THREE.BufferGeometry();
      geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
      const pts = new THREE.Points(geo, new THREE.PointsMaterial({
        color: 0xffc070, size: 0.05, transparent: true, opacity: 0,
        blending: THREE.AdditiveBlending, depthWrite: false, sizeAttenuation: true,
      }));
      scene.add(pts);
      return pts;
    })();

    // ---- WELD SPARKS: short-lived bursts at landing joints -----------------
    const sparks = [];
    function sparkBurst(p) {
      if (reduced) return;
      const n = 10;
      const pos = new Float32Array(n * 3);
      const vel = [];
      for (let i = 0; i < n; i++) {
        pos[i * 3] = p.x; pos[i * 3 + 1] = p.y; pos[i * 3 + 2] = p.z;
        vel.push(new THREE.Vector3((Math.random() - 0.5) * 2.6, Math.random() * 2.2 + 0.7, (Math.random() - 0.5) * 2.6));
      }
      const geo = new THREE.BufferGeometry();
      geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
      const pts = new THREE.Points(geo, new THREE.PointsMaterial({
        color: 0xffd9a0, size: 0.07, transparent: true, opacity: 1,
        blending: THREE.AdditiveBlending, depthWrite: false,
      }));
      pts.userData = { vel: vel, life: 0 };
      root.add(pts);
      sparks.push(pts);
    }
    function updateSparks(dt) {
      for (let i = sparks.length - 1; i >= 0; i--) {
        const s = sparks[i];
        s.userData.life += dt;
        const k = s.userData.life / 0.6;
        const a = s.geometry.attributes.position;
        for (let j = 0; j < s.userData.vel.length; j++) {
          const v = s.userData.vel[j];
          a.array[j * 3] += v.x * dt; a.array[j * 3 + 1] += v.y * dt; a.array[j * 3 + 2] += v.z * dt;
          v.y -= 5.5 * dt;
        }
        a.needsUpdate = true;
        s.material.opacity = Math.max(0, 1 - k);
        if (k >= 1) {
          root.remove(s); s.geometry.dispose(); s.material.dispose();
          sparks.splice(i, 1);
        }
      }
    }

    // ---- ASSEMBLY ANIMATION ----------------------------------------------
    let built = false;
    function playBuild() {
      if (built) return; built = true;
      const groups = {};
      buildOrder.forEach(o => { (groups[o.order] = groups[o.order] || []).push(o.mesh); });
      const keys = Object.keys(groups).map(Number).sort((a, b) => a - b);

      if (reduced || typeof gsap === 'undefined') {
        // no motion: just show everything
        buildOrder.forEach(o => { o.mesh.scale.setScalar(1); o.mesh.material.opacity = 1; });
        ground.material.opacity = 1;
        dust.material.opacity = 0.3;
        return;
      }
      // ground + atmosphere ease in under the build
      gsap.to(ground.material, { opacity: 1, duration: 1.4, ease: 'power2.out', delay: 0.2 });
      gsap.to(dust.material, { opacity: 0.32, duration: 2.2, ease: 'power1.out', delay: 0.6 });
      // start hidden: dropped a little + scaled down + transparent
      buildOrder.forEach(o => {
        o.mesh.material.transparent = true; o.mesh.material.opacity = 0;
        o.mesh.userData.ty = o.mesh.position.y;
        o.mesh.position.y += 1.4;
        o.mesh.scale.setScalar(0.01);
      });
      const tl = gsap.timeline({ delay: 0.15 });
      keys.forEach((k, gi) => {
        const meshes = groups[k];
        tl.to(meshes.map(m => m.scale), { x: 1, y: 1, z: 1, duration: 0.5, ease: 'back.out(1.7)' }, gi * 0.11);
        meshes.forEach(m => {
          tl.to(m.position, { y: m.userData.ty, duration: 0.55, ease: 'power3.out' }, gi * 0.11);
          tl.to(m.material, { opacity: 1, duration: 0.4, ease: 'power1.out',
            onComplete: () => { if (m.userData.kind === 'board' || m.userData.kind === 'toeboard') { m.material.transparent = false; } } }, gi * 0.11);
        });
        // weld flash as every other group lands home
        if (gi % 2 === 1) {
          const m = meshes[Math.floor(Math.random() * meshes.length)];
          tl.add(() => sparkBurst(m.position), gi * 0.11 + 0.42);
        }
      });
      tl.eventCallback('onComplete', () => { if (opts.onReady) opts.onReady(); });
    }

    // ---- CONTROLS: drag-rotate + inertia + auto-rotate -------------------
    let dragging = false, lastX = 0, lastY = 0, velY = 0, velX = 0;
    let targetRotX = 0.12, curRotX = 0.12;
    let autoRotate = !reduced;
    let selected = null;

    function onDown(e) {
      dragging = true; velY = 0; velX = 0;
      lastX = (e.touches ? e.touches[0].clientX : e.clientX);
      lastY = (e.touches ? e.touches[0].clientY : e.clientY);
      renderer.domElement.style.cursor = 'grabbing';
      moved = 0;
    }
    let moved = 0;
    function onMove(e) {
      const cx = (e.touches ? e.touches[0].clientX : e.clientX);
      const cy = (e.touches ? e.touches[0].clientY : e.clientY);
      // hover cursor when not dragging
      if (!dragging) { updateHoverCursor(cx, cy); return; }
      const dx = cx - lastX, dy = cy - lastY;
      lastX = cx; lastY = cy; moved += Math.abs(dx) + Math.abs(dy);
      root.rotation.y += dx * 0.006;
      targetRotX = Math.max(-0.5, Math.min(0.6, targetRotX + dy * 0.004));
      velY = dx * 0.006; velX = dy * 0.004;
    }
    function onUp() { dragging = false; renderer.domElement.style.cursor = 'grab'; }

    const ray = new THREE.Raycaster();
    const ndc = new THREE.Vector2();
    function pickAt(clientX, clientY) {
      const r = renderer.domElement.getBoundingClientRect();
      ndc.x = ((clientX - r.left) / r.width) * 2 - 1;
      ndc.y = -((clientY - r.top) / r.height) * 2 + 1;
      ray.setFromCamera(ndc, camera);
      const hits = ray.intersectObjects(pieces, false);
      return hits.length ? hits[0].object : null;
    }
    // hovered piece glows amber so interactivity is discoverable
    let hovered = null;
    function setHover(mesh) {
      if (hovered === mesh) return;
      if (hovered && hovered !== selected && hovered.material.emissive) {
        const u = hovered.userData;
        hovered.material.emissive.setHex(u.baseEmissiveHex || 0x000000);
        if (typeof gsap !== 'undefined') gsap.to(hovered.material, { emissiveIntensity: u.baseEmissiveI || 0, duration: 0.3 });
        else hovered.material.emissiveIntensity = u.baseEmissiveI || 0;
      }
      hovered = mesh;
      if (mesh && mesh !== selected && mesh.material.emissive) {
        mesh.material.emissive.setHex(accentHex);
        if (typeof gsap !== 'undefined') gsap.to(mesh.material, { emissiveIntensity: 0.45, duration: 0.25 });
        else mesh.material.emissiveIntensity = 0.45;
      }
    }
    function updateHoverCursor(cx, cy) {
      if (selected) { renderer.domElement.style.cursor = 'pointer'; return; }
      const hit = pickAt(cx, cy);
      setHover(hit);
      renderer.domElement.style.cursor = hit ? 'pointer' : 'grab';
    }

    function onClick(e) {
      if (moved > 6) return;            // was a drag, not a click
      const cx = (e.changedTouches ? e.changedTouches[0].clientX : e.clientX);
      const cy = (e.changedTouches ? e.changedTouches[0].clientY : e.clientY);
      const hit = pickAt(cx, cy);
      if (hit) selectPiece(hit); else deselect();
    }

    // ---- SELECTION / POP-OUT ---------------------------------------------
    // expanding "sonar ping" ring marks the picked piece
    let ping = null, pingT = 0;
    function spawnPing(worldPos) {
      if (ping) { scene.remove(ping); ping.geometry.dispose(); ping.material.dispose(); }
      ping = new THREE.Mesh(
        new THREE.TorusGeometry(0.32, 0.016, 8, 36),
        new THREE.MeshBasicMaterial({ color: accentHex, transparent: true, opacity: 0.9, depthWrite: false })
      );
      ping.position.copy(worldPos);
      pingT = 0;
      scene.add(ping);
    }

    const _wp = new THREE.Vector3();
    function selectPiece(mesh) {
      if (selected === mesh) { deselect(); return; }
      if (selected) restore(selected, true);
      setHover(null);
      selected = mesh;
      autoRotate = false;
      // dim everyone else
      buildOrder.forEach(o => {
        if (o.mesh === mesh) return;
        if (typeof gsap !== 'undefined') gsap.to(o.mesh.material, { opacity: 0.16, duration: 0.4 });
        else o.mesh.material.opacity = 0.16;
        o.mesh.material.transparent = true;
      });
      // explode direction = outward from centre (fallback to camera-ish)
      const dir = mesh.userData.explodeDir || mesh.position.clone().sub(CENTER);
      if (dir.length() < 0.4) dir.set(0.6, 0.3, 1);
      dir.normalize();
      mesh.userData.homePos = mesh.position.clone();
      mesh.userData.homeScale = mesh.scale.x;
      const dest = mesh.userData.homePos.clone().add(dir.multiplyScalar(sphere.radius * 0.55));
      spawnPing(mesh.getWorldPosition(_wp));
      mesh.material.emissive = new THREE.Color(accentHex);
      if (typeof gsap !== 'undefined') {
        gsap.to(mesh.position, { x: dest.x, y: dest.y, z: dest.z, duration: 0.6, ease: 'power3.out' });
        gsap.to(mesh.scale, { x: mesh.userData.homeScale * 1.55, y: mesh.userData.homeScale * 1.55, z: mesh.userData.homeScale * 1.55, duration: 0.6, ease: 'back.out(1.6)' });
        gsap.to(mesh.material, { opacity: 1, emissiveIntensity: 0.6, duration: 0.4 });
      } else {
        mesh.position.copy(dest); mesh.material.opacity = 1;
      }
      showCard(mesh.userData.info);
    }
    function restore(mesh, quiet) {
      if (!mesh.userData.homePos) return;
      const hp = mesh.userData.homePos, hs = mesh.userData.homeScale;
      if (typeof gsap !== 'undefined') {
        gsap.to(mesh.position, { x: hp.x, y: hp.y, z: hp.z, duration: 0.5, ease: 'power3.inOut' });
        gsap.to(mesh.scale, { x: hs, y: hs, z: hs, duration: 0.5, ease: 'power3.inOut' });
        gsap.to(mesh.material, { emissiveIntensity: mesh.userData.baseEmissiveI || 0, duration: 0.4,
          onComplete: () => { if (mesh.material.emissive) mesh.material.emissive.setHex(mesh.userData.baseEmissiveHex || 0x000000); } });
      } else {
        mesh.position.copy(hp); mesh.scale.setScalar(hs);
        if (mesh.material.emissive) {
          mesh.material.emissive.setHex(mesh.userData.baseEmissiveHex || 0x000000);
          mesh.material.emissiveIntensity = mesh.userData.baseEmissiveI || 0;
        }
      }
    }
    function deselect() {
      if (!selected) return;
      restore(selected);
      const wasBoard = ['board', 'toeboard'].includes(selected.userData.kind);
      selected = null;
      buildOrder.forEach(o => {
        if (typeof gsap !== 'undefined') gsap.to(o.mesh.material, { opacity: 1, duration: 0.45,
          onComplete: () => { if (['board', 'toeboard'].includes(o.mesh.userData.kind)) o.mesh.material.transparent = false; } });
        else o.mesh.material.opacity = 1;
      });
      hideCard();
      autoRotate = !reduced;
    }

    // ---- INFO CARD (HTML overlay) ----------------------------------------
    let card = null;
    function ensureCard() {
      if (card) return card;
      card = document.createElement('div');
      card.className = 's3d-card';
      card.innerHTML = '<button class="s3d-card-x" aria-label="Close">✕</button>' +
        '<div class="s3d-card-tag"></div><div class="s3d-card-title"></div><div class="s3d-card-desc"></div>' +
        '<div class="s3d-card-tip"></div>';
      container.appendChild(card);
      card.querySelector('.s3d-card-x').addEventListener('click', (ev) => { ev.stopPropagation(); deselect(); });
      return card;
    }
    function showCard(info) {
      if (!info) return;
      ensureCard();
      card.querySelector('.s3d-card-tag').textContent = info.tag;
      card.querySelector('.s3d-card-title').textContent = info.label;
      card.querySelector('.s3d-card-desc').textContent = info.desc;
      const tipEl = card.querySelector('.s3d-card-tip');
      tipEl.textContent = info.tip || '';
      tipEl.style.display = info.tip ? '' : 'none';
      card.classList.add('show');
    }
    function hideCard() { if (card) card.classList.remove('show'); }

    // ---- ZOOM: double-click toggle (desktop) + pinch (touch) --------------
    const camBase = camera.position.clone();
    let zoomLevel = 1;
    function setZoom(z, animate) {
      zoomLevel = Math.max(0.62, Math.min(1.5, z));
      const dest = camBase.clone().multiplyScalar(zoomLevel);
      if (animate && typeof gsap !== 'undefined') {
        gsap.to(camera.position, { x: dest.x, y: dest.y, z: dest.z, duration: 0.7, ease: 'power3.out' });
      } else {
        camera.position.copy(dest);
      }
    }
    function onDblClick() { setZoom(zoomLevel > 0.85 ? 0.7 : 1, true); }

    let pinchD = 0;
    function touchDist(t) { return Math.hypot(t[0].clientX - t[1].clientX, t[0].clientY - t[1].clientY); }
    function onPinchStart(e) {
      if (e.touches.length === 2) { dragging = false; pinchD = touchDist(e.touches); }
    }
    function onPinchMove(e) {
      if (e.touches.length !== 2 || !pinchD) return;
      e.preventDefault();
      const d = touchDist(e.touches);
      setZoom(zoomLevel * (pinchD / d), false);
      pinchD = d;
    }

    // Esc releases the selected piece
    function onKey(e) { if (e.key === 'Escape') deselect(); }

    // ---- events -----------------------------------------------------------
    const el = renderer.domElement;
    el.addEventListener('mousedown', onDown);
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
    el.addEventListener('mousemove', (e) => { if (!dragging) updateHoverCursor(e.clientX, e.clientY); });
    el.addEventListener('click', onClick);
    el.addEventListener('dblclick', onDblClick);
    el.addEventListener('touchstart', onDown, { passive: true });
    el.addEventListener('touchstart', onPinchStart, { passive: true });
    el.addEventListener('touchmove', onMove, { passive: true });
    el.addEventListener('touchmove', onPinchMove, { passive: false });
    el.addEventListener('touchend', (e) => { onUp(); if (e.touches.length === 0) pinchD = 0; onClick(e); }, { passive: true });
    window.addEventListener('keydown', onKey);

    // ---- resize -----------------------------------------------------------
    function resize() {
      const w = container.clientWidth || 1, h = container.clientHeight || 1;
      renderer.setSize(w, h, false);
      camera.aspect = w / h; camera.updateProjectionMatrix();
    }
    // Don't rely on ResizeObserver alone (it can stay silent in some embeds).
    // Use it when present AND always listen to window resize + re-sync as the
    // layout settles, so the buffer never gets stuck at the wrong aspect.
    const ro = ('ResizeObserver' in window) ? new ResizeObserver(resize) : null;
    if (ro) ro.observe(container);
    window.addEventListener('resize', resize);
    resize();
    requestAnimationFrame(resize);
    [120, 350, 800, 1500].forEach(ms => setTimeout(resize, ms));
    window.addEventListener('load', resize);

    // ---- render loop ------------------------------------------------------
    let raf = 0, t = 0, running = true, visible = true;
    function loop() {
      if (!running || !visible) { raf = 0; return; }
      raf = requestAnimationFrame(loop);
      t += 0.016;
      if (!dragging) {
        if (autoRotate) root.rotation.y += 0.0026;
        // inertia
        root.rotation.y += velY; velY *= 0.94;
        targetRotX += velX; velX *= 0.9;
      }
      curRotX += (targetRotX - curRotX) * 0.08;
      root.rotation.x = curRotX;
      // idle bob — the structure breathes
      if (!reduced) root.position.y = Math.sin(t * 0.7) * 0.025;
      // atmosphere drift
      dust.rotation.y -= 0.0007;
      dust.position.y = Math.sin(t * 0.4) * 0.06;
      updateSparks(0.016);
      // selection ping — billboard to camera while it expands
      if (ping) {
        pingT += 0.016;
        const k = pingT / 0.9;
        ping.scale.setScalar(1 + k * 3);
        ping.material.opacity = 0.9 * (1 - k);
        ping.quaternion.copy(camera.quaternion);
        if (k >= 1) { scene.remove(ping); ping.geometry.dispose(); ping.material.dispose(); ping = null; }
      }
      // rescue sway — compound pendulum, not a metronome
      if (swayRoot) {
        swayRoot.rotation.z = Math.sin(t * 0.9) * 0.07;
        swayRoot.rotation.x = Math.sin(t * 0.63 + 1.2) * 0.045;
      }
      if (rescueRing) {
        const p = (t * 0.6) % 1;
        rescueRing.scale.setScalar(0.6 + p * 2.4);
        rescueRing.material.opacity = 0.5 * (1 - p);
      }
      glow.intensity = 0.7 + Math.sin(t * 1.6) * 0.25;
      renderer.render(scene, camera);
    }
    loop();

    // pause rendering entirely while the canvas is offscreen
    let ioVis = null;
    if ('IntersectionObserver' in window) {
      ioVis = new IntersectionObserver((ents) => {
        ents.forEach(en => {
          visible = en.isIntersecting;
          if (visible && running && !raf) loop();
        });
      }, { threshold: 0 });
      ioVis.observe(container);
    }

    // kick off assembly when the container scrolls into view (or immediately)
    let started = false;
    function start() { if (started) return; started = true; playBuild(); }
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((ents) => {
        ents.forEach(e => { if (e.isIntersecting) { start(); io.disconnect(); } });
      }, { threshold: 0.15 });
      io.observe(container);
      // safety: start after 1.2s regardless
      setTimeout(start, 1200);
    } else { start(); }

    // ---- public API -------------------------------------------------------
    return {
      destroy() {
        running = false; cancelAnimationFrame(raf);
        if (ro) ro.disconnect();
        if (ioVis) ioVis.disconnect();
        window.removeEventListener('resize', resize);
        window.removeEventListener('load', resize);
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onUp);
        window.removeEventListener('keydown', onKey);
        sparks.forEach(s => { s.geometry.dispose(); s.material.dispose(); });
        if (ping) { ping.geometry.dispose(); ping.material.dispose(); }
        dust.geometry.dispose(); dust.material.dispose();
        ground.geometry.dispose(); ground.material.map && ground.material.map.dispose(); ground.material.dispose();
        renderer.dispose();
        if (renderer.domElement.parentNode) renderer.domElement.parentNode.removeChild(renderer.domElement);
        if (card && card.parentNode) card.parentNode.removeChild(card);
      },
      deselect,
      get selected() { return selected; },
    };
  }

  window.createScaffold3D = createScaffold3D;
})();
