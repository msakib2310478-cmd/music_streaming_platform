<?php
require_once __DIR__ . '/functions.php';
requireUser();
$artistId = filter_input(INPUT_GET, 'artist_id', FILTER_VALIDATE_INT) ?: 0;
$artistStmt = $pdo->prepare('SELECT artist_id, artist_name, genre, country FROM artists WHERE artist_id = :artist_id');
$artistStmt->execute(['artist_id' => $artistId]);
$artist = $artistStmt->fetch();
if (!$artist) {
    http_response_code(404);
    exit('Artist not found.');
}
$summaryStmt = $pdo->prepare("SELECT
    (SELECT COUNT(*) FROM artist_follows WHERE artist_id = :followers_artist_id) AS followers,
    (SELECT COUNT(*) FROM stream_history sh JOIN track_artists ta ON ta.track_id = sh.track_id WHERE ta.artist_id = :streams_artist_id) AS streams,
    (SELECT AVG(r.rating) FROM ratings r JOIN track_artists ta ON ta.track_id = r.track_id WHERE ta.artist_id = :ratings_artist_id) AS average_rating");
$summaryStmt->execute(['followers_artist_id' => $artistId, 'streams_artist_id' => $artistId, 'ratings_artist_id' => $artistId]);
$summary = $summaryStmt->fetch() ?: ['followers' => 0, 'streams' => 0, 'average_rating' => null];
$topStmt = $pdo->prepare("SELECT t.title, COUNT(sh.stream_id) AS plays FROM tracks t JOIN track_artists ta ON ta.track_id = t.track_id LEFT JOIN stream_history sh ON sh.track_id = t.track_id WHERE ta.artist_id = :artist_id GROUP BY t.track_id, t.title ORDER BY plays DESC, t.title LIMIT 10");
$topStmt->execute(['artist_id' => $artistId]);
$topSongs = $topStmt->fetchAll();
$deviceStmt = $pdo->prepare("SELECT COALESCE(sh.device_type, 'unknown') AS device_type, COUNT(*) AS streams FROM stream_history sh JOIN track_artists ta ON ta.track_id = sh.track_id WHERE ta.artist_id = :artist_id GROUP BY sh.device_type ORDER BY streams DESC");
$deviceStmt->execute(['artist_id' => $artistId]);
$devices = $deviceStmt->fetchAll();
$totalDeviceStreams = max(1, array_sum(array_map(static fn($row) => (int)$row['streams'], $devices)));
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo e($artist['artist_name']); ?> Dashboard - PulseFlow</title><link rel="stylesheet" href="../frontend/user-dashbord.css"><link rel="stylesheet" href="../frontend/reporting.css"><style>
body{min-height:100vh;background:linear-gradient(135deg,#16111a,#241c2d 52%,#101418);color:#f8f4fb}.artist{max-width:1100px;margin:0 auto;padding:36px 24px}.back{color:#d6a7ff;text-decoration:none}.eyebrow{color:#c99bf3;text-transform:uppercase;letter-spacing:.14em;font-size:.78rem}.artist h1{font-size:clamp(2rem,5vw,4rem);margin:10px 0 6px}.muted{color:#b9adbf}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:28px 0}.stat,.panel{background:rgba(18,13,25,.7);border:1px solid rgba(220,180,255,.16);border-radius:12px;padding:20px}.stat strong{display:block;font-size:1.8rem;margin-top:8px}.stat span{color:#ad9bb8;font-size:.82rem}.panel{margin-top:18px}.panel h2{margin-top:0}.song{display:grid;grid-template-columns:36px 1fr auto;gap:14px;padding:13px 0;border-bottom:1px solid rgba(255,255,255,.08)}.rank{color:#d6a7ff;font-weight:800}.device{display:grid;grid-template-columns:90px 1fr 48px;gap:12px;align-items:center;margin:14px 0}.bar{height:10px;background:#3a2b46;border-radius:3px;overflow:hidden}.bar i{display:block;height:100%;background:#c99bf3}@media(max-width:620px){.artist{padding:24px 16px}.stats{grid-template-columns:1fr}.device{grid-template-columns:72px 1fr 38px}}
</style></head><body><main class="artist"><a class="back" href="artist.php?id=<?php echo $artistId; ?>">&larr; Back to artist</a><p class="eyebrow">Artist dashboard</p><h1><?php echo e($artist['artist_name']); ?></h1><p class="muted"><?php echo e($artist['genre'] ?: 'Independent artist'); ?><?php echo $artist['country'] ? ' · ' . e($artist['country']) : ''; ?></p><section class="stats"><div class="stat"><span>Followers</span><strong><?php echo (int)$summary['followers']; ?></strong></div><div class="stat"><span>Total streams</span><strong><?php echo (int)$summary['streams']; ?></strong></div><div class="stat"><span>Average rating</span><strong><?php echo $summary['average_rating'] === null ? 'No ratings' : number_format((float)$summary['average_rating'], 1) . ' / 5'; ?></strong></div></section><section class="panel"><h2>Top songs</h2><?php foreach($topSongs as $index=>$song): ?><div class="song"><span class="rank"><?php echo $index + 1; ?></span><span><?php echo e($song['title']); ?></span><strong><?php echo (int)$song['plays']; ?> plays</strong></div><?php endforeach; ?><?php if(!$topSongs): ?><p class="muted">Add tracks to start seeing performance.</p><?php endif; ?></section><section class="panel"><h2>Audience by device</h2><?php foreach($devices as $device): ?><div class="device"><span><?php echo e(ucfirst($device['device_type'])); ?></span><div class="bar"><i style="width:<?php echo (int)round(((int)$device['streams'] / $totalDeviceStreams) * 100); ?>%"></i></div><span class="muted"><?php echo (int)round(((int)$device['streams'] / $totalDeviceStreams) * 100); ?>%</span></div><?php endforeach; ?><?php if(!$devices): ?><p class="muted">No audience data yet.</p><?php endif; ?></section></main></body></html>
