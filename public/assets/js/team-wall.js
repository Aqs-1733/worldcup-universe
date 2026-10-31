/* Home team wall: pins the stage, untilts the wall and flies into the centre squad photo, then reveals the copy.
   Without GSAP or with reduced motion the wall stays a static banner (team-wall.css). */
(() => {
  const wall = document.querySelector('[data-team-wall]');
  const gsap = window.gsap, ScrollTrigger = window.ScrollTrigger, motion = window.WCUMotion || {};
  if (!wall || !gsap || !ScrollTrigger || motion.reduced) return;
  const stage = wall.querySelector('[data-wall-stage]'), grid = wall.querySelector('[data-wall-grid]'), hero = wall.querySelector('[data-wall-hero]');
  const tiles = [...grid.children].filter(tile => tile !== hero);
  const plate = wall.querySelector('[data-wall-plate]');
  hero.querySelector('img').loading = 'eager'; plate.loading = 'eager';
  wall.classList.add('is-live');

  // Scale at which the centre tile covers the whole stage, matching the plate's object-fit: cover.
  const cover = () => Math.max(stage.clientWidth / hero.offsetWidth, stage.clientHeight / hero.offsetHeight);
  // Columns drift against each other while tilted; the centre column stays put so the hero stays centred.
  const drift = (i, tile) => { const col = Number(tile.style.getPropertyValue('--col')); return col === 3 ? 0 : (col % 2 ? -1 : 1) * hero.offsetHeight * 0.45; };

  gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: { trigger: wall, start: 'top top', end: '+=190%', pin: stage, scrub: 0.9, invalidateOnRefresh: true } })
    .fromTo(grid, { rotateX: 22, rotateZ: -7, scale: 0.95 }, { rotateX: 0, rotateZ: 0, scale: 1.25, duration: 0.3, ease: 'power1.inOut' }, 0)
    .fromTo(tiles, { y: drift }, { y: 0, duration: 0.3, ease: 'power1.inOut' }, 0)
    .to(grid, { scale: cover, duration: 0.42, ease: 'power2.in' }, 0.3)
    .to(tiles, { autoAlpha: 0.3, duration: 0.3 }, 0.42)
    .to(plate, { opacity: 1, duration: 0.04 }, 0.68)
    .to('[data-wall-shade]', { opacity: 1, duration: 0.14 }, 0.7)
    .fromTo('[data-wall-copy]', { autoAlpha: 0, y: 50 }, { autoAlpha: 1, y: 0, duration: 0.14, ease: 'power3.out' }, 0.74)
    .to({}, { duration: 0.12 }, 0.88);
})();
