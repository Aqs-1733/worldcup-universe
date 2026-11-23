/*
 * 文件作用：新闻页定时查询同步状态，有新新闻时提示刷新。
 * 作者：梁靖泽_2412852
 */
(() => {
  const bar = document.querySelector('[data-news-live]');
  if (!bar) return;

  const notice = bar.querySelector('[data-news-new]');
  const timestamp = bar.querySelector('[data-news-updated]');
  let revision = null;

  async function check() {
    if (document.hidden) return;
    try {
      const response = await fetch('/news/status', {credentials: 'same-origin', cache: 'no-store'});
      if (!response.ok) return;
      const data = await response.json();
      const nextRevision = data.revision || `${data.updated || ''}:${data.latest || 0}`;
      if (revision !== null && nextRevision !== revision) notice.hidden = false;
      revision = nextRevision;
      timestamp.textContent = data.updated || '—';
    } catch (_) {
      // The list remains usable while a status request is temporarily unavailable.
    }
  }

  check();
  setInterval(check, 60000);
})();
