<?php
require_once __DIR__ . '/functions.php';

$userId = requireUser();
$type = $_GET['type'] ?? 'track';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($type === 'track') {
    $stmt = $pdo->prepare(trackQuery() . ' WHERE t.track_id = :id');
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    if (!$item) {
        http_response_code(404);
        exit('Track not found.');
    }
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
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    if (!$item) {
        http_response_code(404);
        exit('Album not found.');
    }
    $tracksStmt = $pdo->prepare(trackQuery() . ' WHERE t.album_id = :id ORDER BY t.track_number, t.track_id');
    $tracksStmt->execute(['id' => $id]);
    $tracks = $tracksStmt->fetchAll();
    $title = $item['title'];
} else {
    $stmt = $pdo->prepare('SELECT ar.*, COUNT(DISTINCT af.user_id) followers FROM artists ar LEFT JOIN artist_follows af ON af.artist_id = ar.artist_id WHERE ar.artist_id = :id GROUP BY ar.artist_id');
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    if (!$item) {
        http_response_code(404);
        exit('Artist not found.');
    }
    $tracksStmt = $pdo->prepare(trackQuery() . ' WHERE ar.artist_id = :id ORDER BY t.track_id LIMIT 20');
    $tracksStmt->execute(['id' => $id]);
    $tracks = $tracksStmt->fetchAll();
    $albumsStmt = $pdo->prepare('SELECT album_id, title, release_date, cover_image FROM albums WHERE artist_id = :id ORDER BY release_date DESC');
    $albumsStmt->execute(['id' => $id]);
    $albums = $albumsStmt->fetchAll();
    $followStmt = $pdo->prepare('SELECT 1 FROM artist_follows WHERE user_id = :user_id AND artist_id = :artist_id');
    $followStmt->execute(['user_id' => $userId, 'artist_id' => $id]);
    $isFollowing = (bool) $followStmt->fetchColumn();
    $title = $item['artist_name'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title); ?> - PulseFlow</title>
    <link rel="stylesheet" href="../frontend/user-dashbord.css">
    <style>
        body { min-width: 1100px; overflow-x: auto; }
        .page { max-width: 1500px; width: calc(100% - 40px); min-width: 1100px; margin: 0 auto; }
        .panel { background: var(--bg-card); padding: 24px; border-radius: 14px; margin: 20px 0; }
        .hero { display: flex; gap: 20px; align-items: center; }
        .hero img { width: 180px; height: 180px; object-fit: cover; border-radius: 12px; }
        .row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border); }
        .muted { color: var(--text-muted); }
        .button { background: var(--spotify-green); border: 0; border-radius: 20px; padding: 10px 16px; font-weight: 700; }
        .inline { display: inline; }
        .rating { color: #ffd166; }
        .genres { display: flex; gap: 8px; flex-wrap: wrap; }
        .genres a { color: var(--text-muted); border: 1px solid var(--border); border-radius: 20px; padding: 6px 10px; }
        .track-audio { display: block; width: min(520px, 100%); margin: 18px 0; }
        .top-nav { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; gap: 18px; padding: 0 0 8px; background: rgba(18,18,18,.92); border-bottom: 1px solid rgba(255,255,255,.06); }
        .top-search { flex: 1; max-width: 720px; }
        .search-shell { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 999px; background: rgba(255,255,255,.08); }
        .search-shell input { flex: 1; border: 0; outline: 0; background: transparent; color: #fff; }
        .nav-left, .nav-arrows, .user-actions { display: flex; align-items: center; gap: 12px; }
        .user-actions { margin-left: auto; }
        .home-btn, .premium-btn { padding: 10px 16px; border-radius: 999px; text-decoration: none; font-weight: 700; }
        .home-btn { color: #fff; background: rgba(255,255,255,.06); }
        .premium-btn { color: #000; background: #fff; }
        .nav-arrow-btn, .user-icon-btn { width: 38px; height: 38px; border: 0; border-radius: 50%; background: rgba(255,255,255,.08); color: #fff; cursor: pointer; }
    </style>
</head>
<body>
<header class="top-nav">
    <div class="nav-left">
        <a class="home-btn" href="user-dashbord.php">Home</a>
        <div class="nav-arrows">
            <button type="button" class="nav-arrow-btn" data-nav="back" aria-label="Go back">&lt;</button>
            <button type="button" class="nav-arrow-btn" data-nav="forward" aria-label="Go forward">&gt;</button>
        </div>
    </div>
    <form class="top-search" action="search.php" method="get">
        <div class="search-shell">
            <input type="text" name="q" placeholder="What do you want to play?" aria-label="Search songs, albums, artists">
            <button type="submit" aria-label="Search">Search</button>
        </div>
    </form>
    <div class="user-actions"><a class="premium-btn" href="subscriptions.php">Explore Premium</a><button class="user-icon-btn" type="button" aria-label="Account">&#9679;</button></div>
</header>
<main class="main-content page">
    <p><a href="user-dashbord.php" data-dashboard-link>&larr; Home</a></p>
    <?php if ($type === 'track'): ?>
        <section class="panel hero">
            <img src="<?php echo e($item['cover_image'] ?: $item['album_cover']); ?>" alt="">
            <div>
                <h1><?php echo e($item['title']); ?></h1>
                <p class="muted"><?php echo e($item['artist_name']); ?> · <a href="album.php?id=<?php echo (int) $item['album_id']; ?>"><?php echo e($item['album_title']); ?></a></p>
                <p class="genres"><?php foreach ($genres as $genre): ?><a href="genre.php?id=<?php echo (int) $genre['genre_id']; ?>"><?php echo e($genre['genre_name']); ?></a><?php endforeach; ?></p>
                <?php if (!empty($item['audio_url'])): ?><audio class="track-audio" controls preload="metadata" src="<?php echo e($item['audio_url']); ?>" aria-label="Play <?php echo e($item['title']); ?>"></audio><?php else: ?><p class="muted">Audio is not available for this track.</p><?php endif; ?>
                <form class="inline" method="post" action="api.php"><input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="action" value="favorite"><input type="hidden" name="track_id" value="<?php echo $id; ?>"><input type="hidden" name="redirect" value="track.php?id=<?php echo $id; ?>"><button class="button" type="submit"><?php echo $isFavorite ? 'Remove Favorite' : 'Add Favorite'; ?></button></form>
                <span class="rating"> ★ <?php echo $rating['average_rating'] ? number_format((float) $rating['average_rating'], 1) : 'No ratings'; ?> (<?php echo (int) $rating['rating_count']; ?>)</span>
            </div>
        </section>
        <section class="panel"><h2>Rate this track</h2><form method="post" action="api.php"><input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="action" value="rate"><input type="hidden" name="track_id" value="<?php echo $id; ?>"><input type="hidden" name="redirect" value="track.php?id=<?php echo $id; ?>"><select name="rating" required><option value="">Choose rating</option><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?php echo $i; ?>" <?php echo (int) $rating['user_rating'] === $i ? 'selected' : ''; ?>><?php echo $i; ?> star<?php echo $i === 1 ? '' : 's'; ?></option><?php endfor; ?></select><button class="button" type="submit">Save rating</button></form><?php if (!empty($item['lyrics'])): ?><h2>Lyrics</h2><p><?php echo nl2br(e($item['lyrics'])); ?></p><?php endif; ?></section>
    <?php elseif ($type === 'album'): ?>
        <section class="panel hero"><img src="<?php echo e($item['cover_image']); ?>" alt=""><div><h1><?php echo e($item['title']); ?></h1><p class="muted"><?php echo e($item['artist_name']); ?> · <?php echo e($item['release_date']); ?></p><p><?php echo e($item['description']); ?></p></div></section>
        <section class="panel"><h2>Tracks</h2><?php foreach ($tracks as $track): ?><div class="row"><a href="track.php?id=<?php echo (int) $track['track_id']; ?>"><?php echo (int) $track['track_number']; ?>. <?php echo e($track['title']); ?></a><span class="muted"><?php echo (int) $track['duration_seconds']; ?> sec</span></div><?php endforeach; ?></section>
    <?php else: ?>
        <section class="panel hero"><img src="<?php echo e($item['profile_image']); ?>" alt=""><div><h1><?php echo e($item['artist_name']); ?></h1><p class="muted"><?php echo e($item['country']); ?> · <?php echo (int) $item['followers']; ?> followers</p><p><?php echo e($item['bio']); ?></p><form method="post" action="api.php"><input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="action" value="follow"><input type="hidden" name="artist_id" value="<?php echo $id; ?>"><input type="hidden" name="redirect" value="artist.php?id=<?php echo $id; ?>"><button class="button" type="submit"><?php echo $isFollowing ? 'Following' : 'Follow'; ?></button></form></div></section>
        <section class="panel"><h2>Popular tracks</h2><?php foreach ($tracks as $track): ?><div class="row"><a href="track.php?id=<?php echo (int) $track['track_id']; ?>"><?php echo e($track['title']); ?></a><span class="muted"><?php echo (int) $track['duration_seconds']; ?> sec</span></div><?php endforeach; ?></section>
        <section class="panel"><h2>Albums</h2><?php foreach ($albums as $album): ?><div class="row"><a href="album.php?id=<?php echo (int) $album['album_id']; ?>"><?php echo e($album['title']); ?></a><span class="muted"><?php echo e($album['release_date']); ?></span></div><?php endforeach; ?></section>
    <?php endif; ?>
</main>
<script>document.querySelectorAll('.nav-arrow-btn').forEach(button => button.addEventListener('click', () => button.dataset.nav === 'back' ? history.back() : history.forward()));</script>
</body>
</html>
