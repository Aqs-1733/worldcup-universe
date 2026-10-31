/* Home team wall: the column drift is pure CSS; this only pauses it while the wall is off screen. */
(() => {
  const wall = document.querySelector('[data-team-wall]');
  if (!wall || !('IntersectionObserver' in window)) return;
  new IntersectionObserver(([entry]) => wall.classList.toggle('is-offscreen', !entry.isIntersecting), { rootMargin: '120px 0px' }).observe(wall);
})();
