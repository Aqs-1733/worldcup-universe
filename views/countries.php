<?php /** Compact country cover; the globe remains the interactive centrepiece. */ ?>
<?= $this->render('/catalog/_masthead',['heading'=>'国家与主办城市','photo'=>'vancouver','photoLabel'=>'温哥华城市全景','class'=>'countries-cover']) ?>
<section class="hero-grid country-hero reveal">
  <div class="hero-panel travel-hero">



    <div class="hero-actions">
      <a class="primary-button" href="#country-list">探索国家</a>
      <a class="ghost-button" href="#host-cities">主办城市</a>
    </div>
  </div>
  <div class="globe-card globe-card-3d" data-globe-3d>
    <div class="globe-toolbar">
      <span>交互式地球</span>
      <b data-globe-feature-count>加载中</b>
    </div>
    <div class="globe-stage" data-globe-stage aria-label="可旋转的世界地图">
      <div class="globe-canvas" data-globe-canvas></div>
      <div class="globe-loading" data-globe-loading>地球加载中…</div>
      <div class="globe-unavailable" data-globe-unavailable hidden>
        此设备无法启动 3D 地球。你仍可使用下面的国家列表探索内容。
      </div>
    </div>
    <div class="globe-continent-controls" aria-label="大洲聚焦">
      <button type="button" data-globe-reset>重置视角</button>
      <button type="button" data-globe-continent="North America">北美洲</button>
      <button type="button" data-globe-continent="South America">南美洲</button>
      <button type="button" data-globe-continent="Europe">欧洲</button>
      <button type="button" data-globe-continent="Africa">非洲</button>
      <button type="button" data-globe-continent="Asia">亚洲</button>
      <button type="button" data-globe-continent="Oceania">大洋洲</button>
    </div>
    <div class="globe-readout globe-readout-3d" data-globe-3d-readout aria-live="polite">
      <span data-globe-readout-kicker>国家探索</span>
      <strong data-globe-readout-title>旋转地球，选择国家</strong>
      <small data-globe-readout-detail></small>
    </div>
  </div>
</section>

<?php $library=json_decode(file_get_contents(ROOT_PATH.'/public/assets/media/photos/library.json'),true); ?>
<section id="host-cities" class="section"><div class="section-head"><h2>主办城市与球场</h2><a href="/credits">摄影来源 ↗</a></div><div class="venue-gallery"><?php foreach($library as $photo): ?><article class="thumb"><button class="image" type="button" data-gallery-image="<?= e($photo['path']) ?>" data-photo-title="<?= e($photo['label']) ?>" data-photo-source="<?= e(safe_url($photo['source_url'])) ?>" aria-label="查看<?= e($photo['label']) ?>照片"><img src="<?= e(is_file(ROOT_PATH.'/public/assets/media/photos/display/'.$photo['key'].'.jpg')?'/assets/media/photos/display/'.$photo['key'].'.jpg':$photo['path']) ?>" alt="<?= e($photo['label']) ?>" loading="lazy"></button><h3><?= e($photo['label']) ?></h3></article><?php endforeach ?></div><details class="venue-directory"><summary>全部 16 座比赛场馆</summary><div class="datagrid"><?php foreach($venues as $v): ?><div class="datagrid-item"><div class="datagrid-title"><?= e($v['city']) ?></div><div class="datagrid-content"><?= e($v['name']) ?></div></div><?php endforeach ?></div></details></section>
<dialog class="photo-dialog" data-photo-dialog><button class="photo-close" data-photo-close type="button" aria-label="关闭照片">×</button><img alt=""><div class="photo-caption"><h2></h2><a href="#" target="_blank" rel="noopener">原图与摄影署名 ↗</a></div></dialog>
<section id="country-list" class="panel reveal">
  <div class="section-head">
    <div><span class="kicker">国家资料</span><h2>参赛国家探索</h2></div>
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


        <div class="meta-row">
          <span class="country-teams"><?php foreach($country['linked_teams'] as $ct): ?><a href="/teams/<?= e($ct['slug']) ?>"><?= \app\services\TeamUi::label(['code'=>$ct['code'],'name'=>$ct['name_cn']?:$ct['name_original'],'flag_url'=>$ct['flag_url']]) ?></a><?php endforeach ?></span>
          <?php if ($country['latitude'] !== null && $country['longitude'] !== null): ?><span>坐标：<?= e(number_format((float) $country['latitude'], 2)) ?>°, <?= e(number_format((float) $country['longitude'], 2)) ?>°</span><?php endif; ?>
          <?php if (!empty($country['map_url'])): ?><a class="mini-link" href="<?= e(safe_url($country['map_url'])) ?>" target="_blank" rel="noreferrer">地图</a><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$countries): ?>
      <div class="data-empty">暂无国家资料。</div>
    <?php endif; ?>
  </div>
</section>
