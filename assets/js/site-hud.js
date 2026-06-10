/* ============================================================================
   site-hud.js — Radian H.A. Limited · "construction site HUD" (all design pages)
   Pairs with assets/css/site-hud.css. Zones adapt per page via body class.

   - Scaffold Lift Tower: scroll progress rendered as an erecting scaffold;
     each coupler joint is a section-nav button (LIFT 01 · HOME …)
   - Blueprint crosshair: drafting cursor + live X/Y/zone readout
   - Site status board: zone + ticking site time (bottom right)
   - Drawing title block: engineering sheet stamp injected into the hero
   - Weld-spark clicks: tiny spark pops on every pointer-down
============================================================================ */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  // Site "zones" per design page: [selector, tag], in page order.
  // The lift tower only erects when a page has 4+ zones; the crosshair and
  // status board work everywhere.
  var PAGE_ZONES = {
    'rad-home': [
      ['#home', 'Hero'], ['#cisrs', 'CISRS'], ['#getmie', 'Getmie'], ['#calendar', 'Calendar'],
      ['#gallery', 'Gallery'], ['#videos', 'Videos'], ['#contact', 'Contact'], ['#cta', 'Enroll'],
    ],
    'rad-about': [
      ['body', 'Intro'], ['#story', 'Story'], ['#team', 'Team'],
      ['#testimonials', 'Delegates'], ['#credentials', 'Credentials'], ['#go', 'Enroll'],
    ],
    'rad-start': [
      ['body', 'Sign-In'], ['#pathway', 'The Route'], ['#faq', 'Checklist'], ['#go', 'Permit'],
    ],
    'rad-cisrs': [
      ['body', 'Hero'], ['.overview-section', 'Overview'], ['#courses', 'Courses'],
      ['.courses-section', 'Catalogue'], ['.video-section', 'Videos'], ['#cta', 'Enroll'],
    ],
    'rad-getmie': [
      ['body', 'Hero'], ['.programme-track', 'At Height'],
      ['.programme-track.alt-bg', 'Rescue'], ['#cta', 'Enroll'],
    ],
    'rad-certificate': [['body', 'Portal']],
    'rad-course':      [['body', 'Course']],
    'rad-enrol':       [['body', 'Enroll']],
    'rad-news':        [['body', 'News']],
  };
  var ZONES = (function () {
    for (var k in PAGE_ZONES) if (document.body.classList.contains(k)) return PAGE_ZONES[k];
    return [['body', 'Site']];
  })();

  /* ── helpers ─────────────────────────────────────────────────────── */
  function el(tag, cls, parent) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (parent) parent.appendChild(n);
    return n;
  }
  function pad(n, w) { n = String(n); while (n.length < (w || 2)) n = '0' + n; return n; }

  function sectionTops() {
    return ZONES.map(function (z) {
      if (z[0] === 'body') return 0;
      var s = document.querySelector(z[0]);
      return s ? s.getBoundingClientRect().top + window.pageYOffset : 0;
    });
  }
  function currentZoneIndex(tops) {
    var probe = window.pageYOffset + window.innerHeight * 0.38;
    var idx = 0;
    for (var i = 0; i < tops.length; i++) if (tops[i] <= probe) idx = i;
    return idx;
  }

  /* ── 1 · SCAFFOLD LIFT TOWER ─────────────────────────────────────── */
  function buildTower() {
    var tower = el('div', '', document.body);
    tower.id = 'rhud-tower';
    el('div', 'rhud-rail l', tower);
    el('div', 'rhud-rail r', tower);
    var fill = el('div', 'rhud-fill', tower);

    var lifts = ZONES.map(function (z, i) {
      var b = el('button', 'rhud-lift', tower);
      // bottom lift = first section; stack upward like a real scaffold
      b.style.bottom = (i / (ZONES.length - 1) * 100) + '%';
      b.setAttribute('aria-label', 'Go to ' + z[1]);
      el('span', 'rhud-node', b);
      var tag = el('span', 'rhud-tag', b);
      tag.textContent = 'Lift ' + pad(i + 1) + ' · ' + z[1];
      b.addEventListener('click', function () {
        if (z[0] === 'body') { window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }); return; }
        var s = document.querySelector(z[0]);
        if (s) s.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' });
      });
      return b;
    });

    var plat = el('div', 'rhud-plat', tower);
    var pct  = el('div', 'rhud-pct', tower);
    pct.innerHTML = 'ERECTED <b>00%</b>';

    return { fill: fill, lifts: lifts, plat: plat, pct: pct.querySelector('b') };
  }

  /* ── 2 · BLUEPRINT CROSSHAIR ─────────────────────────────────────── */
  function buildCross() {
    if (!finePointer || reduced) return null;
    var c = el('div', '', document.body);
    c.id = 'rhud-cross';
    var x = el('div', 'rhud-x', c);
    var y = el('div', 'rhud-y', c);
    var ring = el('div', 'rhud-ring', c);
    var chip = el('div', 'rhud-chip', c);
    return { root: c, x: x, y: y, ring: ring, chip: chip };
  }

  /* ── 3 · SITE STATUS BOARD (+ blueprint mode toggle) ─────────────── */
  function buildStatus() {
    var s = el('div', '', document.body);
    s.id = 'rhud-status';
    el('span', 'rhud-beacon', s);
    var txt = el('span', '', s);
    var clock = el('span', 'rhud-clock', s);

    // 📐 blueprint mode — flips the whole page into drawing-office view
    var bp = el('button', 'rhud-bp-btn', s);
    bp.textContent = '📐 Blueprint';
    bp.setAttribute('aria-pressed', 'false');
    function setBp(on, save) {
      document.body.classList.toggle('rhud-bp', on);
      bp.classList.toggle('on', on);
      bp.setAttribute('aria-pressed', on ? 'true' : 'false');
      if (save) { try { localStorage.setItem('rhudBp', on ? '1' : ''); } catch (e) {} }
    }
    bp.addEventListener('click', function () {
      setBp(!document.body.classList.contains('rhud-bp'), true);
    });
    try { if (localStorage.getItem('rhudBp') === '1') setBp(true, false); } catch (e) {}

    return { zone: txt, clock: clock };
  }

  /* ── 5 · WELD-SPARK CLICKS ───────────────────────────────────────── */
  function initSparks() {
    if (reduced || !finePointer) return;
    document.addEventListener('pointerdown', function (e) {
      if (e.button !== 0) return;
      for (var i = 0; i < 7; i++) {
        var s = el('div', 'rhud-spark', document.body);
        s.style.left = e.clientX + 'px';
        s.style.top  = e.clientY + 'px';
        var ang  = Math.random() * Math.PI * 2;
        var dist = 18 + Math.random() * 34;
        var dx = Math.cos(ang) * dist;
        var dy = Math.sin(ang) * dist - 14;          // bias upward like real sparks
        var anim = s.animate([
          { transform: 'translate(0,0) scale(1)',                          opacity: 1 },
          { transform: 'translate(' + dx + 'px,' + (dy + 26) + 'px) scale(0.2)', opacity: 0 },
        ], { duration: 420 + Math.random() * 220, easing: 'cubic-bezier(0.2,0.6,0.4,1)' });
        anim.onfinish = (function (node) { return function () { node.remove(); }; })(s);
      }
    }, true);
  }

  /* ── boot ────────────────────────────────────────────────────────── */
  function boot() {
    var tower  = ZONES.length >= 4 ? buildTower() : null;   // short pages: no tower
    var cross  = buildCross();
    var status = buildStatus();
    initSparks();

    var tops = sectionTops();
    var refresh = function () { tops = sectionTops(); };
    window.addEventListener('resize', refresh);
    window.addEventListener('load', function () { setTimeout(refresh, 600); });
    setTimeout(refresh, 1500);  // after React render settles
    setTimeout(refresh, 4000);

    /* scroll-driven state */
    var ticking = false;
    function onScroll() {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        var max = document.documentElement.scrollHeight - window.innerHeight;
        var p = max > 0 ? Math.min(window.pageYOffset / max, 1) : 0;
        var zi = currentZoneIndex(tops);

        if (tower) {
          tower.fill.style.height = (p * 100) + '%';
          tower.plat.style.bottom = 'calc(' + (p * 100) + '% - ' + (p * 8) + 'px)';
          tower.pct.textContent = pad(Math.round(p * 100)) + '%';
          tower.lifts.forEach(function (b, i) {
            b.classList.toggle('done',   i <= zi);
            b.classList.toggle('active', i === zi);
          });
        }

        status.zone.innerHTML = 'Site active · Zone <b>' + ZONES[zi][1] + '</b> ·&nbsp;';
        ticking = false;
      });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* site clock */
    function tickClock() {
      var d = new Date();
      status.clock.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()) + ' site time';
    }
    tickClock();
    setInterval(tickClock, 1000);

    /* crosshair */
    if (cross) {
      var mx = -100, my = -100, cx = -100, cy = -100, raf = null, on = false;
      function loop() {
        cx += (mx - cx) * 0.22;
        cy += (my - cy) * 0.22;
        cross.x.style.transform = 'translateY(' + cy.toFixed(1) + 'px)';
        cross.y.style.transform = 'translateX(' + cx.toFixed(1) + 'px)';
        cross.ring.style.transform = 'translate(' + cx.toFixed(1) + 'px,' + cy.toFixed(1) + 'px)';
        cross.chip.style.transform = 'translate(' + cx.toFixed(1) + 'px,' + cy.toFixed(1) + 'px)';
        if (Math.abs(mx - cx) > 0.2 || Math.abs(my - cy) > 0.2) {
          raf = requestAnimationFrame(loop);
        } else { raf = null; }
      }
      document.addEventListener('pointermove', function (e) {
        mx = e.clientX; my = e.clientY;
        if (!on) { on = true; cross.root.classList.add('on'); }
        var zi = currentZoneIndex(tops);
        cross.chip.innerHTML =
          'X ' + pad(Math.round(e.clientX), 4) + ' · Y ' + pad(Math.round(e.clientY), 4) +
          ' · ZONE <b>' + ZONES[zi][1].toUpperCase() + '</b>';
        if (!raf) raf = requestAnimationFrame(loop);
      }, { passive: true });
      document.addEventListener('pointerleave', function () {
        on = false; cross.root.classList.remove('on');
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
