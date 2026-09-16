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
  <div class="globe-card" aria-hidden="true">
    <div class="globe-orbit orbit-one"></div>
    <div class="globe-orbit orbit-two"></div>
    <div class="globe-core">🌎</div>
    <span>Teams</span>
    <strong><?= e((string) count($teams)) ?></strong>
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
      <?php $flag = 'https://flagcdn.com/w80/' . strtolower((string) $country['country_code']) . '.png'; ?>
      <article class="country-card">
        <div class="country-card-head">
          <?= flag_html($flag, null, (string) $country['country_name_cn']) ?>
          <div>
            <h3><?= e(display_name($country['country_name_cn'], $country['country_name_original'])) ?></h3>
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
          <?php if (!empty($country['map_url'])): ?><a class="mini-link" href="<?= e($country['map_url']) ?>" target="_blank" rel="noreferrer">地图</a><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$countries): ?>
      <div class="data-empty">还没有国家资料。运行 <code>php scripts/sync_country_profiles.php</code> 后显示。</div>
    <?php endif; ?>
  </div>
</section>

