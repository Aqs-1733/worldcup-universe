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
  const markerLayer = document.querySelector('[data-globe-markers]');
  const readout = document.querySelector('[data-globe-readout]');
  const countryCards = Array.from(document.querySelectorAll('[data-country-card]'));
  if (globe && markerLayer && readout && countryCards.length) {
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

    const markers = new Map();
    points.forEach((point) => {
      const marker = document.createElement('button');
      marker.type = 'button';
      marker.className = 'globe-marker';
      marker.title = point.name;
      marker.dataset.code = point.code;
      marker.addEventListener('click', () => selectCountry(point, true));
      markerLayer.appendChild(marker);
      markers.set(point.code, marker);
    });

    let centerLat = 18;
    let centerLng = 0;
    let targetLat = centerLat;
    let targetLng = centerLng;
    let focusedUntil = 0;
    let activeCode = '';
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
      const safeFlag = escapeHtml(point.flag);
      readout.innerHTML = `
        <span>${escapeHtml(point.code)}</span>
        <strong>${safeFlag ? `<img class="inline-flag" src="${safeFlag}" alt="">` : ''}${escapeHtml(point.name)}</strong>
        <small>${escapeHtml(point.capital)} · ${escapeHtml(point.region)} · ${point.lat.toFixed(2)}°, ${point.lng.toFixed(2)}°</small>
      `;
    };

    const selectCountry = (point, scroll) => {
      activeCode = point.code;
      targetLat = Math.max(-58, Math.min(58, point.lat));
      targetLng = normalizeLng(point.lng);
      focusedUntil = performance.now() + 8500;
      countryCards.forEach((card) => card.classList.toggle('active', card.dataset.code === point.code));
      markers.forEach((marker, code) => marker.classList.toggle('active', code === point.code));
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

    const project = (point, radius, now) => {
      const rad = Math.PI / 180;
      const phi = point.lat * rad;
      const lambda = (point.lng - centerLng) * rad;
      const phi0 = centerLat * rad;
      const cosc = Math.sin(phi0) * Math.sin(phi) + Math.cos(phi0) * Math.cos(phi) * Math.cos(lambda);
      const x = radius * Math.cos(phi) * Math.sin(lambda);
      const y = -radius * (Math.cos(phi0) * Math.sin(phi) - Math.sin(phi0) * Math.cos(phi) * Math.cos(lambda));
      return { x, y, visible: cosc > -0.08, depth: Math.max(0, cosc) };
    };

    const tick = (now) => {
      if (now > focusedUntil) {
        targetLng += 0.035;
        targetLat = 12 + Math.sin(now / 3400) * 6;
      }
      centerLat += (targetLat - centerLat) * 0.04;
      centerLng += (targetLng - centerLng) * 0.04;
      const rect = markerLayer.getBoundingClientRect();
      const radius = Math.min(rect.width, rect.height) * 0.43;
      points.forEach((point) => {
        const marker = markers.get(point.code);
        if (!marker) return;
        const projected = project(point, radius, now);
        marker.style.setProperty('--x', `${projected.x}px`);
        marker.style.setProperty('--y', `${projected.y}px`);
        marker.style.setProperty('--opacity', projected.visible ? String(0.28 + projected.depth * 0.72) : '0');
        marker.style.setProperty('--scale', point.code === activeCode ? '1.45' : String(0.62 + projected.depth * 0.58));
        marker.hidden = !projected.visible && point.code !== activeCode;
      });
      requestAnimationFrame(tick);
    };

    selectCountry(points[0], false);
    requestAnimationFrame(tick);
  }

})();
