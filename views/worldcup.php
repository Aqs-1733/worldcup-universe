<section class="page-title reveal">
  <div><span class="kicker">WORLD CUP</span><h1>赛程、分组与淘汰赛树</h1><p>小组赛按组折叠展示；淘汰赛用 VS 树状图展示，决赛居中，每场比赛都可点进详情。</p></div>
  <a class="ghost-button" href="<?= e(url('/news')) ?>">查看新闻</a>
</section>

<section class="panel reveal">
  <div class="section-head"><div><span class="kicker">KNOCKOUT TREE</span><h2>淘汰赛 VS 树状图</h2></div><span class="source-chip">不预测未赛比赛</span></div>
  <?php
    $stageOrder = ['32强', '16强', '四分之一决赛', '半决赛', '决赛', '季军赛'];
    $knockoutGroups = array_fill_keys($stageOrder, []);
    foreach ($matches as $match) {
        if (isset($knockoutGroups[$match['stage']])) {
            $knockoutGroups[$match['stage']][] = $match;
        }
    }
    $leftStages = ['32强', '16强', '四分之一决赛', '半决赛'];
    $rightStages = array_reverse($leftStages);
    $finalMatch = $knockoutGroups['决赛'][0] ?? null;
    $thirdMatch = $knockoutGroups['季军赛'][0] ?? null;
    $half = static fn(array $items, bool $right = false): array => array_slice($items, $right ? (int) ceil(count($items) / 2) : 0, $right ? null : (int) ceil(count($items) / 2));
  ?>
  <div class="knockout-tree">
    <?php foreach ($leftStages as $stage): ?>
      <div class="tree-round left-round">
        <h3><?= e($stage) ?></h3>
        <?php foreach ($half($knockoutGroups[$stage] ?? []) as $match): ?><?= match_tree_card($match) ?><?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <div class="tree-final-zone">
      <h3>FINAL</h3>
      <?php if ($finalMatch): ?><?= match_tree_card($finalMatch, true) ?><?php else: ?><div class="bracket-slot is-empty">决赛席位待同步</div><?php endif; ?>
      <?php if ($thirdMatch): ?><h3 class="third-title">季军赛</h3><?= match_tree_card($thirdMatch, true) ?><?php endif; ?>
    </div>
    <?php foreach ($rightStages as $stage): ?>
      <div class="tree-round right-round">
        <h3><?= e($stage) ?></h3>
        <?php foreach ($half($knockoutGroups[$stage] ?? [], true) as $match): ?><?= match_tree_card($match) ?><?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$matches): ?><div class="data-empty wide">同步赛事后显示真实赛程和结果。</div><?php endif; ?>
  </div>
</section>

<section class="panel reveal">
  <div class="section-head"><div><span class="kicker">GROUP STAGE</span><h2>小组赛分组</h2></div><span class="source-chip">点击展开每组比赛</span></div>
  <div class="group-accordion">
    <?php foreach ($groupMatches as $group => $rows): ?>
      <details class="group-card">
        <summary><strong><?= e($group) ?> 组</strong><span><?= e((string) count($rows)) ?> 场比赛</span></summary>
        <div class="match-list compact-match-list">
          <?php foreach ($rows as $match): ?>
            <a class="match-row" href="<?= e(url('/matches/' . $match['id'])) ?>">
              <time><?= e(date_label($match['starts_at'])) ?></time>
              <div class="versus">
                <span><?= team_name_html($match['home_flag_url'] ?? null, $match['home_flag'] ?? null, $match['home_name_cn'] ?? null, $match['home_team_name'] ?: ($match['home_name_original'] ?? null)) ?></span>
                <b><?= $match['home_score'] === null ? 'vs' : e($match['home_score'] . ':' . $match['away_score']) ?></b>
                <span><?= team_name_html($match['away_flag_url'] ?? null, $match['away_flag'] ?? null, $match['away_name_cn'] ?? null, $match['away_team_name'] ?: ($match['away_name_original'] ?? null)) ?></span>
              </div>
              <small><?= e($match['status']) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endforeach; ?>
    <?php if (!$groupMatches): ?><div class="data-empty">同步小组赛后显示分组详情。</div><?php endif; ?>
  </div>
</section>

<section class="panel reveal">
  <div class="section-head"><div><span class="kicker">STANDINGS</span><h2>积分榜</h2></div></div>
  <?php foreach ($standings as $group => $rows): ?>
    <details class="group-card standings-card" open>
      <summary><strong><?= e($group) ?> 组积分</strong><span><?= e((string) count($rows)) ?> 支球队</span></summary>
      <div class="table-wrap">
        <table>
          <thead><tr><th>球队</th><th>赛</th><th>胜</th><th>平</th><th>负</th><th>进</th><th>失</th><th>净</th><th>分</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= team_name_html($row['flag_url'] ?? null, $row['flag_emoji'] ?? null, $row['name_cn'] ?? null, $row['name_original'] ?? null) ?></td>
              <td><?= e($row['played']) ?></td><td><?= e($row['won']) ?></td><td><?= e($row['drawn']) ?></td><td><?= e($row['lost']) ?></td>
              <td><?= e($row['goals_for']) ?></td><td><?= e($row['goals_against']) ?></td><td><?= e($row['goal_difference']) ?></td><td><b><?= e($row['points']) ?></b></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>
  <?php endforeach; ?>
  <?php if (!$standings): ?><div class="data-empty">同步积分榜后显示。</div><?php endif; ?>
</section>

<?php
function match_tree_card(array $match, bool $featured = false): string
{
    ob_start();
    ?>
    <a class="tree-match <?= $featured ? 'featured' : '' ?> <?= e($match['status']) ?>" href="<?= e(url('/matches/' . $match['id'])) ?>">
      <small><?= e(date_label($match['starts_at'])) ?></small>
      <div><span><?= team_name_html($match['home_flag_url'] ?? null, $match['home_flag'] ?? null, $match['home_name_cn'] ?? null, $match['home_team_name'] ?: ($match['home_name_original'] ?? null)) ?></span><b><?= $match['home_score'] === null ? '-' : e($match['home_score']) ?></b></div>
      <div><span><?= team_name_html($match['away_flag_url'] ?? null, $match['away_flag'] ?? null, $match['away_name_cn'] ?? null, $match['away_team_name'] ?: ($match['away_name_original'] ?? null)) ?></span><b><?= $match['away_score'] === null ? '-' : e($match['away_score']) ?></b></div>
    </a>
    <?php
    return (string) ob_get_clean();
}
?>
