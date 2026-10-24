(() => {
  const root = document.querySelector('[data-globe-3d]');
  if (!root) return;

  const stage = root.querySelector('[data-globe-stage]');
  const canvasHost = root.querySelector('[data-globe-canvas]');
  const loading = root.querySelector('[data-globe-loading]');
  const unavailable = root.querySelector('[data-globe-unavailable]');
  const featureCount = root.querySelector('[data-globe-feature-count]');
  const continentButtons = Array.from(root.querySelectorAll('[data-globe-continent]'));
  const readout = {
    kicker: root.querySelector('[data-globe-readout-kicker]'),
    title: root.querySelector('[data-globe-readout-title]'),
    detail: root.querySelector('[data-globe-readout-detail]'),
  };
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const supportedContinents = new Set(['North America', 'South America', 'Europe', 'Africa', 'Asia', 'Oceania']);
  const continentViews = {
    'North America': { lat: 38, lng: -102, altitude: 1.72 },
    'South America': { lat: -16, lng: -61, altitude: 1.78 },
    Europe: { lat: 50, lng: 14, altitude: 1.55 },
    Africa: { lat: 4, lng: 19, altitude: 1.65 },
    Asia: { lat: 33, lng: 91, altitude: 1.62 },
    Oceania: { lat: -22, lng: 145, altitude: 1.78 },
  };

  const canUseWebGl = () => {
    const canvas = document.createElement('canvas');
    return Boolean(window.WebGLRenderingContext && (canvas.getContext('webgl2') || canvas.getContext('webgl')));
  };

  const showUnavailable = (message) => {
    loading.hidden = true;
    unavailable.hidden = false;
    unavailable.textContent = message;
    featureCount.textContent = '列表模式';
    continentButtons.forEach((button) => { button.disabled = true; });
  };

  const updateReadout = (feature) => {
    if (!feature) {
      readout.kicker.textContent = '国家探索';
      readout.title.textContent = '拖动地球探索真实国家轮廓';
      readout.detail.textContent = '';
      return;
    }
    const properties = feature.properties;
    const name = properties.NAME_ZH || properties.NAME_EN || properties.NAME;
    const iso = properties.ISO_A3 && properties.ISO_A3 !== '-99' ? ` · ISO ${properties.ISO_A3}` : '';
    readout.kicker.textContent = '已选地区';
    readout.title.textContent = name;
    readout.detail.textContent = '';
  };

  const featureView = (feature) => ({
    lat: Number(feature.properties.LABEL_Y) || 0,
    lng: Number(feature.properties.LABEL_X) || 0,
    altitude: feature.properties.CONTINENT === 'Europe' ? 1.45 : 1.7,
  });
  const normalizeGeometry = (geometry) => {
    const normalizeRing = (ring) => ring.map(([lng, lat, ...rest]) => [
      lng === 180 ? 179.999 : lng === -180 ? -179.999 : lng,
      lat,
      ...rest,
    ]);

    if (geometry.type === 'Polygon') {
      return { ...geometry, coordinates: geometry.coordinates.map(normalizeRing) };
    }
    return { ...geometry, coordinates: geometry.coordinates.map((polygon) => polygon.map(normalizeRing)) };
  };
  const geometryArea = (geometry) => {
    const ringArea = (ring) => Math.abs(ring.reduce((area, [lng, lat], index) => {
      const [nextRawLng, nextLat] = ring[(index + 1) % ring.length];
      let nextLng = nextRawLng;
      while (nextLng - lng > 180) nextLng -= 360;
      while (nextLng - lng < -180) nextLng += 360;
      return area + lng * nextLat - nextLng * lat;
    }, 0) / 2);
    const polygons = geometry.type === 'Polygon' ? [geometry.coordinates] : geometry.coordinates;
    return polygons.reduce((area, polygon) => area + ringArea(polygon[0]) - polygon.slice(1).reduce((holes, ring) => holes + ringArea(ring), 0), 0);
  };

  if (!window.Globe || !canUseWebGl()) {
    showUnavailable('此设备无法启动 3D 地球。你仍可使用下面的国家列表探索内容。');
    return;
  }

  fetch('/assets/data/globe/natural-earth-admin0-map-units-110m.web.geojson')
    .then((response) => {
      if (!response.ok) throw new Error(`地图资源加载失败（${response.status}）`);
      return response.json();
    })
    .then((source) => {
      const features = source.features
        .filter((feature) => supportedContinents.has(feature.properties?.CONTINENT))
        .map((feature) => {
          const mapFeatureId = `ne-${feature.properties.NE_ID}`;
          const geometry = normalizeGeometry(feature.geometry);
          return {
            ...feature,
            id: mapFeatureId,
            __id: mapFeatureId,
            geoKey: mapFeatureId,
            geometry,
            mapArea: geometryArea(geometry),
          };
        });
      if (!features.length) throw new Error('本地地图没有可用的大洲国家单元。');

      let activeFeature = null;
      let hoveredFeature = null;
      let selectedId = null;
      let hoveredId = null;
      let autoRotateTimer = null;
      let pointerOrigin = null;
      let pointerMoved = false;
      let hoverFrame = null;
      let lastDragAt = 0;
      const normalLandColor = '#6F9568';
      const normalBorderColor = '#C8D6DB';
      const oceanColor = '#245F9E';
      const hoverOutlineColor = '#F4C95D';
      const selectedOutlineColor = '#FFD86B';
      const featurePolygons = (feature) => (feature.geometry.type === 'Polygon'
        ? [feature.geometry.coordinates]
        : feature.geometry.coordinates);
      const buildOutlinePaths = (feature, color, altitude, stroke) => featurePolygons(feature)
        .flatMap((polygon, polygonIndex) => polygon.map((ring, ringIndex) => ({
          id: `${feature.geoKey}-${polygonIndex}-${ringIndex}-${altitude}`,
          color,
          stroke,
          points: ring.map(([lng, lat]) => ({ lat, lng, altitude })),
        })));
      const createMapTexture = (mapFeatures, fillFor = () => normalLandColor, ocean = oceanColor, border = normalBorderColor) => {
        const width = window.innerWidth <= 720 ? 1024 : 1536;
        const height = width / 2;
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const context = canvas.getContext('2d');
        context.fillStyle = ocean;
        context.fillRect(0, 0, width, height);
        const project = (lng, lat, xOffset) => [
          ((lng + 180) / 360) * width + xOffset,
          ((90 - lat) / 180) * height,
        ];
        const drawRing = (ring, xOffset) => {
          let previousLng = ring[0][0];
          let [x, y] = project(previousLng, ring[0][1], xOffset);
          context.moveTo(x, y);
          ring.slice(1).forEach(([rawLng, lat]) => {
            let lng = rawLng;
            while (lng - previousLng > 180) lng -= 360;
            while (lng - previousLng < -180) lng += 360;
            previousLng = lng;
            [x, y] = project(lng, lat, xOffset);
            context.lineTo(x, y);
          });
          context.closePath();
        };
        mapFeatures.forEach((feature) => featurePolygons(feature).forEach((polygon) => {
          context.beginPath();
          [-width, 0, width].forEach((xOffset) => polygon.forEach((ring) => drawRing(ring, xOffset)));
          context.fillStyle = fillFor(feature);
          context.fill('evenodd');
          context.strokeStyle = border;
          context.lineWidth = width / 1200;
          context.stroke();
        }));
        return canvas.toDataURL('image/png');
      };
      const pointInRing = (lng, lat, ring) => {
        let inside = false;
        for (let index = 0, previous = ring.length - 1; index < ring.length; previous = index++) {
          let [currentLng, currentLat] = ring[index];
          let [previousLng, previousLat] = ring[previous];
          let testLng = lng;
          if (Math.abs(currentLng - previousLng) > 180) {
            if (currentLng < 0) currentLng += 360;
            if (previousLng < 0) previousLng += 360;
            if (testLng < 0) testLng += 360;
          }
          const crossesLatitude = (currentLat > lat) !== (previousLat > lat);
          if (crossesLatitude && testLng < ((previousLng - currentLng) * (lat - currentLat)) / (previousLat - currentLat) + currentLng) {
            inside = !inside;
          }
        }
        return inside;
      };
      const pointInPolygon = (lng, lat, polygon) => (
        pointInRing(lng, lat, polygon[0]) && !polygon.slice(1).some((ring) => pointInRing(lng, lat, ring))
      );
      const findFeatureAt = ({ lat, lng }) => features.reduce((match, feature) => {
        if (!featurePolygons(feature).some((polygon) => pointInPolygon(lng, lat, polygon))) return match;
        return !match || feature.mapArea < match.mapArea ? feature : match;
      }, null);
      const mapTexture = createMapTexture(features);
      const globe = window.Globe()
        .globeImageUrl(mapTexture)
        .backgroundColor('rgba(0,0,0,0)')
        .showAtmosphere(true)
        .atmosphereColor('#7d9cff')
        .atmosphereAltitude(0.07)
        .polygonsData([])
        .polygonsTransitionDuration(0)
        .pathsData([])
        .pathPoints('points')
        .pathPointLat('lat')
        .pathPointLng('lng')
        .pathPointAlt('altitude')
        .pathColor('color')
        .pathStroke('stroke')
        .pathResolution(1)
        .pathTransitionDuration(0)
        (canvasHost);
      const renderFeatureOutlines = () => {
        const outlinePaths = [];
        if (hoveredFeature && hoveredId !== selectedId) {
          outlinePaths.push(...buildOutlinePaths(hoveredFeature, hoverOutlineColor, 0.009, 1.35));
        }
        if (activeFeature) {
          outlinePaths.push(...buildOutlinePaths(activeFeature, selectedOutlineColor, 0.014, 2));
        }
        globe.pathsData(outlinePaths);
      };
      const tooltip = document.createElement('div');
      tooltip.className = 'globe-country-tooltip';
      tooltip.hidden = true;
      stage.appendChild(tooltip);
      const setHoveredFeature = (feature, event) => {
        const nextId = feature?.geoKey || null;
        if (hoveredId !== nextId) {
          hoveredFeature = feature;
          hoveredId = nextId;
          renderFeatureOutlines();
          canvasHost.querySelector('canvas').style.cursor = feature ? 'pointer' : 'grab';
        }
        tooltip.hidden = !feature;
        if (feature) {
          const properties = feature.properties;
          tooltip.textContent = `${properties.NAME_ZH || properties.NAME_EN || properties.NAME}`;
          const bounds = stage.getBoundingClientRect();
          tooltip.style.left = `${event.clientX - bounds.left + 12}px`;
          tooltip.style.top = `${event.clientY - bounds.top + 12}px`;
        }
      };
      const selectFeature = (feature) => {
        if (!feature || performance.now() - lastDragAt < 250) return;
        activeFeature = feature;
        selectedId = feature.geoKey;
        updateReadout(feature);
        root.dispatchEvent(new CustomEvent('globe:select', { detail: { key: feature.geoKey } }));
        renderFeatureOutlines();
        globe.pointOfView(featureView(feature), reduceMotion ? 0 : 1100);
        continentButtons.forEach((button) => button.classList.remove('active'));
        pauseAutoRotate();
      };
      globe
        .pointerEventsFilter((object) => object.__globeObjType === 'globe');

      globe.width(stage.clientWidth).height(stage.clientHeight).pointOfView({ lat: 18, lng: 10, altitude: 2.05 }, 0);
      globe.renderer().setPixelRatio(Math.min(window.devicePixelRatio || 1, window.innerWidth <= 720 ? 1 : 1.25));
      const material = globe.globeMaterial();
      material.color.set('#ffffff');
      material.emissive.set('#0d2c58');
      material.emissiveIntensity = 0.08;
      material.shininess = 10;

      const controls = globe.controls();
      controls.enableDamping = true;
      controls.dampingFactor = 0.08;
      controls.enablePan = false;
      controls.minDistance = 135;
      controls.maxDistance = 600;
      controls.zoomSpeed = 0.65;
      controls.autoRotate = !reduceMotion;
      controls.autoRotateSpeed = 0.26;

      const refreshSize = () => {
        globe.width(stage.clientWidth).height(stage.clientHeight);
        globe.renderer().setPixelRatio(Math.min(window.devicePixelRatio || 1, window.innerWidth <= 720 ? 1 : 1.25));
      };
      const pauseAutoRotate = () => {
        if (reduceMotion) return;
        controls.autoRotate = false;
        window.clearTimeout(autoRotateTimer);
        autoRotateTimer = window.setTimeout(() => {
          controls.autoRotate = true;
        }, 5500);
      };
      root.querySelectorAll('[data-globe-zoom]').forEach((button) => button.addEventListener('click', () => {
        const view = globe.pointOfView();
        const altitude = Math.max(0.45, Math.min(4, view.altitude * (button.dataset.globeZoom === 'in' ? 0.78 : 1.28)));
        globe.pointOfView({ ...view, altitude }, reduceMotion ? 0 : 350); pauseAutoRotate();
      }));
      root.addEventListener('globe:focus', (event) => {
        const { key, lat, lng, name, countryKey } = event.detail;
        const feature = features.find((item) => item.geoKey === key);
        if (feature) selectFeature(feature);
        else {
          selectedId = null; activeFeature = null; renderFeatureOutlines();
          readout.kicker.textContent = '已选国家'; readout.title.textContent = name; readout.detail.textContent = '';
          globe.pointOfView({ lat, lng, altitude: 1.2 }, reduceMotion ? 0 : 800);
          root.dispatchEvent(new CustomEvent('globe:select', { detail: { key: countryKey } }));
        }
        pauseAutoRotate();
      });

      stage.addEventListener('pointerdown', (event) => {
        pointerOrigin = { x: event.clientX, y: event.clientY };
        pointerMoved = false;
        pauseAutoRotate();
      }, { passive: true });
      stage.addEventListener('click', (event) => {
        if (!canvasHost.contains(event.target)) return;
        if (performance.now() - lastDragAt < 250) return;
        const bounds = canvasHost.getBoundingClientRect();
        const coordinates = globe.toGlobeCoords(event.clientX - bounds.left, event.clientY - bounds.top);
        selectFeature(coordinates ? findFeatureAt(coordinates) : null);
      });
      stage.addEventListener('pointermove', (event) => {
        if (pointerOrigin && Math.hypot(event.clientX - pointerOrigin.x, event.clientY - pointerOrigin.y) > 6) {
          pointerMoved = true;
          lastDragAt = performance.now();
        }
        if (pointerMoved || hoverFrame) return;
        if (!pointerOrigin) pauseAutoRotate();
        hoverFrame = requestAnimationFrame(() => {
          hoverFrame = null;
          const bounds = canvasHost.getBoundingClientRect();
          const coordinates = globe.toGlobeCoords(event.clientX - bounds.left, event.clientY - bounds.top);
          setHoveredFeature(coordinates ? findFeatureAt(coordinates) : null, event);
        });
      }, { passive: true });
      ['pointerup', 'pointercancel'].forEach((eventName) => stage.addEventListener(eventName, () => {
        if (pointerMoved) lastDragAt = performance.now();
        pointerOrigin = null; pointerMoved = false;
      }, { passive: true }));
      stage.addEventListener('pointerleave', (event) => { setHoveredFeature(null, event); }, { passive: true });
      ['touchstart', 'wheel'].forEach((eventName) => stage.addEventListener(eventName, pauseAutoRotate, { passive: true }));
      const resizeObserver = new ResizeObserver(refreshSize);
      resizeObserver.observe(stage);

      // Database-backed country cards also work as keyboard-accessible controls.
      document.querySelectorAll('[data-country-card]').forEach((card) => {
        const focusCountry = () => {
          const lat = Number(card.dataset.lat);
          const lng = Number(card.dataset.lng);
          if (!card.dataset.lat || !card.dataset.lng || !Number.isFinite(lat) || !Number.isFinite(lng)) return;
          const feature = findFeatureAt({ lat, lng });
          if (feature) selectFeature(feature);
          else globe.pointOfView({ lat, lng, altitude: 1.5 }, reduceMotion ? 0 : 1000);
          readout.title.textContent = card.dataset.name;
          readout.detail.textContent = `${card.dataset.capital} · ${card.dataset.region}`;
          root.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        };
        card.addEventListener('click', (event) => { if (!event.target.closest('a')) focusCountry(); });
        card.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); focusCountry(); }
        });
      });
      root.querySelector('[data-globe-reset]')?.addEventListener('click', () => {
        selectedId = null; hoveredId = null; activeFeature = null; hoveredFeature = null;
        renderFeatureOutlines(); updateReadout(null);
        root.dispatchEvent(new CustomEvent('globe:reset'));
        globe.pointOfView({ lat: 18, lng: 10, altitude: 2.05 }, reduceMotion ? 0 : 1000);
        pauseAutoRotate();
      });
      const onVisibility = () => {
        if (document.hidden) { globe.pauseAnimation(); controls.autoRotate = false; }
        else { globe.resumeAnimation(); controls.autoRotate = !reduceMotion; }
      };
      document.addEventListener('visibilitychange', onVisibility);
      window.addEventListener('pagehide', (event) => {
        globe.pauseAnimation(); window.clearTimeout(autoRotateTimer);
        if (hoverFrame) cancelAnimationFrame(hoverFrame);
        if (!event.persisted) {
          resizeObserver.disconnect(); document.removeEventListener('visibilitychange', onVisibility);
          controls.dispose(); globe.renderer().dispose();
        }
      });
      window.addEventListener('pageshow', onVisibility);

      continentButtons.forEach((button) => {
        button.addEventListener('click', () => {
          const continent = button.dataset.globeContinent;
          const view = continentViews[continent];
          if (!view) return;
          activeFeature = null;
          selectedId = null;
          updateReadout(null);
          renderFeatureOutlines();
          globe.pointOfView(view, reduceMotion ? 0 : 1250);
          continentButtons.forEach((item) => item.classList.toggle('active', item === button));
          pauseAutoRotate();
        });
      });

      // ---------- Story modes: qualified map, flights to North America, host cities, a team's journey ----------
      const storyNode = document.querySelector('[data-globe-story]');
      const story = storyNode ? JSON.parse(storyNode.textContent) : null;
      if (story) {
        const REACH = { 1: ['#5b7c8f', '小组赛出局'], 2: ['#3f8fd2', '32 强'], 3: ['#2fc0c9', '16 强'], 4: ['#5fd38d', '8 强'], 5: ['#f08a3c', '4 强'], 6: ['#dfe5ec', '亚军'], 7: ['#f2c75c', '冠军'] };
        const CONF = { UEFA: '#7fb2ff', CONMEBOL: '#f2c75c', CONCACAF: '#ff6b6b', CAF: '#5fd38d', AFC: '#ff9f43', OFC: '#c08cff' };
        const CONF_ZH = { UEFA: '欧洲', CONMEBOL: '南美', CONCACAF: '中北美', CAF: '非洲', AFC: '亚洲', OFC: '大洋洲' };
        const byGeo = new Map(story.teams.filter((t) => t.geo).map((t) => [t.geo, t]));
        const byId = new Map(story.teams.map((t) => [t.id, t]));
        const byCountry = new Map(story.teams.map((t) => [t.country, t]));
        const textures = {
          qualified: createMapTexture(features, (f) => (byGeo.has(f.geoKey) ? REACH[byGeo.get(f.geoKey).reach][0] : '#2d4239'), '#0b2240', '#0b224088'),
          night: createMapTexture(features, (f) => (byGeo.has(f.geoKey) ? '#4f7363' : '#2a3d35'), '#081a33', '#6f8a9966'),
        };
        const modeBar = root.querySelector('[data-globe-modes]');
        const legend = root.querySelector('[data-globe-legend]');
        const caption = root.querySelector('[data-globe-caption]');
        const journeyButton = root.querySelector('[data-globe-journey-go]');
        let mode = 'qualified';
        let journeyTeam = byId.get(story.champion) || null;
        let journeyTimers = [];
        const venue = (i) => story.venues[i];
        const teamGames = (team) => story.games.filter((g) => g.h === team.id || g.a === team.id);
        const escapeHtml = (v) => String(v ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const pin = (html, cls = '') => { const el = document.createElement('div'); el.className = `globe-pin ${cls}`; el.innerHTML = html; return el; };
        globe
          .arcStartLat('sLat').arcStartLng('sLng').arcEndLat('eLat').arcEndLng('eLng')
          .arcColor('color').arcStroke('stroke').arcAltitudeAutoScale(0.42)
          .arcDashLength('dash').arcDashGap('gap').arcDashInitialGap('initial').arcDashAnimateTime('time')
          .arcsTransitionDuration(0)
          .pointLat('lat').pointLng('lng').pointColor('color').pointAltitude('alt').pointRadius('radius').pointsMerge(false).pointsTransitionDuration(reduceMotion ? 0 : 900)
          .ringLat('lat').ringLng('lng').ringColor((d) => (t) => `rgba(${d.rgb},${1 - t})`).ringMaxRadius('max').ringPropagationSpeed(2.2).ringRepeatPeriod('period')
          .htmlLat('lat').htmlLng('lng').htmlAltitude('alt').htmlElement('el').htmlTransitionDuration(0);
        const clearLayers = () => { journeyTimers.forEach(clearTimeout); journeyTimers = []; globe.arcsData([]).pointsData([]).ringsData([]).htmlElementsData([]); };
        const setLegend = (items) => { legend.innerHTML = items.map(([c, t]) => `<span><i style="background:${c}"></i>${t}</span>`).join(''); legend.hidden = !items.length; };
        const setCaption = (kicker, title, detail = '') => { caption.innerHTML = `<span>${kicker}</span><strong>${title}</strong>${detail ? `<small>${detail}</small>` : ''}`; };
        const modes = {
          qualified() {
            globe.globeImageUrl(textures.qualified);
            const counts = {}; story.teams.forEach((t) => { counts[t.reach] = (counts[t.reach] || 0) + 1; });
            setLegend(Object.entries(REACH).reverse().map(([k, [c, t]]) => [c, `${t}${counts[k] ? ` · ${counts[k]}` : ''}`]));
            setCaption('参赛版图', `${story.teams.length} 支球队，颜色代表它走到了哪一轮`, '金色是冠军，银色是亚军；点击国家查看球队资料');
            globe.pointOfView({ lat: 22, lng: -20, altitude: 2.25 }, reduceMotion ? 0 : 1200);
            controls.autoRotate = !reduceMotion;
          },
          flights() {
            globe.globeImageUrl(textures.night);
            const arcs = story.teams.map((t) => {
              const g = teamGames(t)[0]; const v = g && venue(g.v); const c = CONF[t.conf] || '#ffffff';
              return v && { sLat: t.lat, sLng: t.lng, eLat: v.lat, eLng: v.lng, color: [`${c}10`, c], stroke: 0.55, dash: 0.45, gap: 1.6, initial: Math.random() * 2, time: 2600 + Math.random() * 1400 };
            }).filter(Boolean);
            globe.arcsData(arcs);
            globe.ringsData(story.venues.map((v) => ({ lat: v.lat, lng: v.lng, rgb: '255,107,107', max: 2.4, period: 1400 })));
            setLegend(Object.entries(CONF).map(([k, c]) => [c, CONF_ZH[k]]));
            setCaption('飞向北美', `${arcs.length} 支球队，从世界各地奔赴 ${story.venues.length} 座城市`, '每条航线连接球队所在国家与它首场比赛的球场');
            globe.pointOfView({ lat: 28, lng: -45, altitude: 2.6 }, reduceMotion ? 0 : 1400);
            controls.autoRotate = false;
          },
          hosts() {
            globe.globeImageUrl(textures.night);
            const max = Math.max(...story.venues.map((v) => v.matches));
            const height = (v) => 0.02 + (v.matches / max) * 0.22;
            globe.pointsData(story.venues.map((v) => ({ lat: v.lat, lng: v.lng, alt: height(v), radius: 0.42, color: v.final ? '#f2c75c' : '#ff5a5f' })));
            globe.ringsData(story.venues.map((v) => ({ lat: v.lat, lng: v.lng, rgb: v.final ? '242,199,92' : '255,90,95', max: v.final ? 4 : 2.6, period: v.final ? 900 : 1600 })));
            globe.htmlElementsData(story.venues.map((v) => ({ lat: v.lat, lng: v.lng, alt: height(v) + 0.02, el: pin(`<b>${escapeHtml(v.city)}</b><span>${v.matches} 场${v.final ? ' · 决赛' : ''}</span>`, v.final ? 'is-final' : '') })));
            setLegend([['#ff5a5f', '比赛场馆（柱高 = 场次）'], ['#f2c75c', '决赛场馆']]);
            setCaption('主办城市', '加拿大 · 墨西哥 · 美国，16 座城市', '柱子越高，这座球场承办的比赛越多');
            globe.pointOfView({ lat: 34, lng: -96, altitude: 1.3 }, reduceMotion ? 0 : 1400);
            controls.autoRotate = false;
          },
          journey() {
            globe.globeImageUrl(textures.night);
            const team = journeyTeam || byId.get(story.champion);
            if (!team) return;
            const games = teamGames(team);
            const stops = [{ lat: team.lat, lng: team.lng }].concat(games.map((g) => venue(g.v)));
            const arcs = stops.slice(1).map((v, i) => ({ sLat: stops[i].lat, sLng: stops[i].lng, eLat: v.lat, eLng: v.lng, color: i === 0 ? ['#ffffff22', '#f2c75c'] : ['#f2c75c', '#ffe7a3'], stroke: i === 0 ? 0.45 : 0.85, dash: 1, gap: 0, initial: 0, time: 0 }));
            arcs.forEach((arc, i) => journeyTimers.push(setTimeout(() => globe.arcsData(arcs.slice(0, i + 1)), reduceMotion ? 0 : 300 + i * 420)));
            const stopsByVenue = new Map();
            games.forEach((g, i) => {
              const v = venue(g.v); const key = `${v.lat},${v.lng}`; const opp = byId.get(g.h === team.id ? g.a : g.h);
              const score = g.hs == null ? '' : (g.h === team.id ? `${g.hs}-${g.as}` : `${g.as}-${g.hs}`);
              const entry = stopsByVenue.get(key) || { lat: v.lat, lng: v.lng, city: v.city, lines: [] };
              entry.lines.push(`<em>${i + 1}</em>${escapeHtml(g.s)} ${score} ${escapeHtml(opp?.code || '')}`);
              stopsByVenue.set(key, entry);
            });
            globe.htmlElementsData([...stopsByVenue.values()].map((s) => ({ lat: s.lat, lng: s.lng, alt: 0.02, el: pin(`<b>${escapeHtml(s.city)}</b>${s.lines.map((l) => `<span>${l}</span>`).join('')}`, 'is-journey') })));
            const last = games[games.length - 1]; const lastVenue = last && venue(last.v);
            if (lastVenue) globe.ringsData([{ lat: lastVenue.lat, lng: lastVenue.lng, rgb: '242,199,92', max: 4.5, period: 1000 }]);
            setLegend([['#f2c75c', '按比赛顺序的航线'], ['#ffffff55', '从本国出发']]);
            setCaption(`${team.id === story.champion ? '冠军之路' : '球队征程'} · ${REACH[team.reach]?.[1] || ''}`, `<img src="${escapeHtml(team.flag)}" alt="">${escapeHtml(team.name)} 的 ${games.length} 场比赛`, '在参赛版图里点击其他国家，再回来查看它的征程');
            globe.pointOfView({ lat: 34, lng: -92, altitude: 1.35 }, reduceMotion ? 0 : 1400);
            controls.autoRotate = false;
          },
        };
        const setMode = (next) => {
          mode = next; clearLayers(); modes[next]();
          modeBar.querySelectorAll('[data-globe-mode]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.globeMode === next)));
          root.dataset.globeMode = next;
          if (journeyButton) journeyButton.hidden = next === 'journey' || !journeyTeam || journeyTeam.id === story.champion && !journeyButton.dataset.touched;
        };
        modeBar.querySelectorAll('[data-globe-mode]').forEach((b) => b.addEventListener('click', () => { setMode(b.dataset.globeMode); pauseAutoRotate(); }));
        // Selecting a country that sent a team makes it the subject of the journey mode.
        root.addEventListener('globe:select', (event) => {
          const team = byGeo.get(event.detail.key) || byCountry.get(String(event.detail.key || '').replace(/^country-/, '').toUpperCase());
          if (!team) return;
          journeyTeam = team;
          if (mode === 'journey') { setMode('journey'); return; }
          if (journeyButton) { journeyButton.dataset.touched = '1'; journeyButton.hidden = false; journeyButton.textContent = `查看${team.name}的征程 →`; }
        });
        journeyButton?.addEventListener('click', () => { setMode('journey'); pauseAutoRotate(); });
        modeBar.hidden = false;
        setMode('qualified');
      }

      featureCount.textContent = `${features.length} 单元`;
      loading.hidden = true;
    })
    .catch((error) => {
      console.error('[globe-3d]', error);
      showUnavailable('真实地图加载失败。你仍可使用下面的国家列表探索内容。');
    });
})();
