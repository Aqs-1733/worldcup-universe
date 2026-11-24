// 文件作用：Node 测试：3D 地球地图数据的坐标和颜色。
// 作者：崔迪_2412439
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const mapPath = new URL('../public/assets/data/globe/natural-earth-admin0-map-units-50m.geojson', import.meta.url);
const webMapPath = new URL('../public/assets/data/globe/natural-earth-admin0-map-units-50m.web.geojson', import.meta.url);
const renderMapPath = new URL('../public/assets/data/globe/natural-earth-admin0-map-units-110m.web.geojson', import.meta.url);
const globeScriptPath = new URL('../public/assets/js/globe-3d.js', import.meta.url);

const coordinateCount = (coordinates) => coordinates.reduce(
  (count, value) => count + (Array.isArray(value[0]) ? coordinateCount(value) : 1),
  0,
);

const containsPoint = (geometry, [longitude, latitude]) => {
  const inRing = (ring) => {
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
      const [x, y] = ring[i];
      const [previousX, previousY] = ring[j];
      if ((y > latitude) !== (previousY > latitude)
        && longitude < (previousX - x) * (latitude - y) / (previousY - y) + x) inside = !inside;
    }
    return inside;
  };
  const polygons = geometry.type === 'Polygon' ? [geometry.coordinates] : geometry.coordinates;
  return polygons.some(([outer, ...holes]) => inRing(outer) && !holes.some(inRing));
};

test('all map datasets group the mainland and Taiwan into one China feature', async () => {
  for (const path of [mapPath, webMapPath, renderMapPath]) {
    const map = JSON.parse(await readFile(path, 'utf8'));
    const china = map.features.filter((feature) => feature.properties.ISO_A3 === 'CHN');
    assert.equal(china.length, 1, `${path}: expected one China feature`);
    assert.ok(!map.features.some((feature) => feature.properties.ISO_A3 === 'TWN'
      || feature.properties.NE_ID === 1159321335), `${path}: unexpected separate feature`);
    assert.equal(china[0].properties.NAME_ZH, '中国');
    assert.ok(containsPoint(china[0].geometry, [116.4074, 39.9042]), `${path}: Beijing must remain in China`);
    assert.ok(containsPoint(china[0].geometry, [121.5654, 25.0330]), `${path}: Taipei must be in the same China feature`);
  }
});

test('local Natural Earth map units provide clickable real-world country features', async () => {
  const source = await readFile(mapPath, 'utf8');
  const map = JSON.parse(source);

  assert.equal(map.type, 'FeatureCollection');
  assert.ok(map.features.length >= 200, 'expected a complete world map, not a demo subset');
  assert.ok(map.features.every((feature) => ['Polygon', 'MultiPolygon'].includes(feature.geometry?.type)));
  assert.ok(map.features.every((feature) => feature.properties?.NE_ID));
  assert.ok(map.features.every((feature) => feature.properties?.NAME));
  assert.ok(map.features.some((feature) => feature.geometry.type === 'MultiPolygon'));
});

test('web map preserves the local map units while reducing polygon complexity', async () => {
  const [source, webSource] = await Promise.all([
    readFile(mapPath, 'utf8').then(JSON.parse),
    readFile(webMapPath, 'utf8').then(JSON.parse),
  ]);

  assert.equal(webSource.features.length, source.features.length);
  assert.deepEqual(
    webSource.features.map((feature) => feature.properties.NE_ID).sort(),
    source.features.map((feature) => feature.properties.NE_ID).sort(),
  );
  assert.ok(
    coordinateCount(webSource.features.map((feature) => feature.geometry.coordinates)) < coordinateCount(source.features.map((feature) => feature.geometry.coordinates)),
    'expected a simpler local rendering geometry',
  );
});

test('rendering map uses complete unsimplified 110m Natural Earth map units', async () => {
  const source = await readFile(renderMapPath, 'utf8').then(JSON.parse);

  assert.equal(source.type, 'FeatureCollection');
  assert.ok(source.features.length >= 180);
  assert.ok(source.features.every((feature) => ['Polygon', 'MultiPolygon'].includes(feature.geometry?.type)));
  assert.ok(source.features.every((feature) => feature.properties?.NE_ID));
  assert.ok(source.features.every((feature) => feature.properties?.CONTINENT));
});

test('country hover keeps map fills stable and uses ID-keyed outline paths', async () => {
  const script = await readFile(globeScriptPath, 'utf8');

  assert.match(script, /natural-earth-admin0-map-units-110m\.web\.geojson/);
  assert.match(script, /const normalizeGeometry/);
  assert.match(script, /179\.999/);
  assert.match(script, /const mapFeatureId = `ne-\$\{feature\.properties\.NE_ID\}`/);
  assert.match(script, /__id: mapFeatureId/);
  assert.match(script, /pointerEventsFilter\(/);
  assert.match(script, /__globeObjType === 'globe'/);
  assert.match(script, /const findFeatureAt/);
  assert.match(script, /const geometryArea/);
  assert.match(script, /mapArea: geometryArea\(geometry\)/);
  assert.match(script, /feature\.mapArea < match\.mapArea/);
  assert.match(script, /globe\.toGlobeCoords/);
  assert.match(script, /stage\.addEventListener\('click'/);
  assert.match(script, /canvasHost\.contains\(event\.target\)/);
  assert.match(script, /canvasHost\.querySelector\('canvas'\)/);
  assert.match(script, /if \(!pointerOrigin\) pauseAutoRotate\(\)/);
  assert.match(script, /lastDragAt/);
  assert.match(script, /const normalLandColor = '#7FB07A'/);
  assert.match(script, /const oceanColor = '#2E7BD1'/);
  assert.match(script, /const createMapTexture/);
  assert.match(script, /context\.fill\('evenodd'\)/);
  assert.match(script, /const hoverOutlineColor = '#F4C95D'/);
  assert.match(script, /const selectedOutlineColor = '#FFD86B'/);
  assert.match(script, /let selectedId = null/);
  assert.match(script, /let hoveredId = null/);
  assert.match(script, /geoKey: mapFeatureId/);
  assert.match(script, /const buildOutlinePaths/);
  assert.match(script, /const renderFeatureOutlines/);
  assert.match(script, /\.pathsData\(outlinePaths\)/);
  assert.match(script, /\.globeImageUrl\(mapTexture\)/);
  assert.match(script, /\.polygonsData\(\[\]\)/);
  assert.doesNotMatch(script, /polygonCapColor\(countryCapColor\)/);
  assert.doesNotMatch(script, /polygonAltitude\(countryAltitude\)/);
  assert.doesNotMatch(script, /polygonCapColor\(\(\) => normalLandColor\)/);
  assert.doesNotMatch(script, /\.onGlobeClick\(/);
  assert.doesNotMatch(script, /const applyFeatureStates/);
  assert.doesNotMatch(script, /onPolygonHover[\s\S]*?globe\.polygonsData\(features\)/);
  assert.match(script, /polygonsTransitionDuration\(0\)/);
  assert.doesNotMatch(script, /const applyPolygonStyles/);
});
