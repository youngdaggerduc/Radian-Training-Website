/* ============================================================================
   motion.js — Radian H.A. Limited
   GSAP + ScrollTrigger choreography helpers, shared across pages.

   Call RadianMotion.init() once the page DOM (incl. React render) is ready.
   Returns true if GSAP took over reveals, false if the caller should fall
   back to its own IntersectionObserver.

   Data-attribute API (add to markup, then call init):
     [data-parallax="0.2"]            scrub-parallax on Y (fraction of travel)
     [data-count] (text = number)     count-up when scrolled into view
     [data-hsection] > [data-htrack]  pin section + scroll track horizontally
     .reveal / .reveal-left / .reveal-right   staggered entrance (batched)

   Automatic choreography (no markup needed):
     .section-title / .cta-title      split into masked words, rise+unrotate
     [class*="marquee-band"]          skews with scroll velocity
============================================================================ */
(function () {
  'use strict';
  const RM = {};
  let started = false;

  RM.ready = function () {
    return typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined';
  };

  RM.init = function () {
    if (started) return true;
    if (!RM.ready()) return false;
    started = true;

    gsap.registerPlugin(ScrollTrigger);
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.documentElement.classList.add('gsap-on');

    // kill the CSS transitions on reveal elements so GSAP owns them cleanly
    const s = document.createElement('style');
    s.textContent = 'html.gsap-on .reveal,html.gsap-on .reveal-left,html.gsap-on .reveal-right{transition:none!important;}';
    document.head.appendChild(s);

    if (reduced) {
      document.querySelectorAll('.reveal,.reveal-left,.reveal-right').forEach(el => {
        gsap.set(el, { opacity: 1, x: 0, y: 0 });
      });
      revealCounters(true);
      return true;
    }

    // ---- crane-drop headings ([data-crane]) — must run before reveals ----
    setupCraneTitles('[data-crane]');

    // ---- staggered reveals (batched so siblings enter together) ----------
    setupReveals('.reveal', { y: 44, x: 0 });
    setupReveals('.reveal-left', { y: 0, x: -56 });
    setupReveals('.reveal-right', { y: 0, x: 56 });

    // ---- cinematic headline reveals (masked words rise into place) -------
    splitTitles('.section-title, .cta-title');

    // ---- marquee bands lean with scroll velocity --------------------------
    setupVelocitySkew('[class*="marquee-band"]');

    // ---- parallax --------------------------------------------------------
    document.querySelectorAll('[data-parallax]').forEach(el => {
      const amt = parseFloat(el.getAttribute('data-parallax')) || 0.2;
      gsap.fromTo(el, { yPercent: -amt * 50 }, {
        yPercent: amt * 50, ease: 'none',
        scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true },
      });
    });

    // ---- count-ups -------------------------------------------------------
    revealCounters(false);

    // ---- horizontal pinned strips ---------------------------------------
    document.querySelectorAll('[data-hsection]').forEach(setupHScroll);

    // refresh after late assets (fonts / images / 3D canvas) settle
    setTimeout(() => ScrollTrigger.refresh(), 400);
    window.addEventListener('load', () => ScrollTrigger.refresh());
    return true;
  };

  function setupReveals(sel, from) {
    const els = gsap.utils.toArray(sel);
    if (!els.length) return;
    gsap.set(els, { opacity: 0, x: from.x, y: from.y, filter: 'blur(8px)' });
    ScrollTrigger.batch(els, {
      start: 'top 86%',
      onEnter: (batch) => gsap.to(batch, {
        opacity: 1, x: 0, y: 0, filter: 'blur(0px)', duration: 0.85, ease: 'power3.out',
        stagger: 0.09, overwrite: true,
        onComplete: () => gsap.set(batch, { clearProps: 'filter' }),
      }),
    });
  }

  /* Split headline text nodes into masked words (.rm-m > .rm-w) and play a
     rise + unrotate stagger when the title scrolls into view. Nested spans
     (.accent / .dim / .outline) keep their styling — only raw text nodes
     are wrapped, in place. */
  function splitTitles(sel) {
    document.querySelectorAll(sel).forEach(title => {
      if (title.dataset.rmSplit) return;
      title.dataset.rmSplit = '1';

      const walker = document.createTreeWalker(title, NodeFilter.SHOW_TEXT);
      const textNodes = [];
      while (walker.nextNode()) {
        if (walker.currentNode.nodeValue.trim()) textNodes.push(walker.currentNode);
      }
      textNodes.forEach(node => {
        const frag = document.createDocumentFragment();
        node.nodeValue.split(/(\s+)/).forEach(part => {
          if (!part) return;
          if (!part.trim()) { frag.appendChild(document.createTextNode(part)); return; }
          const mask = document.createElement('span');
          mask.className = 'rm-m';
          const word = document.createElement('span');
          word.className = 'rm-w';
          word.textContent = part;
          mask.appendChild(word);
          frag.appendChild(mask);
        });
        node.parentNode.replaceChild(frag, node);
      });

      const words = title.querySelectorAll('.rm-w');
      if (!words.length) return;
      gsap.set(words, { yPercent: 115, rotate: 4, transformOrigin: '0% 100%' });
      ScrollTrigger.create({
        trigger: title, start: 'top 88%', once: true,
        onEnter: () => gsap.to(words, {
          yPercent: 0, rotate: 0, duration: 0.9, ease: 'power4.out', stagger: 0.05,
        }),
      });
    });
  }

  /* Crane-drop: a steel cable lowers the heading into place, sways as it
     settles, then the cable retracts upward and disappears. Headings opt in
     with data-crane="1". Claims the title before reveal/splitTitles run. */
  function setupCraneTitles(sel) {
    gsap.utils.toArray(sel).forEach(title => {
      if (title.dataset.rmSplit) return;
      title.dataset.rmSplit = '1';                       // keep splitTitles off it
      title.classList.remove('reveal', 'reveal-left', 'reveal-right');
      if (getComputedStyle(title).position === 'static') title.style.position = 'relative';

      const cable = document.createElement('span');
      cable.className = 'rm-cable';
      cable.innerHTML = '<span class="rm-hook"></span>';
      title.appendChild(cable);

      gsap.set(title, { y: -170, opacity: 0, transformOrigin: '50% 0%' });
      ScrollTrigger.create({
        trigger: title, start: 'top 80%', once: true,
        onEnter: () => {
          const tl = gsap.timeline();
          tl.to(title, { opacity: 1, duration: 0.25, ease: 'power1.out' }, 0);
          tl.to(title, { y: 0, duration: 1.1, ease: 'power2.inOut' }, 0);
          // pendulum settle while the load comes down
          tl.fromTo(title, { rotation: 1.1 }, { rotation: 0, duration: 1.5, ease: 'elastic.out(1, 0.3)' }, 0.45);
          // unhook: cable retracts up and away
          tl.to(cable, {
            scaleY: 0, opacity: 0, transformOrigin: '50% 0%',
            duration: 0.55, ease: 'power2.in',
            onComplete: () => cable.remove(),
          }, '+=0.1');
        },
      });
    });
  }

  /* Bands lean into the scroll — skew tracks velocity, settles back to 0. */
  function setupVelocitySkew(sel) {
    const els = gsap.utils.toArray(sel);
    if (!els.length) return;
    els.forEach(el => {
      const setSkew = gsap.quickTo(el, 'skewY', { duration: 0.4, ease: 'power2.out' });
      ScrollTrigger.create({
        trigger: el, start: 'top bottom', end: 'bottom top',
        onUpdate: (self) => {
          const v = gsap.utils.clamp(-3, 3, self.getVelocity() / 350);
          setSkew(v);
        },
        onLeave: () => setSkew(0),
        onLeaveBack: () => setSkew(0),
      });
      // ease back to level whenever scrolling pauses
      ScrollTrigger.addEventListener('scrollEnd', () => setSkew(0));
    });
  }

  function revealCounters(immediate) {
    document.querySelectorAll('[data-count]').forEach(el => {
      const target = parseFloat(el.getAttribute('data-count'));
      const suffix = el.getAttribute('data-suffix') || '';
      const sep = el.hasAttribute('data-sep');
      const fmt = (v) => {
        let n = Math.round(v);
        let str = sep ? n.toLocaleString('en-GB') : String(n);
        return str + suffix;
      };
      if (immediate) { el.textContent = fmt(target); return; }
      const obj = { v: 0 };
      el.textContent = fmt(0);
      ScrollTrigger.create({
        trigger: el, start: 'top 90%', once: true,
        onEnter: () => gsap.to(obj, { v: target, duration: 1.7, ease: 'power2.out', onUpdate: () => { el.textContent = fmt(obj.v); } }),
      });
    });
  }

  function setupHScroll(section) {
    const track = section.querySelector('[data-htrack]');
    if (!track) return;
    if (window.innerWidth < 760) return;   // stack normally on small screens
    const getScroll = () => track.scrollWidth - section.clientWidth;
    gsap.to(track, {
      x: () => -getScroll(),
      ease: 'none',
      scrollTrigger: {
        trigger: section,
        start: 'top top',
        end: () => '+=' + (getScroll() + section.clientHeight * 0.5),
        pin: true,
        scrub: 1,
        anticipatePin: 1,
        invalidateOnRefresh: true,
      },
    });
  }

  window.RadianMotion = RM;
})();
