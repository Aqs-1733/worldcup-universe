/* Accessible manual player chapters and source-derived SVG bracket routes. */
(() => {
  const tabs = [...document.querySelectorAll('[data-spotlight-tab]')];
  const activate = index => {
    tabs.forEach((tab, i) => { tab.setAttribute('aria-selected', String(i === index)); tab.tabIndex = i === index ? 0 : -1; });
    document.querySelectorAll('[data-spotlight-slide]').forEach((slide, i) => { slide.classList.toggle('is-active', i === index); slide.hidden = i !== index; });
  };
  tabs.forEach((tab, i) => {
    tab.addEventListener('click', () => activate(i));
    tab.addEventListener('keydown', event => {
      const keys = ['ArrowRight','ArrowLeft','Home','End']; if (!keys.includes(event.key)) return;
      event.preventDefault(); const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (i + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
      activate(next); tabs[next].focus();
    });
  });
})();
