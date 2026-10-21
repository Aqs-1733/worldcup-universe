/* Progressive enhancement only: core navigation, filtering and forms work without JS. */
(() => {
  const toggle = document.querySelector('[data-menu-toggle]');
  toggle?.addEventListener('click', () => {
    const menu = document.getElementById(toggle.getAttribute('aria-controls'));
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    menu?.classList.toggle('is-open', open);
  });
  document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));
  document.querySelectorAll('[data-quote]').forEach(link => link.addEventListener('click', () => {
    const input = document.querySelector('[data-parent-input]');
    if (input) input.value = link.dataset.quote;
  }));
  document.querySelectorAll('img').forEach(img => img.addEventListener('error', () => {
    img.hidden = true;
    img.closest('[data-media]')?.classList.add('media-fallback');
  }, {once: true}));
})();
