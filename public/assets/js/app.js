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
  const activeMarker = document.querySelector('[data-globe-active-marker]');
  const readout = document.querySelector('[data-globe-readout]');
  const countryCards = Array.from(document.querySelectorAll('[data-country-card]'));
  if (globe && sphere && activeMarker && readout && countryCards.length) {
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

    let activePoint = null;
    const escapeHtml = (value) => String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');

    const renderReadout = (point) => {
      if (!point) {
        readout.innerHTML = `
          <span>ROTATING EARTH</span>
          <strong>先选一个国家</strong>
          <small>现在不显示密集点；点击国家后显示唯一红色定位点。</small>
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

    const placeMarker = (point) => {
      const rect = sphere.getBoundingClientRect();
      const radius = Math.min(rect.width, rect.height) * 0.43;
      let x = (point.lng / 180) * radius * 0.92;
      let y = (-point.lat / 90) * radius * 0.78;
      const distance = Math.hypot(x, y);
      const maxDistance = radius * 0.90;
      if (distance > maxDistance) {
        const scale = maxDistance / distance;
        x *= scale;
        y *= scale;
      }
      activeMarker.style.setProperty('--x', `${x}px`);
      activeMarker.style.setProperty('--y', `${y}px`);
      activeMarker.hidden = false;
      sphere.classList.add('has-country-focus');
    };

    const selectCountry = (point, scroll) => {
      activePoint = point;
      countryCards.forEach((card) => card.classList.toggle('active', card.dataset.code === point.code));
      renderReadout(point);
      placeMarker(point);
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

    window.addEventListener('resize', () => {
      if (activePoint) placeMarker(activePoint);
    });

    renderReadout(null);
  }

})();
