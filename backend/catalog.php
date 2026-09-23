<?php
require_once __DIR__ . '/functions.php';
$userId = requireUser();
$type = $_GET['type'] ?? 'track';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($type === 'track') {
    $stmt = $pdo->prepare(trackQuery() . ' WHERE t.track_id = :id');
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    if (!$item) { http_response_code(404); exit('Track not found.'); }
    $genresStmt = $pdo->prepare('SELECT g.genre_id, g.genre_name FROM track_genres tg JOIN genres g ON g.genre_id = tg.genre_id WHERE tg.track_id = :id ORDER BY g.genre_name');
    $genresStmt->execute(['id' => $id]);
    $genres = $genresStmt->fetchAll();
    $ratingStmt = $pdo->prepare('SELECT AVG(rating) average_rating, COUNT(*) rating_count, MAX(CASE WHEN user_id = :user_id THEN rating END) user_rating FROM ratings WHERE track_id = :track_id');
    $ratingStmt->execute(['user_id' => $userId, 'track_id' => $id]);
    $rating = $ratingStmt->fetch();
    $isFavorite = userHasFavorite($pdo, $userId, $id);
    $title = $item['title'];
} elseif ($type === 'album') {
    $stmt = $pdo->prepare('SELECT al.*, ar.artist_name FROM albums al JOIN artists ar ON ar.artist_id = al.artist_id WHERE al.album_id = :id');
    $stmt->execute(['id' => $id]); $item = $stmt->fetch();
    if (!$item) { http_response_code(404); exit('Album not found.'); }
    $tracksStmt = $pdo->prepare(trackQuery() . ' WHERE t.album_id = :id ORDER BY t.track_number, t.track_id'); $tracksStmt->execute(['id' => $id]); $tracks = $tracksStmt->fetchAll();
    $title = $item['title'];
} else {
    $stmt = $pdo->prepare('SELECT ar.*, COUNT(DISTINCT af.user_id) followers FROM artists ar LEFT JOIN artist_follows af ON af.artist_id = ar.artist_id WHERE ar.artist_id = :id GROUP BY ar.artist_id');
    $stmt->execute(['id' => $id]); $item = $stmt->fetch();
    if (!$item) { http_response_code(404); exit('Artist not found.'); }
    $tracksStmt = $pdo->prepare(trackQuery() . ' WHERE ar.artist_id = :id ORDER BY t.track_id LIMIT 20'); $tracksStmt->execute(['id' => $id]); $tracks = $tracksStmt->fetchAll();
    $albumsStmt = $pdo->prepare('SELECT album_id, title, release_date, cover_image FROM albums WHERE artist_id = :id ORDER BY release_date DESC'); $albumsStmt->execute(['id' => $id]); $albums = $albumsStmt->fetchAll();
    $followStmt = $pdo->prepare('SELECT 1 FROM artist_follows WHERE user_id = :user_id AND artist_id = :artist_id'); $followStmt->execute(['user_id' => $userId, 'artist_id' => $id]); $isFollowing = (bool)$followStmt->fetchColumn();
    $title = $item['artist_name'];
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo e($title); ?> - PulseFlow</title><link rel="stylesheet" href="../frontend/user-dashbord.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><style>.page{max-width:1000px;margin:auto}.panel{background:var(--bg-card);padding:24px;border-radius:14px;margin:20px 0}.hero{display:flex;gap:20px;align-items:center}.hero img{width:180px;height:180px;object-fit:cover;border-radius:12px}.row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--border)}.muted{color:var(--text-muted)}.button{background:var(--spotify-green);border:0;border-radius:20px;padding:10px 16px;font-weight:700}.inline{display:inline}.rating{color:#ffd166}.genres{display:flex;gap:8px;flex-wrap:wrap}.genres a{color:var(--text-muted);border:1px solid var(--border);border-radius:20px;padding:6px 10px}</style></head><body><main class="main-content page"><p><a href="user-dashbord.php">← Home</a></p>
+<?php if ($type === 'track'): ?><section class="panel hero"><img src="<?php echo e($item['cover_image'] ?: $item['album_cover']); ?>" alt=""><div><h1><?php echo e($item['title']); ?></h1><p class="muted"><?php echo e($item['artist_name']); ?> · <a href="album.php?id=<?php echo (int)$item['album_id']; ?>"><?php echo e($item['album_title']); ?></a></p><p class="genres"><?php foreach ($genres as $genre): ?><a href="genre.php?id=<?php echo (int)$genre['genre_id']; ?>"><?php echo e($genre['genre_name']); ?></a><?php endforeach; ?></p><form class="inline" method="post" action="api.php"><input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="action" value="favorite"><input type="hidden" name="track_id" value="<?php echo $id; ?>"><input type="hidden" name="redirect" value="track.php?id=<?php echo $id; ?>"><button class="button" type="submit"><?php echo $isFavorite ? 'Remove Favorite' : 'Add Favorite'; ?></button></form><span class="rating"> ★ <?php echo $rating['average_rating'] ? number_format((float)$rating['average_rating'], 1) : 'No ratings'; ?> (<?php echo (int)$rating['rating_count']; ?>)</span></div></section><section class="panel"><h2>Rate this track</h2><form method="post" action="api.php"><input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="action" value="rate"><input type="hidden" name="track_id" value="<?php echo $id; ?>"><input type="hidden" name="redirect" value="track.php?id=<?php echo $id; ?>"><select name="rating" required><option value="">Choose rating</option><?php for ($i=1;$i<=5;$i++): ?><option value="<?php echo $i; ?>" <?php echo (int)$rating['user_rating'] === $i ? 'selected' : ''; ?>><?php echo $i; ?> star<?php echo $i === 1 ? '' : 's'; ?></option><?php endfor; ?></select><button class="button" type="submit">Save rating</button></form><?php if (!empty($item['lyrics'])): ?><h2>Lyrics</h2><p><?php echo nl2br(e($item['lyrics'])); ?></p><?php endif; ?></section>
+<?php elseif ($type === 'album'): ?><section class="panel hero"><img src="<?php echo e($item['cover_image']); ?>" alt=""><div><h1><?php echo e($item['title']); ?></h1><p class="muted"><?php echo e($item['artist_name']); ?> · <?php echo e($item['release_date']); ?></p><p><?php echo e($item['description']); ?></p></div></section><section class="panel"><h2>Tracks</h2><?php foreach ($tracks as $track): ?><div class="row"><a href="track.php?id=<?php echo (int)$track['track_id']; ?>"><?php echo (int)$track['track_number']; ?>. <?php echo e($track['title']); ?></a><span class="muted"><?php echo (int)$track['duration_seconds']; ?> sec</span></div><?php endforeach; ?></section>
+<?php else: ?><section class="panel hero"><img src="<?php echo e($item['profile_image']); ?>" alt=""><div><h1><?php echo e($item['artist_name']); ?> <?php echo $item['verified'] ? '✓' : ''; ?></h1><p class="muted"><?php echo e($item['country']); ?> · <?php echo (int)$item['followers']; ?> followers</p><p><?php echo e($item['bio']); ?></p><form method="post" action="api.php"><input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="action" value="follow"><input type="hidden" name="artist_id" value="<?php echo $id; ?>"><input type="hidden" name="redirect" value="artist.php?id=<?php echo $id; ?>"><button class="button" type="submit"><?php echo $isFollowing ? 'Following' : 'Follow'; ?></button></form></div></section><section class="panel"><h2>Popular tracks</h2><?php foreach ($tracks as $track): ?><div class="row"><a href="track.php?id=<?php echo (int)$track['track_id']; ?>"><?php echo e($track['title']); ?></a><span class="muted"><?php echo (int)$track['duration_seconds']; ?> sec</span></div><?php endforeach; ?></section><section class="panel"><h2>Albums</h2><?php foreach ($albums as $album): ?><div class="row"><a href="album.php?id=<?php echo (int)$album['album_id']; ?>"><?php echo e($album['title']); ?></a><span class="muted"><?php echo e($album['release_date']); ?></span></div><?php endforeach; ?></section><?php endif; ?></main></body></html>
