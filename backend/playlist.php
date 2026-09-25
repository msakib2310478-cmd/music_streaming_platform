<?php
require_once __DIR__ . '/functions.php';

$userId = requireUser();
$playlistId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$playlistStmt = $pdo->prepare(
    'SELECT playlist_id, playlist_name, is_public, user_id
     FROM playlists
     WHERE playlist_id = :id
       AND (user_id = :user_id OR is_public = 1)'
);
$playlistStmt->execute([
    'id' => $playlistId,
    'user_id' => $userId,
]);
$playlist = $playlistStmt->fetch();

if (!$playlist) {
    http_response_code(404);
    exit('Playlist not found.');
}

$stmt = $pdo->prepare(
    'SELECT pt.track_order, t.track_id, t.title, t.duration_seconds, ar.artist_name
     FROM playlist_tracks pt
     JOIN tracks t ON t.track_id = pt.track_id
     JOIN albums al ON al.album_id = t.album_id
     JOIN artists ar ON ar.artist_id = al.artist_id
     WHERE pt.playlist_id = :id
     ORDER BY pt.track_order IS NULL, pt.track_order, t.title'
);
$stmt->execute(['id' => $playlistId]);
$tracks = $stmt->fetchAll();

$owner = (int) $playlist['user_id'] === $userId;
$allTracks = $owner
    ? $pdo->query(
        'SELECT t.track_id, t.title, ar.artist_name
         FROM tracks t
         JOIN albums al ON al.album_id = t.album_id
         JOIN artists ar ON ar.artist_id = al.artist_id
         ORDER BY t.title
         LIMIT 200'
    )->fetchAll()
    : [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($playlist['playlist_name']); ?></title>
    <link rel="stylesheet" href="../frontend/user-dashbord.css">
    <style>
        body {
            min-width: 1100px;
            overflow-x: auto;
        }

        .page {
            max-width: 1500px !important;
            width: calc(100% - 40px) !important;
            min-width: 1100px;
            margin: 0 auto !important;
        }

        .panel {
            background: var(--bg-card);
            padding: 24px;
            border-radius: 14px;
            margin: 20px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }

        .muted {
            color: var(--text-muted);
        }

        a {
            color: inherit;
        }

        .inline {
            display: inline;
        }

        .top-nav {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 18px;
            padding: 0 0 8px;
            margin: 0;
            background: rgba(18, 18, 18, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .home-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 700;
        }

        .home-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .top-search {
            flex: 1;
            display: flex;
            justify-content: flex-start;
            max-width: 720px;
        }

        .search-shell {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .search-shell i {
            color: var(--text-muted);
            font-size: 16px;
        }

        .search-shell input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            color: #fff;
            font-size: 14px;
        }

        .search-shell input::placeholder {
            color: var(--text-muted);
        }

        .search-shell button {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 15px;
        }

        .nav-arrows {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-arrow-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .nav-arrow-btn:hover {
            background: rgba(255, 255, 255, 0.14);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-left: auto;
        }

        .premium-btn {
            background: #fff;
            color: #000;
            border: 0;
            padding: 9px 16px;
            border-radius: 999px;
            font-weight: 700;
            text-decoration: none;
        }

        .user-menu-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .user-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .user-icon-btn:hover {
            border-color: var(--spotify-green);
        }

        .account-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 240px;
            background: rgba(18, 18, 18, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.4);
            padding: 16px;
            display: none;
            z-index: 40;
        }

        .account-menu.open {
            display: block;
        }

        .account-menu .menu-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .account-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1ed760, #6ee7b7);
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .account-name {
            font-weight: 700;
        }

        .account-email {
            font-size: 12px;
            color: var(--text-muted);
        }

        .account-details {
            display: grid;
            gap: 8px;
            margin: 12px 0;
        }

        .account-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .account-row strong {
            color: #fff;
        }

        .logout-link {
            display: block;
            margin-top: 8px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: #ffb4b4;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header class="top-nav">
        <div class="nav-left">
            <a class="home-btn" href="user-dashbord.php">
                <i class="fas fa-house"></i>
                Home
            </a>

            <div class="nav-arrows">
                <button type="button" class="nav-arrow-btn" aria-label="Go back" data-nav="back">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button type="button" class="nav-arrow-btn" aria-label="Go forward" data-nav="forward">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <form class="top-search" action="search.php" method="get">
            <div class="search-shell">
                <i class="fas fa-search"></i>
                <input
                    type="text"
                    name="q"
                    placeholder="What do you want to play?"
                    aria-label="Search songs, albums, artists"
                >
                <button type="submit" aria-label="Search">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
            </div>
        </form>

        <div class="user-actions">
            <a class="premium-btn" href="subscriptions.php">Explore Premium</a>

            <div class="user-menu-wrap">
                <button class="user-icon-btn" type="button" aria-label="Account information">
                    <i class="fas fa-user"></i>
                </button>

                <div class="account-menu" aria-live="polite">
                    <div class="menu-header">
                        <div class="account-avatar">
                            <?php echo strtoupper(substr(e($_SESSION['username'] ?? 'U'), 0, 1)); ?>
                        </div>
                        <div>
                            <div class="account-name">
                                <?php echo e($_SESSION['username'] ?? 'User'); ?>
                            </div>
                            <div class="account-email">
                                <?php echo e($_SESSION['email'] ?? 'No email found'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="account-details">
                        <div class="account-row">
                            <span>Plan</span>
                            <strong><?php echo e($_SESSION['subscription_type'] ?? 'Free'); ?></strong>
                        </div>
                        <div class="account-row">
                            <span>Country</span>
                            <strong><?php echo e($_SESSION['country'] ?? 'Not set'); ?></strong>
                        </div>
                    </div>

                    <a class="logout-link" href="../backend/logout.php">Log out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content page">
        <p>
            <a href="playlists.php">Playlists</a>
        </p>

        <section class="panel playlist-panel">
            <h1><?php echo e($playlist['playlist_name']); ?></h1>
            <p class="muted playlist-visibility">
                <?php echo $playlist['is_public'] ? 'Public' : 'Private'; ?>
            </p>

            <?php if ($owner): ?>
                <form class="playlist-form" method="post" action="api.php" data-dashboard-form>
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                    <input type="hidden" name="action" value="rename_playlist">
                    <input type="hidden" name="playlist_id" value="<?php echo $playlistId; ?>">
                    <input type="hidden" name="redirect" value="playlist.php?id=<?php echo $playlistId; ?>">
                    <input
                        name="playlist_name"
                        value="<?php echo e($playlist['playlist_name']); ?>"
                        required
                    >
                    <button>Rename</button>
                </form>

                <form class="playlist-form" method="post" action="api.php" data-dashboard-form>
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                    <input type="hidden" name="action" value="add_to_playlist">
                    <input type="hidden" name="playlist_id" value="<?php echo $playlistId; ?>">
                    <input type="hidden" name="redirect" value="playlist.php?id=<?php echo $playlistId; ?>">
                    <select name="track_id" required>
                        <?php foreach ($allTracks as $candidate): ?>
                            <option value="<?php echo (int) $candidate['track_id']; ?>">
                                <?php echo e($candidate['title']); ?>
                                - <?php echo e($candidate['artist_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button>Add track</button>
                </form>
            <?php endif; ?>

            <?php foreach ($tracks as $track): ?>
                <div class="row playlist-track-row">
                    <a href="track.php?id=<?php echo (int) $track['track_id']; ?>">
                        <?php echo (int) $track['track_order']; ?>.
                        <?php echo e($track['title']); ?>
                        <span class="muted">
                            - <?php echo e($track['artist_name']); ?>
                        </span>
                    </a>

                    <span class="muted">
                        <?php echo (int) $track['duration_seconds']; ?> sec

                        <?php if ($owner): ?>
                            <form class="inline" method="post" action="api.php" data-dashboard-form>
                                <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                                <input type="hidden" name="action" value="remove_from_playlist">
                                <input type="hidden" name="playlist_id" value="<?php echo $playlistId; ?>">
                                <input type="hidden" name="track_id" value="<?php echo (int) $track['track_id']; ?>">
                                <input type="hidden" name="redirect" value="playlist.php?id=<?php echo $playlistId; ?>">
                                <button>Remove</button>
                            </form>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <?php if (!$tracks): ?>
                <p class="muted">This playlist is empty.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
