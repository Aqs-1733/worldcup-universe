/* Site-wide motion layer: smooth scroll, scroll reveals, magnetic buttons and tilt cards.
   Everything is progressive: without JS or with reduced motion the page is fully static and usable. */
(() => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const gsap = window.gsap, ScrollTrigger = window.ScrollTrigger;
  const motion = { reduced, fine, lenis: null };
  window.WCUMotion = motion;

  const header = document.getElementById('site-header');
  if (header) {
    const update = () => header.classList.toggle('is-scrolled', window.scrollY > 24);
    update(); window.addEventListener('scroll', update, { passive: true });
  }
  if (!gsap || !ScrollTrigger || reduced) { document.documentElement.classList.add('motion-off'); return; }
  gsap.registerPlugin(ScrollTrigger);
  document.documentElement.classList.add('motion-on');

  // Lenis gives the weighted, inertial scroll feel; containers that need the wheel opt out with data-lenis-prevent.
  if (window.Lenis && !document.querySelector('[data-no-smooth]')) {
    const lenis = new window.Lenis({ duration: 1.15, easing: t => Math.min(1, 1.001 - Math.pow(2, -10 * t)), smoothWheel: true });
    motion.lenis = lenis;
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(time => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);
    document.querySelectorAll('a[href^="#"]').forEach(link => link.addEventListener('click', event => {
      const id = link.getAttribute('href').slice(1); const target = id && document.getElementById(id);
      if (!target || event.ctrlKey || event.metaKey) return;
      event.preventDefault(); lenis.scrollTo(target, { offset: -(header?.offsetHeight || 0) });
      history.replaceState(null, '', '#' + id);
    }));
  }

  const once = { once: true };
  // Headings: masked line rise.
  document.querySelectorAll('[data-reveal-title]').forEach(title => {
    if (title.querySelector('.reveal-inner')) return;
    const inner = document.createElement('span'); inner.className = 'reveal-inner';
    while (title.firstChild) inner.appendChild(title.firstChild);
    const mask = document.createElement('span'); mask.className = 'reveal-mask'; mask.appendChild(inner); title.appendChild(mask);
    gsap.from(inner, { yPercent: 110, rotate: 2, duration: 1.1, ease: 'expo.out', scrollTrigger: { trigger: title, start: 'top 88%', ...once } });
  });
  // Single elements: fade up.
  document.querySelectorAll('[data-reveal]').forEach(el => {
    gsap.from(el, { y: 36, autoAlpha: 0, duration: 0.9, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 90%', ...once } });
  });
  // Grids: children cascade in.
  document.querySelectorAll('[data-reveal-children]').forEach(group => {
    const items = [...group.children];
    gsap.from(items, { y: 60, autoAlpha: 0, scale: 0.97, duration: 1, ease: 'power3.out', stagger: 0.08, scrollTrigger: { trigger: group, start: 'top 85%', ...once } });
  });
  // Background parallax for photo bands.
  document.querySelectorAll('[data-parallax-bg]').forEach(el => {
    gsap.fromTo(el, { backgroundPosition: '50% 0%' }, { backgroundPosition: '50% 100%', ease: 'none', scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true } });
  });

  if (fine) {
    // Magnetic buttons follow the pointer a little, then spring back.
    document.querySelectorAll('[data-magnetic]').forEach(el => {
      const x = gsap.quickTo(el, 'x', { duration: 0.5, ease: 'power3' }), y = gsap.quickTo(el, 'y', { duration: 0.5, ease: 'power3' });
      el.addEventListener('pointermove', e => { const r = el.getBoundingClientRect(); x((e.clientX - r.left - r.width / 2) * 0.28); y((e.clientY - r.top - r.height / 2) * 0.35); });
      el.addEventListener('pointerleave', () => { x(0); y(0); });
    });
    // 3D tilt with a moving highlight; works on any card with data-tilt.
    motion.bindTilt = root => root.querySelectorAll('[data-tilt]').forEach(card => {
      if (card.dataset.tiltBound) return; card.dataset.tiltBound = '1';
      const rx = gsap.quickTo(card, 'rotationX', { duration: 0.6, ease: 'power3' }), ry = gsap.quickTo(card, 'rotationY', { duration: 0.6, ease: 'power3' });
      gsap.set(card, { transformPerspective: 900 });
      card.addEventListener('pointermove', e => {
        const r = card.getBoundingClientRect(), px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
        ry((px - 0.5) * 14); rx((0.5 - py) * 12);
        card.style.setProperty('--glare-x', `${px * 100}%`); card.style.setProperty('--glare-y', `${py * 100}%`);
      });
      card.addEventListener('pointerleave', () => { rx(0); ry(0); });
    });
    motion.bindTilt(document);
  }
  window.addEventListener('load', () => ScrollTrigger.refresh());
})();
