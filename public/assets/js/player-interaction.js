/* Portrait-relative hit areas and short action replays; no changes to player records. */
(() => {
  const fine = matchMedia('(hover: hover) and (pointer: fine)').matches;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const portraitBounds = new WeakMap();
  const actions = {
    header: { title: '头球破门', hint: '头部 · 停留片刻，头球攻门', start: '迎球起跳', result: '头球破门！', caption: '起跳、甩头，足球飞入球网。', icon: '✓' },
    handball: { title: '手球犯规', hint: '手部 · 停留片刻，看看判罚', start: '来球触手', result: '哨声响起，手球犯规', caption: '足球碰到手臂，裁判鸣哨中止进攻。', icon: '!' },
    kick: { title: '起脚射门', hint: '腿脚 · 停留片刻，起脚射门', start: '调整步点', result: '射门得分！', caption: '摆腿、击球，足球直奔球门。', icon: '✓' }
  };
  const kits = {
    POR: ['#bc4256', '#31584c', '#bc4256'], ARG: ['#75aec9', '#344b61', '#f8fbff'],
    CRO: ['#c85160', '#f3f7fb', '#c85160']
  };

  // Ignore the transparent padding in FIFA portraits when locating the body.
  function measurePortrait(image) {
    if (!image?.complete || !image.naturalWidth || !image.naturalHeight) return null;
    if (portraitBounds.has(image)) return portraitBounds.get(image);
    let bounds = { left: .25, top: .04, width: .5, height: .92, head: .5 };
    try {
      const canvas = document.createElement('canvas');
      canvas.width = 96;
      canvas.height = Math.round(96 * image.naturalHeight / image.naturalWidth);
      const context = canvas.getContext('2d', { willReadFrequently: true });
      context.drawImage(image, 0, 0, canvas.width, canvas.height);
      const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
      let left = canvas.width, right = -1, top = canvas.height, bottom = -1;
      for (let y = 0; y < canvas.height; y++) {
        for (let x = 0; x < canvas.width; x++) {
          if (pixels[(y * canvas.width + x) * 4 + 3] < 70) continue;
          left = Math.min(left, x); right = Math.max(right, x);
          top = Math.min(top, y); bottom = Math.max(bottom, y);
        }
      }
      if (right > left && bottom > top && right - left < canvas.width * .9) {
        let headLeft = right, headRight = left;
        const headBottom = top + (bottom - top) * .14;
        for (let y = top; y <= headBottom; y++) {
          for (let x = left; x <= right; x++) {
            if (pixels[(y * canvas.width + x) * 4 + 3] < 70) continue;
            headLeft = Math.min(headLeft, x); headRight = Math.max(headRight, x);
          }
        }
        bounds = {
          left: left / canvas.width, top: top / canvas.height,
          width: (right - left + 1) / canvas.width, height: (bottom - top + 1) / canvas.height,
          head: ((headLeft + headRight) / 2 - left) / (right - left + 1)
        };
      }
    } catch (_) {
      // Remote portraits without CORS remain usable with the standing-pose fallback.
    }
    portraitBounds.set(image, bounds);
    return bounds;
  }

  function bodyZone(image, point) {
    const bounds = measurePortrait(image);
    if (!bounds) return null;
    const box = image.getBoundingClientRect();
    if (!box.width || !box.height) return null;
    const scale = Math.min(box.width / image.naturalWidth, box.height / image.naturalHeight);
    const width = image.naturalWidth * scale, height = image.naturalHeight * scale;
    const position = getComputedStyle(image).objectPosition.split(' ');
    const fraction = (value, fallback) => value?.endsWith('%') ? parseFloat(value) / 100 : fallback;
    const left = box.left + (box.width - width) * fraction(position[0], .5);
    const top = box.top + (box.height - height) * fraction(position[1], 1);
    const x = ((point.x - left) / width - bounds.left) / bounds.width;
    const y = ((point.y - top) / height - bounds.top) / bounds.height;
    if (y >= -.045 && y <= .2 && Math.abs(x - bounds.head) <= .28) return 'header';
    if (y >= .30 && y <= .61 && ((x >= -.13 && x <= .27) || (x >= .73 && x <= 1.13))) return 'handball';
    if (y >= .63 && y <= 1.055 && x >= -.08 && x <= 1.08) return 'kick';
    return null;
  }

  document.querySelectorAll('.spotlight, .athlete-stage').forEach(stage => {
    const playground = stage.querySelector('[data-player-playground]');
    if (!playground) return;
    playground.hidden = false;
    const replay = playground.querySelector('[data-action-replay]');
    const buttons = [...playground.querySelectorAll('[data-player-action]')];
    const hint = playground.querySelector('[data-action-hint]');
    const status = playground.querySelector('[data-action-status]');
    const idleHint = fine ? '足球靠近头部、手部或腿脚，触发球场瞬间' : '点选一个动作，看看球场瞬间';
    hint.textContent = idleHint;
    const ball = fine ? document.createElement('span') : null;
    if (ball) {
      ball.className = 'football-pointer';
      ball.setAttribute('aria-hidden', 'true');
      ball.hidden = true;
      stage.append(ball);
    }
    let frame = 0, point = null, dwell = 0, resultTimer = 0, endTimer = 0;
    let candidate = null, playedZone = null, running = false, trigger = null;
    const activePanel = () => stage.querySelector('[data-player-panel].is-active') || stage;
    const portrait = () => activePanel().querySelector('.spotlight-photo, .athlete-portrait');
    const cancelDwell = () => { clearTimeout(dwell); dwell = 0; };

    function placeReplay() {
      if (replay.hidden) return;
      const box = stage.getBoundingClientRect();
      const width = Math.min(innerWidth - 32, innerWidth <= 760 ? 440 : Math.max(340, Math.min(520, box.width * .48)));
      replay.style.width = width + 'px';
      const left = Math.max(16, Math.min(innerWidth - width - 16, box.right - width - 24));
      const navBottom = document.querySelector('#site-header')?.getBoundingClientRect().bottom || 0;
      const minimumTop = Math.max(16, navBottom + 12);
      replay.style.maxHeight = Math.max(180, innerHeight - minimumTop - 16) + 'px';
      const top = Math.max(minimumTop, Math.min(box.top + 24, innerHeight - replay.offsetHeight - 16));
      replay.style.left = left + 'px';
      replay.style.top = top + 'px';
    }

    function closeReplay(restoreFocus = false) {
      clearTimeout(resultTimer); clearTimeout(endTimer); cancelDwell();
      const focusInside = replay.contains(document.activeElement);
      if (typeof replay.hidePopover === 'function' && replay.matches(':popover-open')) replay.hidePopover();
      replay.hidden = true;
      replay.classList.remove('is-playing');
      running = false;
      buttons.forEach(button => button.setAttribute('aria-pressed', 'false'));
      hint.textContent = idleHint;
      if ((restoreFocus || focusInside) && trigger) trigger.focus({ preventScroll: true });
    }

    function playAction(action, button = null) {
      if (!actions[action]) return;
      closeReplay();
      trigger = button;
      playedZone = action;
      running = true;
      const panel = activePanel(), data = actions[action];
      const heading = panel.querySelector('.spotlight-copy h2, .athlete-copy h1');
      const parts = [...(heading?.querySelectorAll('span') || [])].map(part => part.textContent.trim());
      const name = parts.length ? parts.join('·') : heading?.textContent.trim() || '场上主角';
      const number = panel.querySelector('.edition-shirt-number, .athlete-shirt')?.firstChild?.textContent.trim() || '7';
      const code = panel.querySelector('.edition-player-watermark, .athlete-watermark')?.textContent.trim();
      const colors = kits[code] || kits.POR;
      ['--action-kit', '--action-shorts', '--action-sock'].forEach((key, index) => playground.style.setProperty(key, colors[index]));
      replay.dataset.action = action;
      replay.querySelector('[data-action-title]').textContent = data.title;
      replay.querySelector('[data-action-player]').textContent = name + ' · ' + number + ' 号';
      replay.querySelector('[data-action-number]').textContent = number;
      replay.querySelector('[data-action-result]').textContent = data.start;
      replay.querySelector('[data-action-caption]').textContent = '这一球，会发生什么？';
      replay.querySelector('[data-action-indicator]').textContent = '↗';
      buttons.forEach(item => item.setAttribute('aria-pressed', String(item.dataset.playerAction === action)));
      replay.hidden = false;
      if (typeof replay.showPopover === 'function') replay.showPopover();
      placeReplay();
      // A manual replay restarts the scene, including repeated presses of the same button.
      void replay.offsetWidth;
      replay.classList.add('is-playing');
      const showResult = () => {
        replay.querySelector('[data-action-result]').textContent = data.result;
        replay.querySelector('[data-action-caption]').textContent = data.caption;
        replay.querySelector('[data-action-indicator]').textContent = data.icon;
        status.textContent = name + '：' + data.result;
      };
      if (reduced) showResult();
      else resultTimer = setTimeout(showResult, 1700);
      endTimer = setTimeout(() => closeReplay(), reduced ? 5000 : 4300);
    }

    function consider(action) {
      if (action === candidate) return;
      cancelDwell();
      candidate = action;
      if (!action) playedZone = null;
      if (!running) hint.textContent = action ? actions[action].hint : idleHint;
      if (!action || running || action === playedZone) return;
      const expectedPanel = activePanel();
      dwell = setTimeout(() => {
        if (candidate === action && expectedPanel === activePanel() && !running) playAction(action);
      }, 220);
    }

    const update = () => {
      frame = 0;
      if (!point || !ball) return;
      const box = stage.getBoundingClientRect();
      ball.style.transform = `translate3d(${point.x - box.left - 12}px,${point.y - box.top - 12}px,0)`;
      consider(bodyZone(portrait(), point));
    };
    if (fine) {
      stage.addEventListener('pointermove', event => {
        const interactive = event.target.closest('a, button, input, select, textarea, [data-action-replay]');
        ball.hidden = Boolean(interactive);
        stage.classList.toggle('football-active', !interactive);
        if (interactive) { point = null; consider(null); return; }
        point = { x: event.clientX, y: event.clientY };
        if (!frame) frame = requestAnimationFrame(update);
      }, { passive: true });
      stage.addEventListener('pointerleave', () => {
        stage.classList.remove('football-active'); ball.hidden = true;
        point = null; consider(null);
        if (frame) cancelAnimationFrame(frame);
        frame = 0;
      });
    }
    buttons.forEach(button => button.addEventListener('click', () => playAction(button.dataset.playerAction, button)));
    playground.querySelector('[data-action-close]').addEventListener('click', () => closeReplay(true));
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !replay.hidden) { closeReplay(true); event.preventDefault(); }
    });
    stage.addEventListener('player:change', () => {
      closeReplay(); candidate = null; playedZone = null; point = null;
      if (ball) ball.hidden = true;
      stage.classList.remove('football-active');
    });
    new IntersectionObserver(([entry]) => {
      if (!entry.isIntersecting) { closeReplay(); point = null; candidate = null; playedZone = null; }
    }).observe(stage);
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) closeReplay();
    });
    window.addEventListener('resize', placeReplay, { passive: true });
  });
})();
