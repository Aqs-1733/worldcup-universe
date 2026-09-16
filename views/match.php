<section class="page-title match-title reveal">
  <div>
    <span class="kicker"><?= e($match['stage']) ?><?= $match['group_name'] ? ' · ' . e($match['group_name']) . '组' : '' ?></span>
    <h1><?= team_name_html($match['home_flag_url'] ?? null, $match['home_flag'] ?? null, $match['home_name_cn'] ?? null, $match['home_team_name'] ?? null) ?> <em>VS</em> <?= team_name_html($match['away_flag_url'] ?? null, $match['away_flag'] ?? null, $match['away_name_cn'] ?? null, $match['away_team_name'] ?? null) ?></h1>
    <p><?= e(date_label($match['starts_at'])) ?> · <?= e($match['venue_name_cn'] ?: $match['venue_name_original'] ?: '场馆待同步') ?><?= $match['venue_city'] ? ' · ' . e($match['venue_city']) : '' ?></p>
  </div>
  <a class="ghost-button" href="<?= e(url('/worldcup')) ?>">返回赛程</a>
</section>

<section class="score-hero reveal">
  <div class="score-team">
    <?= flag_html($match['home_flag_url'] ?? null, $match['home_flag'] ?? null, '') ?>
    <strong><?= e($match['home_name_cn'] ?: $match['home_team_name'] ?: $match['home_name_original']) ?></strong>
  </div>
  <div class="score-center">
    <span><?= e($match['status']) ?></span>
    <b><?= $match['home_score'] === null ? 'VS' : e($match['home_score'] . ' : ' . $match['away_score']) ?></b>
    <?php if ($match['home_penalty_score'] !== null || $match['away_penalty_score'] !== null): ?><small>点球 <?= e((string) $match['home_penalty_score']) ?> : <?= e((string) $match['away_penalty_score']) ?></small><?php endif; ?>
  </div>
  <div class="score-team right">
    <?= flag_html($match['away_flag_url'] ?? null, $match['away_flag'] ?? null, '') ?>
    <strong><?= e($match['away_name_cn'] ?: $match['away_team_name'] ?: $match['away_name_original']) ?></strong>
  </div>
</section>

<section class="dashboard-grid">
  <div class="panel reveal">
    <div class="section-head"><div><span class="kicker">STATS</span><h2>技术统计</h2></div></div>
    <?php if ($stats): ?>
      <div class="stat-compare">
        <?php foreach ($stats as $row): ?>
          <article class="stat-card">
            <h3><?= team_name_html($row['flag_url'] ?? null, $row['flag_emoji'] ?? null, $row['name_cn'] ?? null, $row['name_original'] ?? null) ?></h3>
            <div><span>控球</span><b><?= e((string) ($row['possession'] ?? '-')) ?>%</b></div>
            <div><span>射门</span><b><?= e((string) ($row['shots'] ?? '-')) ?></b></div>
            <div><span>射正</span><b><?= e((string) ($row['shots_on_target'] ?? '-')) ?></b></div>
            <div><span>角球</span><b><?= e((string) ($row['corners'] ?? '-')) ?></b></div>
            <div><span>犯规</span><b><?= e((string) ($row['fouls'] ?? '-')) ?></b></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?><div class="data-empty">技术统计待同步。</div><?php endif; ?>
  </div>

  <div class="panel reveal">
    <div class="section-head"><div><span class="kicker">TIMELINE</span><h2>比赛事件</h2></div></div>
    <div class="event-timeline">
      <?php foreach ($events as $event): ?>
        <article>
          <time><?= e((string) ($event['minute'] ?? '-')) ?>'</time>
          <strong><?= e($event['event_type']) ?></strong>
          <span><?= team_name_html($event['flag_url'] ?? null, $event['flag_emoji'] ?? null, $event['team_name_cn'] ?? null, $event['team_name_original'] ?? null) ?></span>
          <small><?= e(display_name($event['player_name_cn'] ?? null, $event['player_name_original'] ?? null)) ?></small>
        </article>
      <?php endforeach; ?>
      <?php if (!$events): ?><div class="data-empty">事件待同步。</div><?php endif; ?>
    </div>
  </div>
</section>

<section class="panel reveal">
  <div class="section-head"><div><span class="kicker">LINEUPS</span><h2>阵容</h2></div></div>
  <div class="lineup-grid">
    <?php foreach ($lineups as $teamName => $rows): ?>
      <details class="lineup-card" open>
        <summary><?= e($teamName) ?> · <?= e((string) count($rows)) ?> 人</summary>
        <div class="player-stack">
          <?php foreach ($rows as $row): ?>
            <div class="player-row"><b><?= e(($row['shirt_number'] ? '#' . $row['shirt_number'] . ' ' : '') . display_name($row['player_name_cn'] ?? null, $row['player_name_original'] ?? null)) ?></b><small><?= e($row['is_starting_xi'] ? '首发' : '替补') ?> · <?= e($row['position'] ?: $row['tactical_position'] ?: '位置待同步') ?> · <?= e((string) ($row['minutes_played'] ?? '-')) ?> 分钟</small></div>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endforeach; ?>
    <?php if (!$lineups): ?><div class="data-empty">阵容待同步。</div><?php endif; ?>
  </div>
</section>

<section class="panel reveal">
  <div class="section-head"><div><span class="kicker">SOURCE</span><h2>来源</h2></div></div>
  <?php if ($match['source_url']): ?><a class="mini-link" href="<?= e($match['source_url']) ?>" target="_blank" rel="noreferrer">打开原始来源</a><?php else: ?><div class="data-empty">来源链接待同步。</div><?php endif; ?>
</section>
