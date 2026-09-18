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
    <div class="globe-sphere globe-sphere-clean" data-globe-sphere>
      <div class="globe-map" data-globe-map aria-hidden="true">
        <svg viewBox="0 0 360 180" role="img" aria-label="世界大洲轮廓">
          <g class="continent-layer">
            <path class="globe-continent north-america" d="M36 45c19-22 55-26 83-14 15 6 27 18 24 30-4 14-23 10-32 20-10 10-5 26-20 29-18 4-27-13-41-17-15-5-35 2-42-10-7-13 12-26 28-38Z"/>
            <path class="globe-continent south-america" d="M116 94c16 8 24 23 21 40-2 14-11 22-18 34-5 9-2 22-13 27-10-12-18-28-19-45-2-24 8-44 29-56Z"/>
            <path class="globe-continent europe" d="M169 46c18-13 44-12 59 1 12 10 5 23-10 25-18 3-44-2-49-26Z"/>
            <path class="globe-continent africa" d="M191 77c22-5 45 7 50 31 5 23-8 44-26 60-17-14-30-37-31-61 0-12 2-22 7-30Z"/>
            <path class="globe-continent asia" d="M231 45c40-21 91-12 114 16 11 14 4 30-13 33-17 4-27-9-43-7-18 2-29 20-47 13-19-8-29-34-11-55Z"/>
            <path class="globe-continent oceania" d="M293 124c19-8 41-4 53 9 9 10 4 20-10 20-20 0-39-8-43-29Z"/>
          </g>
          <g class="continent-layer duplicate" transform="translate(360 0)">
            <path class="globe-continent north-america" d="M36 45c19-22 55-26 83-14 15 6 27 18 24 30-4 14-23 10-32 20-10 10-5 26-20 29-18 4-27-13-41-17-15-5-35 2-42-10-7-13 12-26 28-38Z"/>
            <path class="globe-continent south-america" d="M116 94c16 8 24 23 21 40-2 14-11 22-18 34-5 9-2 22-13 27-10-12-18-28-19-45-2-24 8-44 29-56Z"/>
            <path class="globe-continent europe" d="M169 46c18-13 44-12 59 1 12 10 5 23-10 25-18 3-44-2-49-26Z"/>
            <path class="globe-continent africa" d="M191 77c22-5 45 7 50 31 5 23-8 44-26 60-17-14-30-37-31-61 0-12 2-22 7-30Z"/>
            <path class="globe-continent asia" d="M231 45c40-21 91-12 114 16 11 14 4 30-13 33-17 4-27-9-43-7-18 2-29 20-47 13-19-8-29-34-11-55Z"/>
            <path class="globe-continent oceania" d="M293 124c19-8 41-4 53 9 9 10 4 20-10 20-20 0-39-8-43-29Z"/>
          </g>
        </svg>
      </div>
      <div class="globe-active-marker" data-globe-active-marker hidden></div>
      <div class="globe-shine"></div>
    </div>
    <div class="globe-readout" data-globe-readout>
      <span>点击下面国家卡片</span>
      <strong>定位球队国家</strong>
      <small>地球会自动转到对应位置</small>
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
