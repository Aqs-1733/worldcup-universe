/* Home only: preloader, pinned three-scene hero, score ticker and horizontal player rail.
   Without GSAP or with reduced motion the scenes render stacked and every link still works. */
(() => {
  const hero = document.querySelector('[data-cine-hero]');
  const preloader = document.querySelector('[data-cine-preloader]');
  const motion = window.WCUMotion || {};
  const gsap = window.gsap, ScrollTrigger = window.ScrollTrigger;
  const enabled = Boolean(hero && gsap && ScrollTrigger && !motion.reduced);
  const finishPreloader = () => preloader?.remove();
  if (!enabled) { finishPreloader(); hero?.classList.add('is-static'); return; }

  hero.classList.add('is-cine');
  const $ = s => hero.querySelector(s), $$ = s => [...hero.querySelectorAll(s)];

  // Split the display title into masked characters.
  $$('.cine-line').forEach(line => {
    const text = line.textContent; line.textContent = ''; line.setAttribute('aria-hidden', 'true');
    [...text].forEach(ch => { const s = document.createElement('span'); s.className = 'cine-char'; s.textContent = ch === ' ' ? ' ' : ch; line.appendChild(s); });
  });
  $('.cine-title')?.setAttribute('aria-label', $$('.cine-line').map(l => l.textContent.replace(/ /g, ' ')).join(' '));
  const chars = $$('.cine-char');

  // ---- Intro: runs once the preloader leaves (or straight away on repeat visits).
  const intro = () => {
    const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
    tl.fromTo('[data-cine-tunnel] img', { scale: 1.18, filter: 'brightness(0.35)' }, { scale: 1, filter: 'brightness(1)', duration: 2.6, ease: 'power2.out' }, 0)
      .from('[data-cine-eyebrow] span', { scaleX: 0, transformOrigin: 'left', duration: 1 }, 0.3)
      .from('[data-cine-eyebrow]', { autoAlpha: 0, x: -20, duration: 1 }, 0.35)
      .from(chars, { yPercent: 115, rotate: 8, duration: 1.3, stagger: 0.035 }, 0.45)
      .from('[data-cine-scene="1"] [data-cine-fade]', { autoAlpha: 0, y: 30, duration: 1.1, stagger: 0.12 }, 0.9)
      .from('.cine-chapters, .cine-scroll-cue, .cine-credit', { autoAlpha: 0, y: 20, duration: 1, stagger: 0.08 }, 1.1);
    return tl;
  };
  const tunnelImage = $('[data-cine-tunnel] img');
  let seen = false; try { seen = sessionStorage.getItem('wcu-intro') === '1'; sessionStorage.setItem('wcu-intro', '1'); } catch (e) { /* private mode */ }
  if (!preloader || seen) { finishPreloader(); intro(); }
  else {
    document.documentElement.classList.add('is-loading');
    const count = preloader.querySelector('[data-cine-count]'), bar = preloader.querySelector('[data-cine-bar]');
    const state = { v: 0 };
    const ready = new Promise(resolve => { if (tunnelImage.complete) resolve(); else { tunnelImage.addEventListener('load', resolve, { once: true }); tunnelImage.addEventListener('error', resolve, { once: true }); } setTimeout(resolve, 2400); });
    const counting = gsap.to(state, { v: 86, duration: 1.1, ease: 'power2.out', onUpdate: () => { count.textContent = String(Math.round(state.v)).padStart(2, '0'); bar.style.transform = `scaleX(${state.v / 100})`; } });
    ready.then(() => counting.then ? counting.then() : null).then(() => {
      gsap.timeline({ onComplete: () => { document.documentElement.classList.remove('is-loading'); finishPreloader(); } })
        .to(state, { v: 100, duration: 0.35, ease: 'power1.in', onUpdate: () => { count.textContent = String(Math.round(state.v)).padStart(2, '0'); bar.style.transform = `scaleX(${state.v / 100})`; } })
        .to(preloader.querySelector('.cine-preloader-inner'), { yPercent: -60, autoAlpha: 0, duration: 0.6, ease: 'power3.in' })
        .to(preloader, { clipPath: 'inset(0 0 100% 0)', duration: 0.9, ease: 'expo.inOut' }, '-=0.2')
        .add(intro(), '-=0.55');
    });
  }

  // ---- Scroll story: tunnel → light → stadium + numbers → champion.
  const counters = $$('[data-count-to]').map(el => ({ el, to: Number(el.dataset.countTo), v: { n: 0 } }));
  counters.forEach(c => { c.el.textContent = '0'; });
  const chapters = $$('[data-cine-chapter]'), progressBar = $('[data-cine-progress]');
  const setChapter = p => { const index = p < 0.36 ? 0 : p < 0.66 ? 1 : 2; chapters.forEach((c, i) => c.classList.toggle('is-active', i === index)); if (progressBar) progressBar.style.transform = `scaleX(${p})`; };
  setChapter(0);
  const story = gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom bottom', scrub: 0.8, onUpdate: self => setChapter(self.progress) } });
  story
    // Walk out of the tunnel: push into the light.
    .to('[data-cine-tunnel] img', { scale: 2.6, filter: 'brightness(1.9) saturate(1.2)', duration: 0.34, ease: 'power2.in' }, 0)
    .to(chars, { yPercent: -120, autoAlpha: 0, stagger: { each: 0.004, from: 'center' }, duration: 0.12, ease: 'power2.in' }, 0.02)
    .to('[data-cine-scene="1"] [data-cine-fade], [data-cine-eyebrow]', { y: -50, autoAlpha: 0, duration: 0.1, stagger: 0.02 }, 0.03)
    .to('.cine-scroll-cue, .cine-credit', { autoAlpha: 0, duration: 0.05 }, 0.02)
    .to('[data-cine-flash]', { autoAlpha: 1, duration: 0.07, ease: 'power2.in' }, 0.27)
    .set('[data-cine-tunnel]', { autoAlpha: 0 }, 0.345)
    .set('[data-cine-stadium]', { autoAlpha: 1 }, 0.345)
    .fromTo('[data-cine-stadium] img, [data-cine-stadium] video', { scale: 1.35 }, { scale: 1.04, duration: 0.3, ease: 'power2.out' }, 0.345)
    .to('[data-cine-flash]', { autoAlpha: 0, duration: 0.1, ease: 'power2.out' }, 0.35)
    // Stadium chapter.
    .fromTo('[data-cine-scene="2"]', { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.02 }, 0.37)
    .fromTo('.cine-year', { scale: 0.55, autoAlpha: 0, letterSpacing: '0.4em' }, { scale: 1, autoAlpha: 1, letterSpacing: '-0.02em', duration: 0.14, ease: 'power3.out' }, 0.38)
    .from('[data-cine-scene="2"] .cine-kicker, .cine-stadium-title', { y: 40, autoAlpha: 0, duration: 0.08, stagger: 0.03, ease: 'power3.out' }, 0.42)
    .from('.cine-stat', { y: 60, autoAlpha: 0, duration: 0.08, stagger: 0.025, ease: 'power3.out' }, 0.45);
  counters.forEach((c, i) => story.to(c.v, { n: c.to, duration: 0.1, ease: 'power2.out', onUpdate: () => { c.el.textContent = Math.round(c.v.n).toLocaleString('en-US'); } }, 0.46 + i * 0.025));
  if ($('[data-cine-scene="3"]')) {
    story
      .to('[data-cine-scene="2"]', { y: -80, autoAlpha: 0, duration: 0.08, ease: 'power2.in' }, 0.62)
      .to('[data-cine-stadium]', { autoAlpha: 0, duration: 0.08 }, 0.64)
      .fromTo('[data-cine-final-bg]', { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.08 }, 0.64)
      .fromTo('[data-cine-final-bg] img', { scale: 1.3 }, { scale: 1.05, duration: 0.3, ease: 'power2.out' }, 0.64)
      .fromTo('[data-cine-scene="3"]', { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.02 }, 0.68)
      .from('[data-cine-scene="3"] .cine-kicker', { y: 30, autoAlpha: 0, duration: 0.06, ease: 'power3.out' }, 0.69)
      .from('.cine-champion-flag', { scale: 0.3, rotate: -12, autoAlpha: 0, duration: 0.1, ease: 'back.out(1.6)' }, 0.71)
      .from('.cine-champion h2', { yPercent: 60, autoAlpha: 0, duration: 0.1, ease: 'power3.out' }, 0.73)
      .from('.cine-final-score, .cine-final-meta, [data-cine-scene="3"] .cine-actions', { y: 40, autoAlpha: 0, duration: 0.08, stagger: 0.025, ease: 'power3.out' }, 0.77)
      .to({}, { duration: 0.12 }, 0.88);
  }

  // Gold confetti while the champion chapter is on screen.
  const finalScene = $('[data-cine-scene="3"]');
  if (finalScene) {
    const canvas = document.createElement('canvas'); canvas.className = 'cine-confetti'; canvas.setAttribute('aria-hidden', 'true');
    finalScene.before(canvas);
    const ctx = canvas.getContext('2d'); let pieces = [], running = false, raf = 0;
    const size = () => { const r = devicePixelRatio > 1 ? 1.5 : 1; canvas.width = innerWidth * r; canvas.height = innerHeight * r; ctx.setTransform(r, 0, 0, r, 0, 0); };
    const spawn = () => ({ x: Math.random() * innerWidth, y: -20 - Math.random() * innerHeight, w: 4 + Math.random() * 6, h: 8 + Math.random() * 10, vy: 0.6 + Math.random() * 1.6, vx: -0.4 + Math.random() * 0.8, r: Math.random() * 6.28, vr: -0.06 + Math.random() * 0.12, c: ['#f2c75c', '#ffe7a3', '#c9962f', '#ffffff', '#e51b2b'][Math.floor(Math.random() * 5)] });
    const frame = () => {
      ctx.clearRect(0, 0, innerWidth, innerHeight);
      pieces.forEach(p => { p.y += p.vy; p.x += p.vx + Math.sin(p.y / 40) * 0.3; p.r += p.vr; if (p.y > innerHeight + 20) Object.assign(p, spawn(), { y: -20 }); ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.r); ctx.fillStyle = p.c; ctx.globalAlpha = 0.85; ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h * Math.abs(Math.cos(p.r))); ctx.restore(); });
      raf = requestAnimationFrame(frame);
    };
    const start = () => { if (running) return; running = true; size(); pieces = Array.from({ length: innerWidth < 720 ? 50 : 110 }, spawn); gsap.to(canvas, { autoAlpha: 1, duration: 0.6 }); frame(); };
    const stop = () => { if (!running) return; running = false; gsap.to(canvas, { autoAlpha: 0, duration: 0.4, onComplete: () => cancelAnimationFrame(raf) }); };
    gsap.set(canvas, { autoAlpha: 0 });
    ScrollTrigger.create({ trigger: hero, start: () => `top+=${(hero.offsetHeight - innerHeight) * 0.7} top`, end: 'bottom top', onToggle: self => self.isActive ? start() : stop(), invalidateOnRefresh: true });
    window.addEventListener('resize', () => running && size());
  }

  // Pointer parallax on the image layers (desktop only).
  if (motion.fine) {
    const layers = $$('.cine-layer').map(el => ({ x: gsap.quickTo(el, 'x', { duration: 1.2, ease: 'power3' }), y: gsap.quickTo(el, 'y', { duration: 1.2, ease: 'power3' }) }));
    hero.addEventListener('pointermove', e => { const nx = e.clientX / innerWidth - 0.5, ny = e.clientY / innerHeight - 0.5; layers.forEach(l => { l.x(nx * -26); l.y(ny * -18); }); });
  }
  const video = $('[data-cine-video]');
  if (video) ScrollTrigger.create({ trigger: hero, start: '30% top', end: 'bottom top', onToggle: self => self.isActive ? video.play().catch(() => {}) : video.pause() });
  $('[data-hero-explore]')?.addEventListener('click', e => { e.preventDefault(); const t = document.getElementById('matchday'); motion.lenis ? motion.lenis.scrollTo(t, { offset: -70 }) : t.scrollIntoView({ behavior: 'smooth' }); });

  // ---- Ticker: endless marquee that speeds up and leans with scroll velocity.
  const track = document.querySelector('[data-ticker-track]');
  if (track) {
    const loop = gsap.to(track, { xPercent: -50, ease: 'none', duration: 60, repeat: -1 });
    const skew = gsap.quickTo(track, 'skewX', { duration: 0.4, ease: 'power3' });
    ScrollTrigger.create({ trigger: track, start: 'top bottom', end: 'bottom top', onUpdate: self => {
      const v = self.getVelocity(); loop.timeScale(gsap.utils.clamp(-6, 6, 1 + v / 300)); skew(gsap.utils.clamp(-8, 8, v / -250));
      gsap.to(loop, { timeScale: self.direction < 0 ? -1 : 1, duration: 1.2, delay: 0.1, overwrite: 'auto' });
    } });
    track.addEventListener('pointerenter', () => gsap.to(loop, { timeScale: 0.15, duration: 0.6 }));
    track.addEventListener('pointerleave', () => gsap.to(loop, { timeScale: 1, duration: 0.6 }));
  }

  // ---- Player rail: a native horizontal scroller (swipe, drag, arrows); vertical scrolling stays with the page.
  const rail = document.querySelector('[data-player-rail]');
  if (rail) {
    const viewport = rail.querySelector('[data-rail-viewport]'), bar = rail.querySelector('[data-rail-progress]');
    const cards = [...rail.querySelectorAll('.rail-card')];
    const prev = rail.querySelector('[data-rail-prev]'), next = rail.querySelector('[data-rail-next]');
    const update = () => {
      const max = viewport.scrollWidth - viewport.clientWidth, p = max > 0 ? viewport.scrollLeft / max : 0;
      if (bar) bar.style.transform = `scaleX(${Math.max(0.08, p)})`;
      if (prev) prev.disabled = viewport.scrollLeft < 4;
      if (next) next.disabled = viewport.scrollLeft > max - 4;
      rail.classList.toggle('at-start', viewport.scrollLeft < 4);
      rail.classList.toggle('at-end', viewport.scrollLeft > max - 4);
    };
    const step = dir => viewport.scrollBy({ left: dir * Math.max(cards[0]?.offsetWidth || 300, viewport.clientWidth * 0.75), behavior: motion.reduced ? 'auto' : 'smooth' });
    prev?.addEventListener('click', () => step(-1));
    next?.addEventListener('click', () => step(1));
    viewport.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
    // Click-and-drag with a mouse; touch and trackpads already scroll natively.
    let drag = null;
    viewport.addEventListener('pointerdown', e => { if (e.pointerType !== 'mouse' || e.button !== 0) return; drag = { x: e.clientX, left: viewport.scrollLeft, moved: false }; });
    window.addEventListener('pointermove', e => {
      if (!drag) return; const dx = e.clientX - drag.x;
      if (!drag.moved && Math.abs(dx) > 5) { drag.moved = true; rail.classList.add('is-dragging'); }
      if (drag.moved) viewport.scrollLeft = drag.left - dx;
    });
    window.addEventListener('pointerup', () => { if (!drag) return; const moved = drag.moved; rail.classList.remove('is-dragging'); setTimeout(() => { drag = null; }, 0); if (moved) viewport.dispatchEvent(new Event('scroll')); });
    viewport.addEventListener('click', e => { if (drag?.moved) { e.preventDefault(); e.stopPropagation(); } }, true);
    viewport.addEventListener('dragstart', e => e.preventDefault());
    gsap.from(cards, { y: 60, autoAlpha: 0, duration: 0.9, stagger: 0.06, ease: 'power3.out', scrollTrigger: { trigger: viewport, start: 'top 85%', once: true } });
  }
  document.querySelectorAll('[data-hover-play]').forEach(v => { const card = v.closest('a'); card.addEventListener('pointerenter', () => v.play().catch(() => {})); card.addEventListener('pointerleave', () => v.pause()); });
  window.addEventListener('load', () => ScrollTrigger.refresh());
})();
