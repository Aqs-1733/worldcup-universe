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
  document.querySelectorAll('[data-quote]').forEach(link => link.addEventListener('click', event => {
    const input = document.querySelector('[data-parent-input]');
    if (!input) return;
    event.preventDefault();
    input.value = link.dataset.quote;
    const chip = document.querySelector('[data-quote-chip]');
    const caption = document.querySelector('[data-quote-caption]');
    if (chip) chip.hidden = false;
    if (caption) caption.textContent = `回复 ${link.dataset.quoteName || '球迷'} · #${link.dataset.quote}`;
    document.getElementById('compose')?.scrollIntoView({block: 'start'});
    document.getElementById('topic-reply-body')?.focus({preventScroll: true});
  }));
  document.querySelector('[data-quote-cancel]')?.addEventListener('click', () => {
    const input = document.querySelector('[data-parent-input]');
    if (input) input.value = '';
    const chip = document.querySelector('[data-quote-chip]');
    if (chip) chip.hidden = true;
    document.getElementById('topic-reply-body')?.focus({preventScroll: true});
  });
  document.querySelectorAll('img').forEach(img => img.addEventListener('error', () => {
    img.hidden = true;
    img.closest('[data-media]')?.classList.add('media-fallback');
  }, {once: true}));
})();
