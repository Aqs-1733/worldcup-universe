(() => {
  const root = document.documentElement;
  const savedTheme = localStorage.getItem('worldcup-theme');
  if (savedTheme) root.dataset.theme = savedTheme;

  document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem('worldcup-theme', root.dataset.theme);
  });

  const reveal = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) entry.target.classList.add('seen');
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach((node) => reveal.observe(node));

  document.querySelectorAll('[data-bars]').forEach((chart) => {
    setTimeout(() => chart.classList.add('active'), 180);
  });


  document.querySelectorAll('.panel, .page-title, .card-grid > *, .match-list > *, .news-list > *, .travel-grid > *, .country-grid > *, .group-card, .team-chip, .quality-chip, tbody tr').forEach((node, index) => {
    node.classList.add('motion-item');
    node.style.setProperty('--stagger', `${Math.min(index % 12, 11) * 45}ms`);
  });

  document.querySelectorAll('[data-filter-input]').forEach((input) => {
    input.addEventListener('input', () => {
      const target = document.querySelector(input.dataset.filterTarget);
      const keyword = input.value.trim().toLowerCase();
      target?.querySelectorAll('[data-filter-text]').forEach((item) => {
        item.hidden = keyword !== '' && !item.dataset.filterText.toLowerCase().includes(keyword);
      });
    });
  });

  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
      form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.dataset.originalText = button.textContent;
        button.textContent = button.textContent?.includes('生成') ? '生成中...' : '处理中...';
        button.setAttribute('disabled', 'disabled');
      });
    });
  });

  document.querySelectorAll('.module-card, .team-card, .player-card, .news-card, .country-card, .travel-card').forEach((card) => {
    card.addEventListener('mousemove', (event) => {
      const rect = card.getBoundingClientRect();
      card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
      card.style.setProperty('--my', `${event.clientY - rect.top}px`);
    });
  });

  const globe = document.querySelector('[data-country-globe]');
  const sphere = document.querySelector('[data-globe-sphere]');
  const globeMap = document.querySelector('[data-globe-map]');
  const activeMarker = document.querySelector('[data-globe-active-marker]');
  const readout = document.querySelector('[data-globe-readout]');
  const countryCards = Array.from(document.querySelectorAll('[data-country-card]'));
  if (globe && sphere && globeMap && activeMarker && readout && countryCards.length) {
    const points = countryCards
      .map((card) => ({
        card,
        code: card.dataset.code || '',
        name: card.dataset.name || '',
        capital: card.dataset.capital || '',
        region: card.dataset.region || '',
        flag: card.dataset.flag || '',
        lat: Number(card.dataset.lat),
        lng: Number(card.dataset.lng),
      }))
      .filter((point) => Number.isFinite(point.lat) && Number.isFinite(point.lng));

    let centerLat = 14;
    let centerLng = 0;
    let targetLat = centerLat;
    let targetLng = centerLng;
    let focusedUntil = 0;
    let activePoint = null;
    const escapeHtml = (value) => String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');

    const normalizeLng = (lng) => {
      let next = lng;
      while (next - centerLng > 180) next -= 360;
      while (next - centerLng < -180) next += 360;
      return next;
    };

    const renderReadout = (point) => {
      if (!point) {
        readout.innerHTML = `
          <span>ROTATING EARTH</span>
          <strong>点击国家卡片定位</strong>
          <small>平时只显示可旋转地球；选择国家后显示唯一定位点。</small>
        `;
        return;
      }
      const safeFlag = escapeHtml(point.flag);
      readout.innerHTML = `
        <span>${escapeHtml(point.code)}</span>
        <strong>${safeFlag ? `<img class="inline-flag" src="${safeFlag}" alt="">` : ''}${escapeHtml(point.name)}</strong>
        <small>${escapeHtml(point.capital)} · ${escapeHtml(point.region)} · ${point.lat.toFixed(2)}°, ${point.lng.toFixed(2)}°</small>
      `;
    };

    const selectCountry = (point, scroll) => {
      activePoint = point;
      targetLat = Math.max(-58, Math.min(58, point.lat));
      targetLng = normalizeLng(point.lng);
      focusedUntil = performance.now() + 9000;
      activeMarker.hidden = false;
      countryCards.forEach((card) => card.classList.toggle('active', card.dataset.code === point.code));
      renderReadout(point);
      if (scroll) {
        point.card.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    };

    countryCards.forEach((card) => {
      const point = points.find((item) => item.code === card.dataset.code);
      if (!point) return;
      card.addEventListener('click', (event) => {
        if (event.target.closest('a')) return;
        selectCountry(point, false);
      });
      card.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          selectCountry(point, false);
        }
      });
    });

    const project = (point, radius) => {
      const rad = Math.PI / 180;
      const phi = point.lat * rad;
      const lambda = (point.lng - centerLng) * rad;
      const phi0 = centerLat * rad;
      const cosc = Math.sin(phi0) * Math.sin(phi) + Math.cos(phi0) * Math.cos(phi) * Math.cos(lambda);
      const x = radius * Math.cos(phi) * Math.sin(lambda);
      const y = -radius * (Math.cos(phi0) * Math.sin(phi) - Math.sin(phi0) * Math.cos(phi) * Math.cos(lambda));
      return { x, y, visible: cosc > -0.02, depth: Math.max(0, cosc) };
    };

    const tick = (now) => {
      if (now > focusedUntil) {
        targetLng += 0.032;
        targetLat = 8 + Math.sin(now / 4200) * 7;
      }
      centerLat += (targetLat - centerLat) * 0.035;
      centerLng += (targetLng - centerLng) * 0.035;
      sphere.style.setProperty('--spin-lng', `${(-centerLng % 360) / 360 * 50}%`);
      globeMap.style.setProperty('--globe-tilt', `${centerLat * -0.22}px`);

      if (activePoint) {
        const rect = sphere.getBoundingClientRect();
        const radius = Math.min(rect.width, rect.height) * 0.43;
        const projected = project(activePoint, radius);
        activeMarker.style.setProperty('--x', `${projected.x}px`);
        activeMarker.style.setProperty('--y', `${projected.y}px`);
        activeMarker.style.setProperty('--opacity', projected.visible ? String(0.38 + projected.depth * 0.62) : '0');
        activeMarker.style.setProperty('--scale', String(0.82 + projected.depth * 0.62));
        activeMarker.hidden = !projected.visible;
      }
      requestAnimationFrame(tick);
    };

    renderReadout(null);
    requestAnimationFrame(tick);
  }

})();
