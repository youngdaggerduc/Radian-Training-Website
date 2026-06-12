/* ============================================================================
   polish.js — Radian H.A. Limited
   Shared micro-interaction engine (vanilla, no dependencies).
   Pairs with assets/css/polish.css. Loaded on every design page.

   - Scroll progress hairline + smart hide/reveal nav
   - Magnetic buttons (cursor-follow with lerp)
   - Cursor spotlight on cards
   - Ambient hero parallax (decorative layers only)
   - Film grain overlay

   All pointer effects are skipped on touch devices and when the user
   prefers reduced motion. Everything is wired with event delegation so
   React re-renders never orphan a listener.
============================================================================ */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  /* ── Scroll progress hairline + smart nav ─────────────────────────── */
  function initScrollUI() {
    var bar = document.createElement('div');
    bar.id = 'rad-progress';
    document.body.appendChild(bar);

    var nav = document.getElementById('navbar');
    var lastY = window.pageYOffset;
    var ticking = false;

    function onScroll() {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        var y = window.pageYOffset;
        var max = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(y / max, 1) : 0) + ')';

        if (nav && !reduced) {
          var delta = y - lastY;
          if (y > 320 && delta > 6) nav.classList.add('rad-nav-hidden');
          else if (delta < -6 || y <= 320) nav.classList.remove('rad-nav-hidden');
        }
        lastY = y;
        ticking = false;
      });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Film grain ───────────────────────────────────────────────────── */
  function initGrain() {
    if (reduced) return;
    var g = document.createElement('div');
    g.id = 'rad-grain';
    document.body.appendChild(g);
  }

  /* ── Magnetic buttons ─────────────────────────────────────────────── */
  var MAGNET_SEL = '.nav-cta, .btn-primary, .btn-outline, .sb-btn';

  function initMagnets() {
    if (reduced || !finePointer) return;
    var bound = new WeakSet();

    document.addEventListener('pointerover', function (e) {
      var btn = e.target.closest && e.target.closest(MAGNET_SEL);
      if (!btn || bound.has(btn)) return;
      bound.add(btn);
      attachMagnet(btn);
    }, true);
  }

  function attachMagnet(btn) {
    var tx = 0, ty = 0, cx = 0, cy = 0, raf = null, inside = false;

    function loop() {
      cx += (tx - cx) * 0.18;
      cy += (ty - cy) * 0.18;
      if (!inside && Math.abs(cx) < 0.1 && Math.abs(cy) < 0.1) {
        btn.style.transform = '';
        raf = null;
        return;
      }
      btn.style.transform = 'translate(' + cx.toFixed(2) + 'px,' + cy.toFixed(2) + 'px)';
      raf = requestAnimationFrame(loop);
    }
    function start() { if (!raf) raf = requestAnimationFrame(loop); }

    btn.addEventListener('pointerenter', function () { inside = true; start(); });
    btn.addEventListener('pointermove', function (e) {
      var r = btn.getBoundingClientRect();
      tx = (e.clientX - r.left - r.width / 2) * 0.22;
      ty = (e.clientY - r.top - r.height / 2) * 0.3;
      start();
    });
    btn.addEventListener('pointerleave', function () {
      inside = false; tx = 0; ty = 0; start();
    });
  }

  /* ── Cursor spotlight on cards ────────────────────────────────────── */
  var SPOT_SEL = [
    '.course-card', '.stat-card', '.area-card', '.s3d-card', '.getmie-card',
    '.meta-card', '.related-card', '.sb-card', '.date-card', '.people-card',
    '.scanner-card', '.pillar', '.hero-stat', '.video-card', '.tilt-card'
  ].join(', ');

  function initSpotlight() {
    if (reduced || !finePointer) return;

    document.addEventListener('pointerover', function (e) {
      var card = e.target.closest && e.target.closest(SPOT_SEL);
      if (!card) return;
      card.classList.add('rad-spot');
      if (getComputedStyle(card).position === 'static') card.style.position = 'relative';
      // (Re)inject overlay — React re-renders can drop it
      if (!card.querySelector(':scope > .rad-glow')) {
        var glow = document.createElement('span');
        glow.className = 'rad-glow';
        card.appendChild(glow);
      }
    }, true);

    document.addEventListener('pointermove', function (e) {
      var card = e.target.closest && e.target.closest(SPOT_SEL);
      if (!card) return;
      var r = card.getBoundingClientRect();
      card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
      card.style.setProperty('--my', (e.clientY - r.top) + 'px');
    }, true);
  }

  /* ── Ambient hero parallax (decorative layers only) ───────────────── */
  function initHeroParallax() {
    if (reduced || !finePointer) return;
    var hero = document.querySelector('.hero');
    if (!hero) return;
    var layers = [
      { el: hero.querySelector('.scaffold-bg'), amt: 16 },
      { el: hero.querySelector('.scaffold-poles'), amt: 9 }
    ].filter(function (l) { return l.el; });
    if (!layers.length) return;

    var tx = 0, ty = 0, cx = 0, cy = 0, raf = null;

    function loop() {
      cx += (tx - cx) * 0.045;
      cy += (ty - cy) * 0.045;
      layers.forEach(function (l) {
        l.el.style.transform = 'translate(' + (cx * l.amt).toFixed(2) + 'px,' + (cy * l.amt).toFixed(2) + 'px)';
      });
      if (Math.abs(tx - cx) > 0.001 || Math.abs(ty - cy) > 0.001) {
        raf = requestAnimationFrame(loop);
      } else { raf = null; }
    }
    hero.addEventListener('pointermove', function (e) {
      tx = (e.clientX / window.innerWidth - 0.5) * 2;   // -1 .. 1
      ty = (e.clientY / window.innerHeight - 0.5) * 2;
      if (!raf) raf = requestAnimationFrame(loop);
    });
    hero.addEventListener('pointerleave', function () {
      tx = 0; ty = 0;
      if (!raf) raf = requestAnimationFrame(loop);
    });
  }

  /* ── Harness-clip anchors: carabiner clips on, page abseils down ───── */
  function initAnchorClips() {
    if (reduced) return;   // native jump behaviour for reduced motion
    document.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[href*="#"]');
      if (!a) return;
      if (a.host !== location.host || a.pathname !== location.pathname) return;
      var id = decodeURIComponent((a.hash || '').slice(1));
      if (!id) return;
      var target = document.getElementById(id);
      if (!target) return;
      e.preventDefault();
      clipAndScroll(a, target, id);
    }, true);
  }

  function clipAndScroll(link, target, id) {
    // carabiner clips onto the link
    var r = link.getBoundingClientRect();
    var clip = document.createElement('span');
    clip.className = 'rad-clip';
    clip.style.left = (r.right + 5) + 'px';
    clip.style.top = (r.top + r.height / 2) + 'px';
    document.body.appendChild(clip);
    requestAnimationFrame(function () { clip.classList.add('on'); });

    // abseil: custom eased scroll, cancellable by the user
    var startY = window.pageYOffset;
    var maxY = document.documentElement.scrollHeight - window.innerHeight;
    var endY = Math.max(0, Math.min(target.getBoundingClientRect().top + startY - 88, maxY));
    var dur = Math.min(1100, 420 + Math.abs(endY - startY) * 0.22);
    var t0 = null, cancelled = false;
    var cancel = function () { cancelled = true; };
    window.addEventListener('wheel', cancel, { once: true, passive: true });
    window.addEventListener('touchstart', cancel, { once: true, passive: true });

    var html = document.documentElement;
    var prevSB = html.style.scrollBehavior;
    html.style.scrollBehavior = 'auto';   // keep CSS smooth-scroll out of our way

    function ease(t) { return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; }
    function done() {
      html.style.scrollBehavior = prevSB;
      if (history.replaceState) history.replaceState(null, '', '#' + id);
      clip.classList.remove('on');
      clip.classList.add('off');           // unclip with a snap
      setTimeout(function () { clip.remove(); }, 450);
      window.removeEventListener('wheel', cancel);
      window.removeEventListener('touchstart', cancel);
    }
    function step(ts) {
      if (cancelled) { done(); return; }
      if (!t0) t0 = ts;
      var k = Math.min((ts - t0) / dur, 1);
      window.scrollTo(0, startY + (endY - startY) * ease(k));
      if (k < 1) requestAnimationFrame(step); else done();
    }
    requestAnimationFrame(step);
  }

  /* ── Mobile navigation: burger + full-screen overlay menu ──────────── */
  function initMobileNav() {
    var nav = document.getElementById('navbar');
    if (!nav) return;
    var links = nav.querySelectorAll('.nav-links a');
    if (!links.length) return;

    // burger
    var burger = document.createElement('button');
    burger.id = 'rad-burger';
    burger.setAttribute('aria-label', 'Open menu');
    burger.setAttribute('aria-expanded', 'false');
    burger.innerHTML = '<span></span><span></span><span></span>';
    nav.appendChild(burger);

    // overlay menu — mirror the nav links, then add the Enroll CTA
    var menu = document.createElement('div');
    menu.id = 'rad-mobile-menu';
    links.forEach(function (a) {
      // dropdown parents (carry a caret) are represented by their children,
      // which querySelectorAll already picked up in document order
      if (a.querySelector('.nav-caret')) return;
      var m = document.createElement('a');
      m.href = a.getAttribute('href');
      m.textContent = a.textContent;
      if (a.classList.contains('active')) m.classList.add('active');
      menu.appendChild(m);
    });
    var urls = window.RADIAN_URLS || {};
    if (urls.enrol) {
      var cta = document.createElement('a');
      cta.href = urls.enrol;
      cta.className = 'rad-mm-cta';
      cta.textContent = 'Enroll Now';
      menu.appendChild(cta);
    }
    document.body.appendChild(menu);

    function setOpen(open) {
      burger.classList.toggle('open', open);
      menu.classList.toggle('open', open);
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      document.documentElement.classList.toggle('rad-mm-lock', open);
    }
    burger.addEventListener('click', function () {
      setOpen(!menu.classList.contains('open'));
    });
    menu.addEventListener('click', function (e) {
      if (e.target.closest && e.target.closest('a')) setOpen(false);
    });
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  }

  /* ── Floating WhatsApp button ────────────────────────────────────── */
  function initWhatsApp() {
    var a = document.createElement('a');
    a.id = 'rad-wa';
    a.href = 'https://wa.me/18682804598?text=' +
      encodeURIComponent('Hi Radian, I would like information about your training courses.');
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
    a.setAttribute('aria-label', 'Chat with us on WhatsApp');
    a.innerHTML = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.1-1.3A10 10 0 1 0 12 2zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.6-6.1c-.3-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.3-.7.8-.8 1-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-2-1.2 7.5 7.5 0 0 1-1.4-1.7c-.1-.3 0-.4.1-.5l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.1s.9 2.4 1 2.6c.1.2 1.8 2.8 4.4 3.9.6.3 1.1.4 1.5.6.6.2 1.2.2 1.6.1.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3z"/></svg>';
    document.body.appendChild(a);
  }

  /* ── Boot ─────────────────────────────────────────────────────────── */
  function boot() {
    initScrollUI();
    initGrain();
    initMagnets();
    initSpotlight();
    initAnchorClips();
    initMobileNav();
    initWhatsApp();
    // Hero is rendered by React — retry briefly until it exists
    var tries = 0;
    (function waitHero() {
      if (document.querySelector('.hero .scaffold-bg')) { initHeroParallax(); return; }
      if (++tries < 40) setTimeout(waitHero, 250);
    })();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
