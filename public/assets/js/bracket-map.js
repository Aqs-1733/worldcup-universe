/* Symmetric bracket: draws connectors from the verified edges, animates them in, and traces a team's path on hover/click.
   After the entrance a comet runs the champion's route to the final (replayable from the 冠军之路 chip). */
(() => {
  const root = document.querySelector('[data-bracket-map]');
  if (!root) return;
  const board = root.querySelector('[data-bm-board]'), svg = root.querySelector('[data-bm-lines]'), scroller = root.querySelector('[data-bm-scroll]');
  const summary = root.querySelector('[data-bm-summary]'), chips = [...root.querySelectorAll('[data-bm-team]')];
  const data = JSON.parse(root.querySelector('[data-bm-data]').textContent);
  const cards = new Map([...root.querySelectorAll('[data-bm-match]')].map(el => [Number(el.dataset.bmMatch), el]));
  const gsap = window.gsap, reduced = window.WCUMotion?.reduced ?? true;
  const NS = 'http://www.w3.org/2000/svg';
  const reachLabel = ['', '止步 32 强', '止步 16 强', '止步 8 强', '止步 4 强', '亚军'];
  let paths = [], locked = null;

  // Layout box relative to the board; offsets ignore transforms, so lines stay put while columns animate in.
  const layout = el => {
    let left = 0, top = 0;
    for (let n = el; n && n !== board; n = n.offsetParent) { left += n.offsetLeft; top += n.offsetTop; }
    return { left, top, right: left + el.offsetWidth, height: el.offsetHeight };
  };
  const draw = () => {
    const w = board.offsetWidth, h = board.offsetHeight;
    svg.setAttribute('viewBox', `0 0 ${w} ${h}`); svg.setAttribute('width', w); svg.setAttribute('height', h);
    svg.replaceChildren(); paths = [];
    data.edges.forEach(edge => {
      const from = cards.get(Number(edge.from_match_id)), to = cards.get(Number(edge.to_match_id));
      if (!from || !to) return;
      const a = layout(from), b = layout(to);
      const ltr = a.left < b.left;
      const x1 = ltr ? a.right : a.left, y1 = a.top + a.height / 2;
      const x2 = ltr ? b.left : b.right, y2 = b.top + b.height / 2;
      const xm = x1 + (x2 - x1) / 2;
      const p = document.createElementNS(NS, 'path');
      p.setAttribute('d', `M${x1} ${y1} H${xm} V${y2} H${x2}`);
      p.dataset.from = edge.from_match_id; p.dataset.to = edge.to_match_id;
      svg.appendChild(p); paths.push(p);
    });
    apply(locked);
  };

  const teamOf = (matchId, teamId) => cards.get(matchId)?.dataset.teams.split(' ').includes(String(teamId));
  const apply = teamId => {
    const active = teamId != null && teamId !== '';
    root.classList.toggle('has-focus', active);
    cards.forEach(card => {
      const on = active && card.dataset.teams.split(' ').includes(String(teamId));
      card.classList.toggle('is-path', on);
      card.querySelectorAll('[data-team]').forEach(row => row.classList.toggle('is-focus', on && row.dataset.team === String(teamId)));
    });
    paths.forEach(p => p.classList.toggle('is-path', active && teamOf(Number(p.dataset.from), teamId) && teamOf(Number(p.dataset.to), teamId)));
    chips.forEach(c => c.setAttribute('aria-pressed', String(active ? c.dataset.bmTeam === String(teamId) : c.dataset.bmTeam === '')));
    // Toggle visibility only: the box keeps its space so hovering never reflows the bracket.
    summary.classList.toggle('is-empty', !active);
    if (!active) return;
    const chip = chips.find(c => c.dataset.bmTeam === String(teamId));
    const reach = Number(chip?.dataset.reach || 0), champion = Number(teamId) === data.champion;
    const games = [...cards.values()].filter(c => c.dataset.teams.split(' ').includes(String(teamId)) && !c.classList.contains('bm-bronze')).length;
    summary.innerHTML = `<span>${chip?.innerHTML || ''}</span><b>${champion ? '🏆 冠军' : reachLabel[reach] || ''}</b><small>淘汰赛 ${games} 场</small>`;
    summary.classList.toggle('is-champion', champion);
    if (gsap && !reduced) gsap.fromTo(summary, { y: 8, autoAlpha: 0.4 }, { y: 0, autoAlpha: 1, duration: 0.4, ease: 'power3.out' });
  };

  root.addEventListener('pointerover', e => { if (locked != null || run) return; const row = e.target.closest('[data-team]'); if (row && Number(row.dataset.team)) apply(row.dataset.team); });
  root.addEventListener('pointerleave', () => { if (locked == null && !run) apply(null); });
  board.addEventListener('pointerout', e => { if (locked == null && !run && !e.relatedTarget?.closest?.('[data-bm-match]')) apply(null); });
  chips.forEach(chip => chip.addEventListener('click', () => {
    stopRun();
    const id = chip.dataset.bmTeam; locked = id === '' || locked === id ? null : id; apply(locked);
    const first = locked && [...cards.values()].find(c => c.classList.contains('is-path'));
    if (first && scroller.scrollWidth > scroller.clientWidth) scroller.scrollTo({ left: first.offsetLeft - scroller.clientWidth / 2, behavior: reduced ? 'auto' : 'smooth' });
  }));

  // Drag to pan on narrow screens.
  let drag = null;
  scroller.addEventListener('pointerdown', e => { if (e.pointerType !== 'mouse' || scroller.scrollWidth <= scroller.clientWidth) return; drag = { x: e.clientX, left: scroller.scrollLeft, moved: false }; });
  window.addEventListener('pointermove', e => { if (!drag) return; const dx = e.clientX - drag.x; if (Math.abs(dx) > 4) drag.moved = true; scroller.scrollLeft = drag.left - dx; });
  window.addEventListener('pointerup', () => { setTimeout(() => { drag = null; }, 0); });
  scroller.addEventListener('click', e => { if (drag?.moved) { e.preventDefault(); e.stopPropagation(); } }, true);

  draw();
  new ResizeObserver(draw).observe(board);
  window.addEventListener('load', draw);
  document.fonts?.ready.then(draw);

  // Champion run: a comet follows the champion's edges round by round, each card flashes as it passes,
  // and the final ends in a gold burst. Any hover on a team or chip click hands control straight back.
  const runButton = root.querySelector('[data-bm-run]');
  let run = null;
  const championRoute = () => {
    const champ = String(data.champion), has = id => cards.get(id)?.dataset.teams.split(' ').includes(champ);
    const edges = data.edges.filter(e => has(Number(e.from_match_id)) && has(Number(e.to_match_id)));
    let current = edges.find(e => !edges.some(o => Number(o.to_match_id) === Number(e.from_match_id)))?.from_match_id;
    const route = [];
    while (current != null) {
      const edge = edges.find(e => Number(e.from_match_id) === Number(current)); if (!edge) break;
      const path = paths.find(p => p.dataset.from === String(edge.from_match_id) && p.dataset.to === String(edge.to_match_id));
      if (path) route.push({ path, from: cards.get(Number(edge.from_match_id)), to: cards.get(Number(edge.to_match_id)) });
      current = edge.to_match_id;
    }
    return route;
  };
  const burst = card => {
    const canvas = document.createElement('canvas'), w = board.offsetWidth, h = board.offsetHeight, box = layout(card);
    canvas.className = 'bm-burst'; canvas.width = w; canvas.height = h; board.appendChild(canvas);
    const ctx = canvas.getContext('2d'), ox = (box.left + box.right) / 2, oy = box.top + box.height / 2;
    const bits = Array.from({ length: 140 }, () => {
      const a = Math.random() * Math.PI * 2, v = 3 + Math.random() * 7;
      return { x: ox, y: oy, vx: Math.cos(a) * v, vy: Math.sin(a) * v - 3, r: Math.random() * 6, vr: Math.random() * 0.3 - 0.15, s: 4 + Math.random() * 6, c: ['#f2c75c', '#c9962f', '#d33e50', '#1d3557', '#ffd58a'][Math.floor(Math.random() * 5)] };
    });
    const state = { t: 0 };
    gsap.to(state, { t: 1, duration: 1.8, ease: 'none', onUpdate: () => {
      ctx.clearRect(0, 0, w, h); ctx.globalAlpha = 1 - state.t;
      bits.forEach(b => { b.x += b.vx; b.y += b.vy; b.vy += 0.22; b.vx *= 0.98; b.r += b.vr; ctx.save(); ctx.translate(b.x, b.y); ctx.rotate(b.r); ctx.fillStyle = b.c; ctx.fillRect(-b.s / 2, -b.s / 4, b.s, b.s / 2); ctx.restore(); });
    }, onComplete: () => canvas.remove() });
  };
  const stopRun = () => {
    if (!run) return;
    run.tl.kill(); run.comet.remove(); root.classList.remove('is-running');
    cards.forEach(c => c.classList.remove('is-hit')); run = null;
  };
  const startRun = () => {
    stopRun(); if (!gsap || reduced || data.champion == null) return;
    const route = championRoute(); if (!route.length) return;
    locked = null; apply(String(data.champion)); root.classList.add('is-running');
    const comet = document.createElementNS(NS, 'g'); comet.setAttribute('class', 'bm-comet');
    const dots = [5, 4, 3.2, 2.5, 1.8, 1.2].map((r, i) => { const c = document.createElementNS(NS, 'circle'); c.setAttribute('r', r); c.style.opacity = String(1 - i * 0.15); comet.appendChild(c); return c; });
    svg.appendChild(comet);
    const trail = [], at = { len: 0 };
    const tl = gsap.timeline({ onComplete: () => { run = null; root.classList.remove('is-running'); comet.remove(); gsap.delayedCall(2.4, () => { if (!run && locked == null && !root.matches(':hover')) apply(null); }); } });
    const hit = card => { card.classList.remove('is-hit'); void card.offsetWidth; card.classList.add('is-hit'); };
    tl.call(() => hit(route[0].from));
    route.forEach(({ path, to }) => {
      const len = path.getTotalLength();
      tl.fromTo(at, { len: 0 }, { len, duration: 0.55, ease: 'power1.inOut', onUpdate: () => {
        trail.unshift(path.getPointAtLength(at.len)); trail.length = Math.min(trail.length, 24);
        dots.forEach((d, i) => { const q = trail[Math.min(i * 3, trail.length - 1)]; d.setAttribute('cx', q.x); d.setAttribute('cy', q.y); });
      } }).call(() => hit(to)).to({}, { duration: 0.12 });
    });
    tl.call(() => { burst(route[route.length - 1].to); gsap.fromTo(root.querySelector('.bm-trophy'), { scale: 1 }, { scale: 1.35, duration: 0.25, yoyo: true, repeat: 1, ease: 'power2.out' }); });
    run = { tl, comet };
  };
  runButton?.addEventListener('click', startRun);
  root.addEventListener('pointerover', e => { if (run && e.target.closest('[data-team]')) stopRun(); });

  // Entrance: columns sweep in from the outside, then the lines draw toward the final.
  if (gsap && window.ScrollTrigger && !reduced) {
    const cols = [...root.querySelectorAll('.bm-col')];
    gsap.set(svg, { autoAlpha: 0 });
    window.ScrollTrigger.create({ trigger: board, start: 'top 75%', once: true, onEnter: () => {
      const tl = gsap.timeline();
      tl.from(cols.filter(c => !c.classList.contains('bm-center')), { autoAlpha: 0, x: i => (cols[i]?.classList.contains('bm-col-left') ? -40 : 40), duration: 0.7, stagger: 0.06, ease: 'power3.out' })
        .from(root.querySelector('.bm-center'), { autoAlpha: 0, scale: 0.9, duration: 0.8, ease: 'back.out(1.4)' }, 0.25)
        .set(svg, { autoAlpha: 1 })
        .add(() => paths.forEach((p, i) => { const len = p.getTotalLength(); gsap.fromTo(p, { strokeDasharray: len, strokeDashoffset: len }, { strokeDashoffset: 0, duration: 0.9, delay: (i % 16) * 0.025, ease: 'power2.inOut', onComplete: () => { p.style.strokeDasharray = ''; } }); }))
        .add(() => { if (locked == null && !root.matches(':hover')) startRun(); }, '+=1.6');
    } });
  }
})();
