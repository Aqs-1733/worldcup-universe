<section class="page-title reveal">
  <div><span class="kicker">SEARCH</span><h1>全站智能搜索</h1><p>搜索球队、球员、比赛、新闻和国家旅行信息；可勾选智能总结辅助理解结果。</p></div>
</section>

<section class="panel reveal">
  <form class="search-bar" method="get" action="<?= e(url('/search')) ?>">
    <input name="q" value="<?= e($q) ?>" placeholder="搜索球队、球员、比赛、新闻、国家或城市">
    <label class="check-line inline-check"><input type="checkbox" name="smart" value="1" <?= $smart ? 'checked' : '' ?>> 使用智能总结</label>
    <button class="primary-button" type="submit">搜索</button>
  </form>
  <?php if (!empty($summary)): ?><div class="commentary-box"><?= nl2br(e($summary)) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert-box"><?= e($error) ?></div><?php endif; ?>
</section>

<?php if ($q !== ''): ?>
<section class="dashboard-grid search-results reveal">
  <div class="panel">
    <div class="section-head"><div><span class="kicker">TEAMS</span><h2>球队</h2></div></div>
    <div class="chip-grid">
      <?php foreach ($results['teams'] as $team): ?><a class="team-chip" href="<?= e(url('/teams/' . $team['slug'])) ?>"><?= team_name_html($team['flag_url'] ?? null, $team['flag_emoji'] ?? null, $team['name_cn'], $team['name_original']) ?></a><?php endforeach; ?>
      <?php if (!$results['teams']): ?><div class="data-empty">没有球队命中。</div><?php endif; ?>
    </div>
  </div>
  <div class="panel">
    <div class="section-head"><div><span class="kicker">PLAYERS</span><h2>球员</h2></div></div>
    <div class="player-stack">
      <?php foreach ($results['players'] as $player): ?><a class="player-row" href="<?= e(url('/players/' . $player['slug'])) ?>"><?= flag_html($player['flag_url'] ?? null, $player['flag_emoji'] ?? null, '') ?><b><?= e(display_name($player['name_cn'], $player['name_original'])) ?></b><small><?= e($player['team_name_cn'] ?? '') ?> · <?= e($player['club'] ?? '') ?></small></a><?php endforeach; ?>
      <?php if (!$results['players']): ?><div class="data-empty">没有球员命中。</div><?php endif; ?>
    </div>
  </div>
</section>

<section class="dashboard-grid search-results reveal">
  <div class="panel">
    <div class="section-head"><div><span class="kicker">MATCHES</span><h2>比赛</h2></div></div>
    <div class="match-list">
      <?php foreach ($results['matches'] as $match): ?><a class="match-row" href="<?= e(url('/matches/' . $match['id'])) ?>"><time><?= e(date_label($match['starts_at'])) ?></time><div class="versus"><span><?= team_name_html($match['home_flag_url'] ?? null, $match['home_flag'] ?? null, $match['home_name_cn'] ?? null, $match['home_team_name'] ?? null) ?></span><b><?= $match['home_score'] === null ? 'vs' : e($match['home_score'] . ':' . $match['away_score']) ?></b><span><?= team_name_html($match['away_flag_url'] ?? null, $match['away_flag'] ?? null, $match['away_name_cn'] ?? null, $match['away_team_name'] ?? null) ?></span></div><small><?= e($match['stage']) ?> <?= e($match['group_name'] ? '· ' . $match['group_name'] . '组' : '') ?></small></a><?php endforeach; ?>
      <?php if (!$results['matches']): ?><div class="data-empty">没有比赛命中。</div><?php endif; ?>
    </div>
  </div>
  <div class="panel">
    <div class="section-head"><div><span class="kicker">COUNTRIES</span><h2>国家探索</h2></div></div>
    <div class="country-grid mini-country-grid">
      <?php foreach ($results['countries'] as $country): ?><?php $flag = $country['team_flag_url'] ?: ('https://flagcdn.com/w80/' . strtolower((string) $country['country_code']) . '.png'); ?><article class="country-card"><div class="country-card-head"><?= flag_html($flag, null, '') ?><div><h3><?= e(display_name($country['country_name_cn'], $country['country_name_original'])) ?></h3><small><?= e($country['capital'] ?: '首都待同步') ?></small></div></div><p><?= e(mb_strimwidth((string) $country['travel_summary'], 0, 120, '...')) ?></p></article><?php endforeach; ?>
      <?php if (!$results['countries']): ?><div class="data-empty">没有国家资料命中。</div><?php endif; ?>
    </div>
  </div>
</section>

<section class="panel reveal">
  <div class="section-head"><div><span class="kicker">NEWS</span><h2>新闻</h2></div></div>
  <div class="news-list">
    <?php foreach ($results['news'] as $article): ?><a class="news-card" href="<?= e(url('/news/' . $article['id'])) ?>"><div class="source-badge"><?= e($article['source_name']) ?></div><h2><?= e($article['title_cn'] ?: $article['title_original']) ?></h2><p><?= e(mb_strimwidth((string) ($article['summary_cn'] ?: $article['summary_original']), 0, 180, '...')) ?></p></a><?php endforeach; ?>
    <?php if (!$results['news']): ?><div class="data-empty">没有新闻命中。</div><?php endif; ?>
  </div>
</section>
<?php endif; ?>
