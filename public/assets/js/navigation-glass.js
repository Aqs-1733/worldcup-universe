/* Pointer light stays within the glass surface and respects motion preferences. */
(() => {
  const surface = document.querySelector('#site-header .nav-inner');
  const opening = document.querySelector('[data-cine-hero]');
  if (surface && opening) {
    const header = surface.closest('#site-header');
    const clearance = header.offsetHeight;
    new IntersectionObserver(([entry]) => {
      header.classList.toggle('is-past-opening', !entry.isIntersecting && entry.boundingClientRect.bottom <= clearance);
    }, { rootMargin: '-' + clearance + 'px 0px 0px 0px' }).observe(opening);
  }
  if (!surface || matchMedia('(prefers-reduced-motion: reduce)').matches ||
      !matchMedia('(hover: hover) and (pointer: fine)').matches) return;

  let frame = 0, pointerX = 0, pointerY = 0;
  surface.addEventListener('pointermove', event => {
    pointerX = event.clientX;
    pointerY = event.clientY;
    if (frame) return;
    frame = requestAnimationFrame(() => {
      frame = 0;
      const box = surface.getBoundingClientRect();
      const x = Math.max(0, Math.min(100, (pointerX - box.left) / box.width * 100));
      const y = Math.max(0, Math.min(100, (pointerY - box.top) / box.height * 100));
      surface.style.setProperty('--glass-x', x + '%');
      surface.style.setProperty('--glass-y', y + '%');
    });
  }, { passive: true });

  surface.addEventListener('pointerleave', () => {
    cancelAnimationFrame(frame);
    frame = 0;
    surface.style.removeProperty('--glass-x');
    surface.style.removeProperty('--glass-y');
  });
})();
