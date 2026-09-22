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
      readout.kicker.textContent = 'REAL MAP UNITS';
      readout.title.textContent = '拖动地球探索真实国家轮廓';
      readout.detail.textContent = '悬停高亮国家；点击后显示 Natural Earth 的稳定地图单元 ID。';
      return;
    }
    const properties = feature.properties;
    const name = properties.NAME_ZH || properties.NAME_EN || properties.NAME;
    const iso = properties.ISO_A3 && properties.ISO_A3 !== '-99' ? ` · ISO ${properties.ISO_A3}` : '';
    readout.kicker.textContent = `MAP UNIT · ${properties.CONTINENT || '未知大洲'}`;
    readout.title.textContent = name;
    readout.detail.textContent = `NE_ID ${properties.NE_ID}${iso} · ${properties.NAME || name}`;
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
      const createMapTexture = (mapFeatures) => {
        const width = window.innerWidth <= 720 ? 1024 : 1536;
        const height = width / 2;
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const context = canvas.getContext('2d');
        context.fillStyle = oceanColor;
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
          context.fillStyle = normalLandColor;
          context.fill('evenodd');
          context.strokeStyle = normalBorderColor;
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
          tooltip.textContent = `${properties.NAME_ZH || properties.NAME_EN || properties.NAME} · ${properties.NE_ID}`;
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
      ['pointerup', 'pointercancel'].forEach((eventName) => stage.addEventListener(eventName, () => { pointerOrigin = null; }, { passive: true }));
      ['touchstart', 'wheel'].forEach((eventName) => stage.addEventListener(eventName, pauseAutoRotate, { passive: true }));
      new ResizeObserver(refreshSize).observe(stage);

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

      featureCount.textContent = `${features.length} 单元`;
      loading.hidden = true;
    })
    .catch((error) => {
      console.error('[globe-3d]', error);
      showUnavailable('真实地图加载失败。你仍可使用下面的国家列表探索内容。');
    });
})();
