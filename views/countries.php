<?php /** Compact country cover and navigable directory. */ $this->title='国家与主办城市'; ?>
<?= $this->render('/catalog/_masthead',['heading'=>'国家与主办城市','photo'=>'vancouver','photoLabel'=>'温哥华城市全景','class'=>'countries-cover']) ?>
<header class="edition-page-intro"><div><span class="edition-kicker">A WORLD OF FOOTBALL</span><h2>转动世界，找到你的主队。</h2></div><a href="#country-list">浏览国家目录 ↓</a></header>
<script type="application/json" data-globe-story><?= yii\helpers\Json::htmlEncode((new app\services\GlobeStoryService())->payload()) ?></script>
<section class="hero-grid country-hero reveal edition-explorer">
  <div class="globe-card globe-card-3d" data-globe-3d>
    <div class="globe-toolbar">
      <span>交互式地球</span>
      <b data-globe-feature-count>加载中</b>
    </div>
    <div class="globe-modes" data-globe-modes role="toolbar" aria-label="地球故事模式" hidden>
      <button type="button" data-globe-mode="qualified" aria-pressed="true"><i>01</i>参赛版图</button>
      <button type="button" data-globe-mode="flights" aria-pressed="false"><i>02</i>飞向北美</button>
      <button type="button" data-globe-mode="hosts" aria-pressed="false"><i>03</i>主办城市</button>
      <button type="button" data-globe-mode="journey" aria-pressed="false"><i>04</i>冠军之路</button>
    </div>
    <div class="globe-caption" data-globe-caption aria-live="polite"></div>
    <div class="globe-legend" data-globe-legend hidden></div>
    <button type="button" class="globe-journey-go" data-globe-journey-go hidden></button>
    <div class="globe-stage" data-globe-stage data-lenis-prevent aria-label="可旋转的世界地图">
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
    <div class="edition-globe-zoom" aria-label="地球缩放"><button type="button" data-globe-zoom="in" aria-label="放大地球">＋</button><button type="button" data-globe-zoom="out" aria-label="缩小地球">−</button></div>
    <div class="globe-readout globe-readout-3d" data-globe-3d-readout aria-live="polite">
      <span data-globe-readout-kicker>国家探索</span>
      <strong data-globe-readout-title>旋转地球，选择国家</strong>
      <small data-globe-readout-detail></small>
    </div>
  </div>
  <aside class="edition-country-panel" data-country-panel>
   <label class="edition-country-picker">选择国家队<select data-globe-country-picker><option value="">点击地球或选择国家</option><?php $geoMap=array_flip(require ROOT_PATH.'/config/globe-country-map.php');foreach($countries as $country): ?><option value="<?= e($country['country_code']) ?>" data-geo-key="<?= e($geoMap[$country['country_code']]??'') ?>" data-lat="<?= e($country['latitude']) ?>" data-lng="<?= e($country['longitude']) ?>"><?= e(app\services\CountryUi::name($country)) ?></option><?php endforeach ?></select></label>
   <div data-country-panel-content aria-live="polite"><span class="edition-kicker">EXPLORE THE WORLD</span><h2>一个世界。<br>不同的热爱。</h2><p class="edition-country-message">拖动地球，点击一个国家；或者从上方选择国家队。</p><div class="edition-world-legend"><span>金色轮廓</span><p>你正在关注的国家</p></div></div>
  </aside>
</section>

<?php $library=json_decode(file_get_contents(ROOT_PATH.'/public/assets/media/photos/library.json'),true); ?>
<section id="host-cities" class="section"><div class="section-head"><h2>主办城市与球场</h2><a href="/credits">摄影来源 ↗</a></div><div class="venue-gallery"><?php foreach($library as $photo): ?><article class="thumb"><button class="image" type="button" data-gallery-image="<?= e($photo['path']) ?>" data-photo-title="<?= e($photo['label']) ?>" data-photo-source="<?= e(safe_url($photo['source_url'])) ?>" aria-label="查看<?= e($photo['label']) ?>照片"><img src="<?= e(is_file(ROOT_PATH.'/public/assets/media/photos/display/'.$photo['key'].'.jpg')?'/assets/media/photos/display/'.$photo['key'].'.jpg':$photo['path']) ?>" alt="<?= e($photo['label']) ?>" loading="lazy"></button><h3><?= e($photo['label']) ?></h3></article><?php endforeach ?></div><details class="venue-directory"><summary>全部 16 座比赛场馆</summary><div class="datagrid"><?php foreach($venues as $v): ?><div class="datagrid-item"><div class="datagrid-title"><?= e($v['city']) ?></div><div class="datagrid-content"><?= e($v['name']) ?></div></div><?php endforeach ?></div></details></section>
<dialog class="photo-dialog" data-photo-dialog><button class="photo-close" data-photo-close type="button" aria-label="关闭照片">×</button><img alt=""><div class="photo-caption"><h2></h2><a href="#" target="_blank" rel="noopener">原图与摄影署名 ↗</a></div></dialog>
<section id="country-list" class="section country-directory" data-country-directory><div class="section-head"><h2>参赛国家</h2><span data-country-counter><?= count($countries) ?> 个国家</span></div><div class="country-directory-tools"><nav aria-label="按大洲筛选"><?php foreach([''=>'全部国家','Europe'=>'欧洲','Asia'=>'亚洲','Africa'=>'非洲','Americas'=>'美洲','Oceania'=>'大洋洲'] as $region=>$label): ?><button type="button" data-country-region="<?= e($region) ?>" aria-pressed="<?= $region===''?'true':'false' ?>"><?= e($label) ?></button><?php endforeach ?></nav><label><span class="visually-hidden">搜索国家</span><input type="search" data-country-search placeholder="搜索国家或首都"></label></div><div class="country-tile-grid"><?php foreach($countries as $country): ?><?php $name=app\services\CountryUi::name($country);$capital=app\services\CountryUi::capital($country); ?><a class="country-tile" href="/countries/<?= e(strtolower($country['country_code'])) ?>" data-region="<?= e($country['region']) ?>" data-search="<?= e($name.' '.$country['country_name_original'].' '.$capital.' '.$country['capital']) ?>"><div class="country-tile-top"><?= app\services\CountryUi::flag($country) ?><span><?= e(app\services\CountryUi::region($country)) ?></span></div><h3><?= e($name) ?></h3><p><?= e($country['country_name_original']) ?></p><div class="country-tile-bottom"><span>首都 <b><?= e($capital) ?></b></span><span class="country-open" aria-hidden="true">↗</span></div></a><?php endforeach ?></div><div class="community-empty" data-country-empty hidden>没有符合条件的国家。</div></section>
