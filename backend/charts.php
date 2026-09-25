<?php
require_once __DIR__ . '/functions.php';
requireUser();
$period = $_GET['period'] ?? 'week';
$periods = ['today' => ['Today\'s Top 10', 'today_plays'], 'week' => ['This Week\'s Top 10', 'week_plays'], 'month' => ['This Month\'s Top 10', 'month_plays']];
$chartRows = [];
if (isset($periods[$period])) {
    [$heading, $metric] = $periods[$period];
    $stmt = $pdo->query("SELECT track_id, title, COALESCE(artist_names, artist_name) AS artist_name, $metric AS plays
        FROM vw_track_chart_metrics WHERE $metric > 0 ORDER BY plays DESC, title LIMIT 10");
    $chartRows = $stmt->fetchAll();
} elseif ($period === 'rated') {
    $heading = 'Most Rated';
    $stmt = $pdo->query("SELECT track_id, title, COALESCE(artist_names, artist_name) AS artist_name, average_rating AS score, rating_count AS votes FROM vw_track_chart_metrics WHERE rating_count > 0 ORDER BY votes DESC, score DESC, title LIMIT 10");
    $chartRows = $stmt->fetchAll();
} elseif ($period === 'favorited') {
    $heading = 'Most Favorited';
    $stmt = $pdo->query("SELECT track_id, title, COALESCE(artist_names, artist_name) AS artist_name, favorite_count AS favorites FROM vw_track_chart_metrics WHERE favorite_count > 0 ORDER BY favorites DESC, title LIMIT 10");
    $chartRows = $stmt->fetchAll();
} else {
    $period = 'trending';
    $heading = 'Fastest Trending';
    $stmt = $pdo->query("SELECT track_id, title, COALESCE(artist_names, artist_name) AS artist_name, recent_plays, previous_plays, growth_percent FROM vw_trending_tracks ORDER BY growth_percent DESC, recent_plays DESC, title LIMIT 10");
    $chartRows = $stmt->fetchAll();
}
if (!$chartRows) {
    $heading .= ' - all-time fallback';
    $fallbackChart = $pdo->query("SELECT track_id, title, COALESCE(artist_names, artist_name) AS artist_name, total_plays AS plays
        FROM vw_track_chart_metrics WHERE total_plays > 0 ORDER BY total_plays DESC, title LIMIT 10");
    $chartRows = $fallbackChart->fetchAll();
}
$links = ['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'rated' => 'Most rated', 'favorited' => 'Most favorited', 'trending' => 'Trending'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo e($heading); ?> - PulseFlow</title><link rel="stylesheet" href="../frontend/user-dashbord.css"><link rel="stylesheet" href="../frontend/reporting.css"><style>
body{min-height:100vh;background:linear-gradient(145deg,#14120e,#2b2518 52%,#111417);color:#f7f4ed}.charts{max-width:1080px;margin:0 auto;padding:36px 24px}.back{color:#f3c978;text-decoration:none}.charts h1{font-size:clamp(2rem,5vw,4rem);margin:24px 0 8px}.muted{color:#b7b0a1}.tabs{display:flex;gap:8px;flex-wrap:wrap;margin:24px 0}.tabs a{padding:10px 14px;border:1px solid rgba(255,255,255,.14);border-radius:6px;text-decoration:none;color:#d9d3c7}.tabs a.active{background:#f0bd61;color:#19150d;border-color:#f0bd61;font-weight:700}.chart{width:100%;border-collapse:collapse;background:rgba(17,19,20,.7);border:1px solid rgba(255,255,255,.1);border-radius:10px;overflow:hidden}.chart th,.chart td{text-align:left;padding:15px 14px;border-bottom:1px solid rgba(255,255,255,.08)}.chart th{color:#a9a293;font-size:.76rem;text-transform:uppercase;letter-spacing:.1em}.rank{color:#f0bd61;font-weight:800}.up{color:#7ce0a1}.down{color:#ed8d7f}@media(max-width:620px){.charts{padding:24px 14px}.chart th:nth-child(3),.chart td:nth-child(3){display:none}}
</style></head><body><main class="charts"><a class="back" href="user-dashbord.php">&larr; Back to your dashboard</a><h1><?php echo e($heading); ?></h1><p class="muted">Live rankings from the PulseFlow listening database.</p><nav class="tabs"><?php foreach($links as $key=>$label): ?><a class="<?php echo $period === $key ? 'active' : ''; ?>" href="charts.php?period=<?php echo e($key); ?>"><?php echo e($label); ?></a><?php endforeach; ?></nav><table class="chart"><thead><tr><th>#</th><th>Song</th><th>Artist</th><th><?php echo $period === 'trending' ? 'Last 7 days' : ($period === 'rated' ? 'Rating' : ($period === 'favorited' ? 'Favorites' : 'Plays')); ?></th><?php if($period === 'trending'): ?><th>Growth</th><?php endif; ?></tr></thead><tbody><?php foreach($chartRows as $index=>$row): ?><tr><td class="rank"><?php echo $index + 1; ?></td><td><?php echo e($row['title']); ?></td><td><?php echo e($row['artist_name']); ?></td><td><?php echo $period === 'rated' ? number_format((float)$row['score'], 1) . ' / 5' : ($period === 'trending' ? (int)$row['recent_plays'] : (int)($row['plays'] ?? $row['favorites'] ?? 0)); ?></td><?php if($period === 'trending'): ?><td class="<?php echo (int)$row['growth_percent'] >= 0 ? 'up' : 'down'; ?>"><?php echo (int)$row['growth_percent'] >= 0 ? '&uarr;' : '&darr;'; ?> <?php echo abs((int)$row['growth_percent']); ?>%</td><?php endif; ?></tr><?php endforeach; ?><?php if(!$chartRows): ?><tr><td colspan="5" class="muted">No chart data yet.</td></tr><?php endif; ?></tbody></table></main></body></html>
