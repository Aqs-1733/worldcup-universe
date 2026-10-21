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
  const bracket = document.querySelector('[data-bracket]');
  if (!bracket) return;
  const svg = bracket.querySelector('svg'), picker = document.querySelector('[data-bracket-team]');
  const nodes = [...bracket.querySelectorAll('[data-match]')], byId = new Map(nodes.map(n => [Number(n.dataset.match), n]));
  const edges = JSON.parse(document.getElementById('bracket-edges')?.textContent || '[]');
  const draw = () => {
    svg.replaceChildren(); const box = bracket.getBoundingClientRect();
    svg.setAttribute('viewBox', `0 0 ${bracket.scrollWidth} ${bracket.scrollHeight}`);
    for (const edge of edges) {
      const source = byId.get(Number(edge.from_match_id)), target = byId.get(Number(edge.to_match_id)); if (!source || !target) continue;
      const a = source.getBoundingClientRect(), b = target.getBoundingClientRect();
      const x1 = a.right - box.left, y1 = a.top + a.height / 2 - box.top, x2 = b.left - box.left, y2 = b.top + b.height / 2 - box.top;
      const path = document.createElementNS('http://www.w3.org/2000/svg','path');
      path.setAttribute('d', `M ${x1} ${y1} H ${(x1+x2)/2} V ${y2} H ${x2}`);
      path.dataset.from = String(edge.from_match_id); path.dataset.to = String(edge.to_match_id); svg.append(path);
    }
    highlight();
  };
  const highlight = () => {
    const team = picker?.value || '', active = new Set();
    nodes.forEach(node => { const selected = team && [node.dataset.home,node.dataset.away].includes(team); if (selected) active.add(node.dataset.match); node.classList.toggle('is-path', Boolean(selected)); node.classList.toggle('is-muted', Boolean(team && !selected)); });
    svg.querySelectorAll('path').forEach(path => { const selected = active.has(path.dataset.from) && active.has(path.dataset.to); path.classList.toggle('is-path', Boolean(selected)); path.classList.toggle('is-muted', Boolean(team && !selected)); });
    const summary = document.querySelector('[data-bracket-summary]');
    if (summary) summary.textContent = team ? (active.size ? `${picker.selectedOptions[0].textContent} · ${active.size} 场淘汰赛已高亮。点击比赛卡片查看详情。` : `${picker.selectedOptions[0].textContent} 未进入淘汰赛。可在下方赛程按球队查看小组赛。`) : '选择球队，高亮它走过的淘汰赛路线。横向滑动查看完整对阵。';
  };
  picker?.addEventListener('change', highlight);
  if ('ResizeObserver' in window) new ResizeObserver(draw).observe(bracket);
  draw();
})();
