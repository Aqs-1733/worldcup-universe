/* Cross-page transition helper: the sweep is pure CSS (page-transition.css); this only names the image that should
   morph between a team/player card and that page's cover, and marks backward navigations so the sweep reverses.
   Loaded without defer so the pagereveal listener exists before the new page first renders. */
(() => {
  if (!('onpagereveal' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const NAME = 'pt-media', BACK = 'wcu-pt-back';
  const covers = { teams: '.team-squad-hero', players: '.athlete-portrait' };
  const lists = /^\/(teams|players)?\/?$|^\/teams\/[^/]+\/?$/;
  const path = url => new URL(url, location.href).pathname;
  const kind = url => path(url).match(/^\/(teams|players)\/[^/]+\/?$/)?.[1] || null;
  const visible = el => { const r = el?.getBoundingClientRect(); return r && r.width > 0 && r.bottom > 0 && r.top < innerHeight ? el : null; };
  // The on-screen card that links to url: its photo, or the media box when the photo failed to load.
  const cardFor = url => {
    const target = path(url);
    for (const link of document.querySelectorAll('a[href^="/teams/"], a[href^="/players/"]')) {
      if (link.pathname !== target) continue;
      const media = visible(link.querySelector('img') || link.querySelector('.p-media, .squad-cover'));
      if (media) return media;
    }
    return null;
  };
  const coverFor = url => { const k = kind(url); return k ? visible(document.querySelector(covers[k])) : null; };
  const name = el => { if (el) el.style.viewTransitionName = NAME; return el; };
  const clear = () => document.querySelectorAll('[style*="view-transition-name"]').forEach(el => { el.style.viewTransitionName = ''; });

  document.addEventListener('click', e => {
    if (e.target.closest?.('.back-link')) { try { sessionStorage.setItem(BACK, '1'); } catch (err) { /* private mode */ } }
  });

  // Old page: name the card being opened, or this page's cover when heading back to a page that lists it.
  addEventListener('pageswap', e => {
    if (!e.viewTransition || !e.activation?.entry?.url) return;
    const to = e.activation.entry.url;
    if (!(kind(to) && name(cardFor(to))) && kind(location.href) && (e.activation.navigationType === 'traverse' || lists.test(path(to)))) name(coverFor(location.href));
  });
  addEventListener('pageshow', e => { if (e.persisted) clear(); });

  // New page: name the matching cover or card, and reverse the sweep for back navigation.
  addEventListener('pagereveal', e => {
    let back = false; try { back = sessionStorage.getItem(BACK) === '1'; sessionStorage.removeItem(BACK); } catch (err) { /* private mode */ }
    if (!e.viewTransition) return;
    const activation = window.navigation?.activation, from = activation?.from?.url;
    if (activation?.navigationType === 'traverse' && activation.from && activation.entry.index < activation.from.index) back = true;
    if (back) e.viewTransition.types?.add('back');
    if (from) name(kind(location.href) ? coverFor(location.href) : kind(from) ? cardFor(from) : null);
    e.viewTransition.finished.finally(clear);
  });
})();
