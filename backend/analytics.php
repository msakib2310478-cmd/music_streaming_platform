<?php
require_once __DIR__ . '/functions.php';
$userId = requireUser();

$summaryStmt = $pdo->prepare("SELECT COUNT(*) AS songs_played,
    COALESCE(SUM(COALESCE(sh.duration_played, t.duration_seconds)), 0) AS listening_seconds
    FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id WHERE sh.user_id = :user_id");
$summaryStmt->execute(['user_id' => $userId]);
$summary = $summaryStmt->fetch();

$topGenreStmt = $pdo->prepare("SELECT g.genre_name, COUNT(*) AS plays
    FROM stream_history sh JOIN track_genres tg ON tg.track_id = sh.track_id
    JOIN genres g ON g.genre_id = tg.genre_id WHERE sh.user_id = :user_id
    GROUP BY g.genre_id, g.genre_name ORDER BY plays DESC, g.genre_name LIMIT 1");
$topGenreStmt->execute(['user_id' => $userId]);
$topGenre = $topGenreStmt->fetch() ?: ['genre_name' => 'No data', 'plays' => 0];

$topArtistStmt = $pdo->prepare("SELECT ar.artist_name, COUNT(*) AS plays
    FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id
    JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id
    WHERE sh.user_id = :user_id GROUP BY ar.artist_id, ar.artist_name
    ORDER BY plays DESC, ar.artist_name LIMIT 1");
$topArtistStmt->execute(['user_id' => $userId]);
$topArtist = $topArtistStmt->fetch() ?: ['artist_name' => 'No data', 'plays' => 0];

$mostPlayedStmt = $pdo->prepare("SELECT t.title, ar.artist_name, COUNT(*) AS plays
    FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id
    JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id
    WHERE sh.user_id = :user_id GROUP BY t.track_id, t.title, ar.artist_name
    ORDER BY plays DESC, t.title LIMIT 1");
$mostPlayedStmt->execute(['user_id' => $userId]);
$mostPlayed = $mostPlayedStmt->fetch() ?: ['title' => 'No data', 'artist_name' => '', 'plays' => 0];

$dailyStmt = $pdo->prepare("SELECT DATE(sh.played_at) AS play_date, COUNT(*) AS plays
    FROM stream_history sh WHERE sh.user_id = :user_id
    AND sh.played_at >= CURRENT_DATE - INTERVAL 6 DAY
    GROUP BY DATE(sh.played_at) ORDER BY play_date");
$dailyStmt->execute(['user_id' => $userId]);
$dailyRows = $dailyStmt->fetchAll();
$daily = [];
foreach ($dailyRows as $row) {
    $daily[$row['play_date']] = (int)$row['plays'];
}
$maxDaily = max(1, ...array_values($daily));
$weekDays = [];
for ($offset = 6; $offset >= 0; $offset--) {
    $date = date('Y-m-d', strtotime("-$offset days"));
    $weekDays[] = ['label' => date('D', strtotime($date)), 'plays' => $daily[$date] ?? 0, 'width' => (int)round((($daily[$date] ?? 0) / $maxDaily) * 100)];
}
function formatListeningTime(int $seconds): string
{
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    return $hours > 0 ? $hours . 'h ' . $minutes . 'm' : $minutes . 'm';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Your Listening Analytics - PulseFlow</title><link rel="stylesheet" href="../frontend/user-dashbord.css"><link rel="stylesheet" href="../frontend/reporting.css"><style>
body{min-height:100vh;background:linear-gradient(135deg,#101820,#172c2b 48%,#101214);color:#f4f7f5}.analytics{max-width:1180px;margin:0 auto;padding:36px 24px}.back{color:#9fe8bb;text-decoration:none}.eyebrow{color:#8ed9aa;text-transform:uppercase;letter-spacing:.14em;font-size:.78rem}.analytics h1{font-size:clamp(2rem,5vw,4rem);margin:10px 0 8px}.muted{color:#a8b8b0}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:28px 0}.stat,.panel{background:rgba(9,18,17,.68);border:1px solid rgba(176,232,199,.14);border-radius:12px;padding:20px}.stat strong{display:block;font-size:1.7rem;margin-top:8px}.stat span{color:#8fa69a;font-size:.82rem}.panel{margin-top:18px}.panel h2{margin-top:0}.day{display:grid;grid-template-columns:42px 1fr 44px;gap:12px;align-items:center;margin:14px 0}.bar{height:12px;background:#243b35;border-radius:3px;overflow:hidden}.bar i{display:block;height:100%;background:#75d99c;border-radius:3px}.callouts{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.callouts p{margin:8px 0 0;font-weight:700}@media(max-width:760px){.stats,.callouts{grid-template-columns:repeat(2,1fr)}.analytics{padding:24px 16px}}@media(max-width:460px){.stats{grid-template-columns:1fr}.day{grid-template-columns:38px 1fr 32px}}
</style></head><body><main class="analytics"><a class="back" href="user-dashbord.php">&larr; Back to your dashboard</a><p class="eyebrow">Your music stats</p><h1>Listening, measured.</h1><p class="muted">A seven-day snapshot built from your stream history.</p><section class="stats"><div class="stat"><span>Songs played</span><strong><?php echo (int)$summary['songs_played']; ?></strong></div><div class="stat"><span>Listening time</span><strong><?php echo e(formatListeningTime((int)$summary['listening_seconds'])); ?></strong></div><div class="stat"><span>Top genre</span><strong><?php echo e($topGenre['genre_name']); ?></strong></div><div class="stat"><span>Top artist</span><strong><?php echo e($topArtist['artist_name']); ?></strong></div></section><section class="panel"><h2>This week</h2><?php foreach($weekDays as $day): ?><div class="day"><span><?php echo e($day['label']); ?></span><div class="bar"><i style="width:<?php echo $day['width']; ?>%"></i></div><span class="muted"><?php echo $day['plays']; ?></span></div><?php endforeach; ?></section><section class="panel callouts"><div><span class="muted">Most played</span><p><?php echo e($mostPlayed['title']); ?></p><span class="muted"><?php echo e($mostPlayed['artist_name']); ?> · <?php echo (int)$mostPlayed['plays']; ?> plays</span></div><div><span class="muted">Top genre plays</span><p><?php echo (int)$topGenre['plays']; ?></p><span class="muted"><?php echo e($topGenre['genre_name']); ?></span></div><div><span class="muted">Top artist plays</span><p><?php echo (int)$topArtist['plays']; ?></p><span class="muted"><?php echo e($topArtist['artist_name']); ?></span></div></section></main></body></html>
