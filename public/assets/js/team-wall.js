/*
 * 文件作用：首页球队墙：离开屏幕时暂停动画。
 * 作者：崔迪_2412439
 */
(() => {
  const wall = document.querySelector('[data-team-wall]');
  if (!wall || !('IntersectionObserver' in window)) return;
  new IntersectionObserver(([entry]) => wall.classList.toggle('is-offscreen', !entry.isIntersecting), { rootMargin: '120px 0px' }).observe(wall);
})();
