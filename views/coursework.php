<section class="page-title reveal">
  <div>
    <span class="kicker">COURSEWORK</span>
    <h1>课程交付总览</h1>
    <p>按老师给出的个人 1/2/3 阶段和团队 1-6 作业留存要求整理，项目本体仍是 PHP + MySQL 世界杯系统。</p>
  </div>
  <?php if (\App\Core\Auth::isAdmin()): ?>
    <a class="ghost-button" href="<?= e(url('/admin/coursework_artifacts')) ?>">后台维护清单</a>
  <?php endif; ?>
</section>

<section class="panel reveal">
  <div class="section-head">
    <div><span class="kicker">TEACHER REQUIREMENTS</span><h2>老师要求映射</h2></div>
    <span class="source-chip"><?= e(($counts['coursework_artifacts'] ?? 0) . ' 项留存') ?></span>
  </div>
  <div class="module-grid compact-modules">
    <article class="module-card static-card"><span>个人作业1</span><small>GET/POST 请求截图、JQuery 事件、浏览器插件。</small></article>
    <article class="module-card static-card"><span>个人作业2</span><small>Axure 或同类原型、前台/后台模板选型。</small></article>
    <article class="module-card static-card"><span>个人作业3</span><small>WordPress 安装与页面改造步骤、最终截图。</small></article>
    <article class="module-card static-card"><span>团队作业</span><small>需求、设计、实现、用户手册、部署文档、项目展示。</small></article>
  </div>
</section>

<?php foreach ($artifacts as $stage => $items): ?>
<section class="panel reveal">
  <div class="section-head">
    <div><span class="kicker"><?= e(strtoupper($stage)) ?></span><h2><?= e($stage) ?></h2></div>
  </div>
  <div class="timeline-list">
    <?php foreach ($items as $item): ?>
      <article class="timeline-card status-<?= e($item['status']) ?>">
        <div class="timeline-top">
          <span class="status-pill"><?= e($item['status']) ?></span>
          <strong><?= e($item['title']) ?></strong>
        </div>
        <p><?= e($item['requirement_summary']) ?></p>
        <div class="meta-row">
          <span>证据路径：<?= e($item['evidence_path'] ?: '待补') ?></span>
          <span><?= e($item['notes'] ?: '') ?></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<section class="panel reveal">
  <div class="section-head"><div><span class="kicker">PROJECT BODY</span><h2>项目本体完成情况</h2></div></div>
  <div class="quality-grid">
    <div class="quality-chip pass"><strong>PHP + MySQL</strong><span>无 SQLite，入口 public/index.php</span></div>
    <div class="quality-chip pass"><strong>10+ 数据表</strong><span><?= e((string) count($counts)) ?> 类计数，schema 内含 30+ 表</span></div>
    <div class="quality-chip pass"><strong>前台展示</strong><span>首页、赛程、球队、球员、新闻、留言、AI</span></div>
    <div class="quality-chip pass"><strong>后台管理</strong><span>团队、发布、球队、球员、赛程、新闻、留言</span></div>
    <div class="quality-chip pass"><strong>动态图形</strong><span>积分条形图、世界杯晋级图、滚动新闻动效</span></div>
    <div class="quality-chip pass"><strong>真实数据</strong><span>数据导入与校验结果写入 MySQL</span></div>
  </div>
</section>
