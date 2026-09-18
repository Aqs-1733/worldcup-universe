<section class="hero-grid country-hero reveal">
  <div class="hero-panel travel-hero">
    <div class="kicker">COUNTRY & TRAVEL</div>
    <h1><span>世界杯国家</span>文化旅行地图</h1>
    <p>不只看比赛，也看参赛国家、主办城市、语言、货币、地图和旅行文化。数据从 MySQL 读取，国家基础资料通过开源国家数据集同步并结合球队关系展示。</p>
    <div class="hero-actions">
      <a class="primary-button" href="#country-list">探索国家</a>
      <a class="ghost-button" href="#host-cities">主办城市</a>
    </div>
  </div>
  <div class="globe-card" data-country-globe>
    <div class="globe-toolbar">
      <span>Interactive Globe</span>
      <b><?= e((string) count($countries)) ?>/<?= e((string) count($teams)) ?></b>
    </div>
    <div class="earth-stage" data-globe-sphere>
      <div class="earth-orbit orbit-a" aria-hidden="true"></div>
      <div class="earth-orbit orbit-b" aria-hidden="true"></div>
      <svg class="mini-earth" data-globe-map viewBox="0 0 240 240" role="img" aria-label="可旋转的完整地球">
        <defs>
          <radialGradient id="earthOcean" cx="35%" cy="26%" r="72%">
            <stop offset="0" stop-color="#9ee7ff"/>
            <stop offset=".38" stop-color="#2d9df0"/>
            <stop offset=".73" stop-color="#1451a5"/>
            <stop offset="1" stop-color="#071b55"/>
          </radialGradient>
          <linearGradient id="landGrad" x1="0" x2="1" y1="0" y2="1">
            <stop offset="0" stop-color="#d3c47b"/>
            <stop offset=".48" stop-color="#7fba72"/>
            <stop offset="1" stop-color="#3f8d72"/>
          </linearGradient>
          <clipPath id="earthClip"><circle cx="120" cy="120" r="104"/></clipPath>
        </defs>
        <circle class="earth-shadow" cx="120" cy="120" r="106"/>
        <circle class="earth-ocean" cx="120" cy="120" r="104" fill="url(#earthOcean)"/>
        <g clip-path="url(#earthClip)">
          <g class="earth-grid">
            <path d="M16 120H224"/>
            <path d="M28 84H212"/>
            <path d="M28 156H212"/>
            <ellipse cx="120" cy="120" rx="104" ry="38"/>
            <ellipse cx="120" cy="120" rx="70" ry="104"/>
            <ellipse cx="120" cy="120" rx="36" ry="104"/>
          </g>
          <g class="earth-continents">
            <path d="M44 73c16-21 49-30 75-19 18 7 27 23 18 36-9 12-31 7-43 17-10 8-9 22-24 24-19 2-25-16-41-20-15-4-25-2-30-13-4-10 26-12 45-25Z"/>
            <path d="M91 121c20 8 31 27 25 49-3 14-11 24-17 38-14-8-24-28-24-51 0-17 5-30 16-36Z"/>
            <path d="M126 72c15-11 37-10 51 0 12 9 10 22-4 27-19 6-40-3-47-27Z"/>
            <path d="M137 100c25-5 47 10 50 36 2 25-12 47-33 59-19-18-29-42-26-66 1-12 4-22 9-29Z"/>
            <path d="M165 69c35-24 83-16 101 10 10 15 1 33-16 34-18 1-28-11-44-8-17 3-31 20-47 9-15-11-12-32 6-45Z"/>
            <path d="M180 164c20-8 44-4 56 10 7 9 1 19-12 19-22 0-39-10-44-29Z"/>
          </g>
          <g class="earth-clouds">
            <path d="M22 65c28-16 62-17 96-5"/>
            <path d="M126 50c30-11 62-9 91 7"/>
            <path d="M54 179c32 13 70 15 111 4"/>
            <path d="M142 141c25 6 51 5 78-3"/>
          </g>
        </g>
        <circle class="earth-rim" cx="120" cy="120" r="104"/>
        <circle class="earth-highlight" cx="88" cy="76" r="34"/>
      </svg>
      <div class="globe-active-marker" data-globe-active-marker hidden><span></span></div>
    </div>
    <div class="globe-readout" data-globe-readout>
      <span>点击下面国家卡片</span>
      <strong>完整地球定位</strong>
      <small>默认无定位点；选中国家后只显示一个红色位置点。</small>
    </div>
  </div>
</section>

<section id="host-cities" class="panel reveal">
  <div class="section-head">
    <div><span class="kicker">HOST CITIES</span><h2>2026 主办城市和场馆</h2></div>
    <span class="source-chip">MySQL venues</span>
  </div>
  <div class="travel-grid">
    <?php foreach (array_slice($venues, 0, 18) as $venue): ?>
      <article class="travel-card host-card">
        <div class="travel-top"><span><?= e($venue['country'] ?: 'Host') ?></span><b><?= e($venue['city'] ?: 'City') ?></b></div>
        <h3><?= e($venue['name']) ?></h3>
        <p><?= e(($venue['capacity'] ?? null) ? ('容量约 ' . number_format((int) $venue['capacity']) . ' 人') : '场馆信息来自导入数据，可在后台补充容量。') ?></p>
      </article>
    <?php endforeach; ?>
    <?php if (!$venues): ?><div class="data-empty">导入赛事数据后显示主办城市和场馆。</div><?php endif; ?>
  </div>
</section>

<section id="country-list" class="panel reveal">
  <div class="section-head">
    <div><span class="kicker">COUNTRIES</span><h2>参赛国家探索</h2></div>
    <span class="source-chip"><?= e((string) count($countries)) ?> 个国家资料</span>
  </div>
  <div class="country-grid">
    <?php foreach ($countries as $country): ?>
      <?php $flag = $country['team_flag_url'] ?: ('https://flagcdn.com/w80/' . strtolower((string) $country['country_code']) . '.png'); ?>
      <?php $countryName = display_name($country['country_name_cn'], $country['country_name_original']); ?>
      <article class="country-card" tabindex="0" role="button"
        data-country-card
        data-code="<?= e($country['country_code']) ?>"
        data-name="<?= e($countryName) ?>"
        data-capital="<?= e($country['capital'] ?: '待同步') ?>"
        data-region="<?= e($country['region'] ?: '地区待同步') ?>"
        data-flag="<?= e($flag) ?>"
        data-lat="<?= e((string) ($country['latitude'] ?? '')) ?>"
        data-lng="<?= e((string) ($country['longitude'] ?? '')) ?>">
        <div class="country-card-head">
          <?= flag_html($flag, null, (string) $country['country_name_cn']) ?>
          <div>
            <h3><?= e($countryName) ?></h3>
            <small><?= e($country['region'] ?: '地区待同步') ?><?= $country['subregion'] ? ' · ' . e($country['subregion']) : '' ?></small>
          </div>
        </div>
        <div class="country-facts">
          <span>首都：<?= e($country['capital'] ?: '待同步') ?></span>
          <span>语言：<?= e($country['languages'] ?: '待同步') ?></span>
          <span>货币：<?= e($country['currencies'] ?: '待同步') ?></span>
        </div>
        <p><?= e($country['travel_summary'] ?: '运行国家资料同步脚本后显示旅行摘要。') ?></p>
        <p class="muted-line"><?= e($country['culture_summary'] ?: '文化摘要待同步。') ?></p>
        <div class="meta-row">
          <span>球队：<?= e($country['team_names'] ?: '待关联') ?></span>
          <?php if ($country['latitude'] !== null && $country['longitude'] !== null): ?><span>坐标：<?= e(number_format((float) $country['latitude'], 2)) ?>°, <?= e(number_format((float) $country['longitude'], 2)) ?>°</span><?php endif; ?>
          <?php if (!empty($country['map_url'])): ?><a class="mini-link" href="<?= e($country['map_url']) ?>" target="_blank" rel="noreferrer">地图</a><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$countries): ?>
      <div class="data-empty">还没有国家资料。运行 <code>php scripts/sync_country_profiles.php</code> 后显示。</div>
    <?php endif; ?>
  </div>
</section>
