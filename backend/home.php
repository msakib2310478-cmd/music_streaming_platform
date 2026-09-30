<?php
require_once __DIR__ . '/functions.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$userId = requireUser();

$queries = [
    'weekly' => "SELECT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name, COUNT(sh.stream_id) AS metric FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id WHERE sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 7 DAY GROUP BY t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name ORDER BY metric DESC, t.title LIMIT 10",
    'trending' => "SELECT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name, SUM(sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 7 DAY) AS recent_plays, SUM(sh.played_at < CURRENT_TIMESTAMP - INTERVAL 7 DAY) AS previous_plays, ROUND((SUM(sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 7 DAY) - SUM(sh.played_at < CURRENT_TIMESTAMP - INTERVAL 7 DAY)) / GREATEST(SUM(sh.played_at < CURRENT_TIMESTAMP - INTERVAL 7 DAY), 1) * 100, 0) AS growth_percent FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id WHERE sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 14 DAY GROUP BY t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name HAVING recent_plays > 0 ORDER BY growth_percent DESC, recent_plays DESC, t.title LIMIT 10",
    'played' => "SELECT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name, COUNT(sh.stream_id) AS metric FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id GROUP BY t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name ORDER BY metric DESC, t.title LIMIT 10",
    'liked' => "SELECT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name, COUNT(f.user_id) AS metric FROM favorites f JOIN tracks t ON t.track_id = f.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id GROUP BY t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name ORDER BY metric DESC, t.title LIMIT 10",
    'rated' => "SELECT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name, AVG(r.rating) AS average_rating, COUNT(r.user_id) AS rating_count FROM ratings r JOIN tracks t ON t.track_id = r.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id GROUP BY t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name HAVING COUNT(r.user_id) >= 2 ORDER BY average_rating DESC, rating_count DESC, t.title LIMIT 10",
    'recent' => "SELECT DISTINCT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name, MAX(sh.played_at) AS last_played FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id WHERE sh.user_id = :user_id GROUP BY t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name ORDER BY last_played DESC LIMIT 10",
    'latest' => "SELECT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name FROM tracks t JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id ORDER BY t.track_id DESC LIMIT 10",
];
$sections = [];
foreach ($queries as $name => $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($name === 'recent' ? ['user_id' => $userId] : []);
    $sections[$name] = $stmt->fetchAll();
}
foreach ($sections as &$sectionTracks) {
    foreach ($sectionTracks as &$track) {
        $track['cover_image'] = coverImageUrl($track['cover_image'] ?? null);
    }
    unset($track);
}
unset($sectionTracks);
$recentTrackIds = array_column($sections['recent'], 'track_id');
foreach ($sections['latest'] as $track) {
    if (!in_array($track['track_id'], $recentTrackIds, true)) {
        $sections['recent'][] = $track;
    }
}
$dashboardTrackIds = [];
foreach ($sections as $sectionTracks) {
    foreach ($sectionTracks as $track) {
        $dashboardTrackIds[] = (int) $track['track_id'];
    }
}
$dashboardTrackIds = array_values(array_unique($dashboardTrackIds));
if ($dashboardTrackIds) {
    $trackIdParameters = [];
    $trackIdPlaceholders = [];
    foreach ($dashboardTrackIds as $index => $trackId) {
        $placeholder = ':track_id_' . $index;
        $trackIdPlaceholders[] = $placeholder;
        $trackIdParameters[$placeholder] = $trackId;
    }
    $artistIdStmt = $pdo->prepare('SELECT t.track_id, al.artist_id FROM tracks t JOIN albums al ON al.album_id = t.album_id WHERE t.track_id IN (' . implode(', ', $trackIdPlaceholders) . ')');
    $artistIdStmt->execute($trackIdParameters);
    $artistIdsByTrack = array_column($artistIdStmt->fetchAll(), 'artist_id', 'track_id');
    foreach ($sections as &$sectionTracks) {
        foreach ($sectionTracks as &$track) {
            $track['artist_id'] = (int) ($artistIdsByTrack[$track['track_id']] ?? 0);
        }
        unset($track);
    }
    unset($sectionTracks);
}
$featuredTrack = $sections['recent'][0] ?? $sections['trending'][0] ?? $sections['latest'][0] ?? null;
$newReleases = $pdo->query("SELECT al.album_id, al.title, al.cover_image, al.release_date, ar.artist_name FROM albums al JOIN artists ar ON ar.artist_id = al.artist_id WHERE al.release_date IS NOT NULL ORDER BY al.release_date DESC LIMIT 8")->fetchAll();
$newReleases = array_map(static function (array $album): array {
    $album['cover_image'] = coverImageUrl($album['cover_image'] ?? null);
    return $album;
}, $newReleases);
$ratedAlbums = $pdo->query("SELECT al.album_id, al.title, al.cover_image, ar.artist_name, AVG(r.rating) average_rating, COUNT(r.user_id) rating_count FROM albums al JOIN artists ar ON ar.artist_id = al.artist_id JOIN tracks t ON t.album_id = al.album_id JOIN ratings r ON r.track_id = t.track_id GROUP BY al.album_id, al.title, al.cover_image, ar.artist_name HAVING COUNT(r.user_id) >= 2 ORDER BY average_rating DESC, rating_count DESC LIMIT 8")->fetchAll();
$popularArtists = $pdo->query("SELECT ar.artist_id, ar.artist_name, ar.profile_image, COUNT(DISTINCT af.user_id) follower_count, COUNT(DISTINCT sh.stream_id) stream_count FROM artists ar LEFT JOIN albums al ON al.artist_id = ar.artist_id LEFT JOIN tracks t ON t.album_id = al.album_id LEFT JOIN stream_history sh ON sh.track_id = t.track_id LEFT JOIN artist_follows af ON af.artist_id = ar.artist_id GROUP BY ar.artist_id, ar.artist_name, ar.profile_image ORDER BY stream_count DESC, follower_count DESC LIMIT 8")->fetchAll();
$ratedArtists = $pdo->query("SELECT ar.artist_id, ar.artist_name, AVG(r.rating) average_rating, COUNT(r.user_id) rating_count FROM artists ar JOIN albums al ON al.artist_id = ar.artist_id JOIN tracks t ON t.album_id = al.album_id JOIN ratings r ON r.track_id = t.track_id GROUP BY ar.artist_id, ar.artist_name HAVING COUNT(r.user_id) >= 2 ORDER BY average_rating DESC, rating_count DESC LIMIT 8")->fetchAll();
$genres = $pdo->query('SELECT genre_id, genre_name FROM genres ORDER BY genre_name')->fetchAll();
$playlistsStmt = $pdo->prepare('SELECT playlist_id, playlist_name FROM playlists WHERE user_id = :user_id ORDER BY playlist_name');
$playlistsStmt->execute(['user_id' => $userId]);
$playlists = $playlistsStmt->fetchAll();
$favoriteCountStmt = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = :user_id');
$favoriteCountStmt->execute(['user_id' => $userId]);
$favoriteCount = (int)$favoriteCountStmt->fetchColumn();
$favoritesStmt = $pdo->prepare('SELECT track_id FROM favorites WHERE user_id = :user_id');
$favoritesStmt->execute(['user_id' => $userId]);
$favoriteTrackIds = array_fill_keys(array_map('strval', $favoritesStmt->fetchAll(PDO::FETCH_COLUMN, 0)), true);
$notificationCountStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
$notificationCountStmt->execute(['user_id' => $userId]);
$notificationCount = (int) $notificationCountStmt->fetchColumn();
$message = $_SESSION['success'] ?? $_SESSION['error'] ?? '';
$messageType = isset($_SESSION['error']) ? 'error' : 'success';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FulseFLow Dashboard</title>
    <link rel="stylesheet" href="../frontend/user-dashbord.css?v=20260925">
    <link rel="stylesheet" href="../frontend/reporting.css?v=20260925">
    <link rel="stylesheet" href="../frontend/profile.css?v=20260930">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .feature-bar { display:flex; gap:10px; flex-wrap:wrap; margin:14px 0 24px; }
        .feature-bar a, .genre-pill { padding:9px 13px; border:1px solid var(--border); border-radius:999px; color:var(--text-muted); text-decoration:none; }
        .feature-bar a:hover, .genre-pill:hover { color:var(--text-white); border-color:var(--accent); }
        .metric { color:var(--text-muted); font-size:.8rem; }
        .card-actions { display:flex; gap:8px; align-items:center; }
        .card-actions form { display:inline; }
        .small-action { background:none; border:0; color:var(--text-muted); cursor:pointer; padding:4px; }
        .small-action:hover { color:var(--accent); }
        .alert { padding:12px 16px; border-radius:10px; margin-bottom:16px; background:rgba(30,215,96,.14); }
        .alert.error { background:rgba(239,68,68,.16); }
        .genre-list { display:flex; gap:10px; flex-wrap:wrap; }
        .album-row { display:flex; gap:14px; align-items:center; padding:10px 0; border-bottom:1px solid var(--border); color:inherit; text-decoration:none; }
        .album-row img { width:56px; height:56px; object-fit:cover; border-radius:8px; }
        .empty { color:var(--text-muted); }
        .search-result-group { display:block; margin:0 0 28px; padding:24px; background:rgba(255,255,255,0.035); border:1px solid var(--border); border-radius:14px; }
        .search-result-group h2 { margin:0 0 18px; line-height:1.2; }
        .search-result-row { display:block; min-height:0; padding:14px 4px; border-bottom:1px solid var(--border); line-height:1.5; }
        .search-result-row:last-child { border-bottom:0; }
        .nav-left { display:flex; align-items:center; gap:14px; }
        .home-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:999px; background:rgba(255,255,255,0.06); color:var(--text-white); text-decoration:none; border:1px solid var(--border); font-weight:700; }
        .home-btn:hover { background:rgba(255,255,255,0.1); }
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
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .top-search { flex:1; display:flex; justify-content:flex-start; max-width: 720px; margin:0 0 0 0; }
        .search-shell { width:100%; display:flex; align-items:center; gap:12px; padding:10px 16px; border-radius:999px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); box-shadow:inset 0 1px 0 rgba(255,255,255,0.04); }
        .search-shell i { color:var(--text-muted); font-size:16px; }
        .search-shell input { flex:1; border:none; outline:none; background:transparent; color:var(--text-white); font-size:14px; }
        .search-shell input::placeholder { color:var(--text-muted); }
        .search-shell button { background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:15px; }
        .nav-arrows { display:flex; align-items:center; gap:8px; }
        .nav-arrow-btn {
            width: 36px;
            height: 36px;
            border:none;
            border-radius:50%;
            background: rgba(255,255,255,0.08);
            color: var(--text-white);
            display:flex;
            align-items:center;
            justify-content:center;
            cursor: pointer;
            padding:0;
        }
        .nav-arrow-btn:hover { background: rgba(255,255,255,0.14); cursor: pointer; }
        .user-menu-wrap { position:relative; display:flex; align-items:center; }
        .user-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.06);
            color: var(--text-white);
            display:flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            padding:0;
        }
        .user-icon-btn:hover { border-color: var(--accent); }
        .account-menu {
            position:absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 240px;
            background: rgba(18,18,18,0.98);
            border:1px solid rgba(255,255,255,0.12);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.4);
            padding: 16px;
            display:none;
            z-index:40;
        }
        .account-menu.open { display:block; }
        .account-menu .menu-header {
            display:flex;
            align-items:center;
            gap:12px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .account-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b57, #63d9d1);
            color: #111;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight: 800;
        }
        .account-name { font-weight:700; }
        .account-email { font-size:12px; color:var(--text-muted); }
        .account-details { display:grid; gap:8px; margin: 12px 0; }
        .account-row {
            display:flex;
            justify-content:space-between;
            gap:12px;
            font-size: 13px;
            color: var(--text-muted);
        }
        .account-row strong { color: var(--text-white); }
        .logout-link {
            display:block;
            margin-top:8px;
            padding-top:12px;
            border-top:1px solid rgba(255,255,255,0.08);
            color: #ffb4b4;
            text-decoration:none;
        }
        .main-content { padding-top: 0 !important; }
        .greeting-section { margin-top: 8px; }
        .top-nav { background: rgba(10, 15, 31, .72); border-color: rgba(160, 174, 192, .14); box-shadow: 0 12px 30px rgba(0, 0, 0, .18); }
        .search-shell { background: rgba(17, 24, 39, .78); border-color: rgba(160, 174, 192, .18); }
        .account-menu { background: rgba(17, 24, 39, .96); border-color: rgba(160, 174, 192, .18); }
        .premium-btn { background: var(--accent); color: #180d0a; }
        .sidebar-profile { display:flex; align-items:center; gap:11px; margin:4px 4px 18px; padding:12px; border:1px solid rgba(160,174,192,.14); border-radius:14px; background:linear-gradient(135deg, rgba(255,107,74,.14), rgba(17,24,39,.7)); }
        .sidebar-profile-copy { display:grid; gap:3px; min-width:0; flex:1; }
        .sidebar-profile-copy strong { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .sidebar-profile-copy span { color:var(--text-muted); font-size:.75rem; }
        .profile-avatar-small { width:36px; height:36px; display:grid; place-items:center; flex:0 0 auto; border-radius:11px; background:linear-gradient(135deg, var(--accent), var(--secondary-accent, #FF9966)); color:#180d0a; font-weight:900; }
        .notification-btn { width:30px; height:30px; display:grid; place-items:center; border:1px solid rgba(160,174,192,.16); border-radius:9px; background:rgba(255,255,255,.04); color:var(--text-muted); cursor:pointer; }
        .notification-btn:hover { color:var(--text-white); border-color:var(--accent); }
        .top-notification { width:38px; height:38px; display:grid; place-items:center; border:1px solid rgba(160,174,192,.16); border-radius:10px; background:rgba(17,24,39,.58); color:var(--text-muted); cursor:pointer; }
        .top-notification:hover { color:var(--text-white); border-color:var(--accent); }
        .notification-wrap { position:relative; }
        .notification-count { position:absolute; top:-5px; right:-5px; min-width:17px; height:17px; padding:0 4px; display:grid; place-items:center; border-radius:99px; background:var(--accent); color:#180d0a; font-size:.65rem; font-weight:900; }
        .notification-popover { position:absolute; top:calc(100% + 12px); right:0; width:300px; display:none; padding:12px; border:1px solid rgba(160,174,192,.18); border-radius:14px; background:rgba(17,24,39,.97); box-shadow:0 18px 42px rgba(0,0,0,.4); z-index:45; }
        .notification-popover.open { display:block; }
        .notification-popover h3 { margin:0 0 10px; font-size:.9rem; }
        .notification-item { display:block; padding:10px; border-radius:9px; color:var(--text-muted); font-size:.78rem; line-height:1.4; }
        .notification-item:hover { background:rgba(255,255,255,.05); color:var(--text-white); }
        .notification-empty { padding:14px 10px; color:var(--text-muted); font-size:.8rem; }
        .sidebar-premium { margin:0 4px 18px; padding:15px; border-radius:14px; background:linear-gradient(135deg, rgba(255,107,74,.22), rgba(255,153,102,.08)); border:1px solid rgba(255,107,74,.24); }
        .sidebar-premium strong { display:block; margin-bottom:5px; }
        .sidebar-premium span { display:block; color:var(--text-muted); font-size:.75rem; line-height:1.4; margin-bottom:11px; }
        .sidebar-premium a { display:inline-flex; padding:7px 10px; border-radius:8px; background:var(--accent); color:#180d0a; font-size:.75rem; font-weight:800; }
        .hero-banner { position:relative; display:grid; grid-template-columns:minmax(0, 1fr) 240px; gap:24px; min-height:310px; margin:4px 0 30px; padding:34px; overflow:hidden; border:1px solid rgba(160,174,192,.16); border-radius:24px; background:linear-gradient(120deg, rgba(255,107,74,.28), rgba(17,24,39,.9) 58%), radial-gradient(circle at 85% 20%, rgba(255,153,102,.25), transparent 34%); box-shadow:0 24px 60px rgba(0,0,0,.24); }
        .hero-banner::before { content:""; position:absolute; inset:-25%; background:var(--hero-image) center/cover; filter:blur(36px); opacity:.2; transform:scale(1.15); }
        .hero-copy, .hero-art { position:relative; z-index:1; }
        .hero-copy { align-self:end; max-width:680px; }
        .hero-eyebrow { margin:0 0 10px; color:#ffd2c7; font-size:.74rem; font-weight:800; letter-spacing:.16em; text-transform:uppercase; }
        .hero-copy h1 { margin:0; font-size:clamp(2rem, 4vw, 3.5rem); line-height:1.03; letter-spacing:-.02em; }
        .hero-copy p { margin:14px 0 4px; color:#ffe8e2; font-size:1rem; }
        .hero-track { margin:0; font-size:1.25rem; font-weight:800; }
        .hero-artist { margin:5px 0 0; color:#ffd2c7; }
        .hero-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:22px; }
        .hero-play { display:inline-flex; align-items:center; gap:9px; padding:12px 17px; border:0; border-radius:10px; background:#fff; color:#17101a; font-weight:800; cursor:pointer; }
        .hero-play:hover { transform:translateY(-2px); }
        .hero-secondary { display:inline-flex; align-items:center; gap:8px; padding:11px 15px; border:1px solid rgba(255,255,255,.22); border-radius:10px; color:#fff; background:rgba(17,24,39,.36); font-weight:700; }
        .hero-art { align-self:center; justify-self:end; width:min(100%, 230px); aspect-ratio:1; padding:9px; border-radius:18px; background:rgba(255,255,255,.13); transform:rotate(3deg); box-shadow:0 18px 34px rgba(0,0,0,.3); }
        .hero-art img { width:100%; height:100%; object-fit:cover; border-radius:12px; }
        @media (max-width:760px) { .hero-banner { grid-template-columns:1fr; padding:24px; } .hero-art { position:absolute; right:18px; top:18px; width:130px; opacity:.58; } .hero-copy { padding-top:100px; } }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false"><i class="fas fa-bars"></i></button>
    <div class="sidebar-scrim" aria-hidden="true"></div>
    <div class="main-container">
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-music"></i>
                <span>FulseFLow</span>
            </div>

            <div class="sidebar-profile">
                <div class="profile-avatar-small"><?php echo strtoupper(substr(e($_SESSION['username'] ?? 'U'), 0, 1)); ?></div>
                <div class="sidebar-profile-copy">
                    <strong><?php echo e($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Listener'); ?></strong>
                    <span>Personal listener</span>
                </div>
                <button class="notification-btn" type="button" aria-label="Notifications" title="Notifications"><i class="fas fa-bell"></i></button>
            </div>

            <div class="sidebar-premium">
                <strong>FulseFLow Plus</strong>
                <span>Unlock richer listening insights and a more personal mix.</span>
                <a href="subscriptions.php">Upgrade</a>
            </div>

            <nav class="nav-links">
                <a href="user-dashbord.php" class="active" data-dashboard-link><i class="fas fa-home"></i> Home</a>
                <a href="#search" data-search-trigger><i class="fas fa-search"></i> Search</a>
                <a href="favorites.php" data-dashboard-link><i class="fas fa-heart"></i> Favorites <span class="list-count" id="favorite-count"><?php echo $favoriteCount; ?></span></a>
                <a href="queue.php" data-dashboard-link><i class="fas fa-list-ol"></i> Up Next</a>
                <a href="playlists.php" data-dashboard-link><i class="fas fa-book"></i> Playlists</a>
                <a href="subscriptions.php" data-dashboard-link><i class="fas fa-crown"></i> Subscription</a>
                <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>

            <div class="library-box">
                <div class="library-header">
                    <h3>Your Playlists</h3>
                    <a class="icon-btn" href="playlists.php" data-dashboard-link aria-label="Manage playlists"><i class="fas fa-plus"></i></a>
                </div>

                <?php foreach ($playlists as $playlist): ?>
                    <a class="list-item" href="playlist.php?id=<?php echo (int)$playlist['playlist_id']; ?>" data-dashboard-link>
                        <span><?php echo e($playlist['playlist_name']); ?></span>
                    </a>
                <?php endforeach; ?>

                <?php if (!$playlists): ?>
                    <p class="empty">No playlists yet.</p>
                    <a class="playlist-create-link" href="playlists.php" data-dashboard-link>Create your first playlist</a>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-nav">
                <div class="nav-left">
                    <a class="home-btn" href="user-dashbord.php" data-dashboard-link><i class="fas fa-house"></i> Home</a>
                    <div class="nav-arrows">
                        <button type="button" class="nav-arrow-btn" aria-label="Go back" data-nav="back"><i class="fas fa-chevron-left"></i></button>
                        <button type="button" class="nav-arrow-btn" aria-label="Go forward" data-nav="forward"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>

                <form class="top-search" action="search.php" method="get" data-dashboard-search>
                    <div class="search-shell">
                        <input type="text" name="q" list="search-suggestions" placeholder="Search your sound" aria-label="Search songs, albums, artists" autocomplete="off">
                        <datalist id="search-suggestions"></datalist>
                        <button type="button" class="search-clear" aria-label="Clear search" title="Clear search" hidden><i class="fas fa-xmark"></i></button>
                        <button type="submit" aria-label="Search">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>

                <div class="user-actions">
                    <div class="notification-wrap">
                        <button class="top-notification" type="button" aria-label="Notifications" title="Notifications" data-notifications><i class="fas fa-bell"></i><?php if ($notificationCount): ?><span class="notification-count"><?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?></span><?php endif; ?></button>
                        <div class="notification-popover" data-notification-popover><h3>Notifications</h3><div data-notification-list><div class="notification-empty">Loading notifications...</div></div></div>
                    </div>
                    <a class="badge-btn premium-btn" href="subscriptions.php">FulseFLow Plus</a>
                    <div class="user-menu-wrap">
                        <button class="user-icon-btn" type="button" aria-label="Account information">
                            <i class="fas fa-user"></i>
                        </button>
                        <div class="account-menu" aria-live="polite">
                            <div class="menu-header">
                                <div class="account-avatar"><?php echo strtoupper(substr(e($_SESSION['username'] ?? 'U'), 0, 1)); ?></div>
                                <div>
                                    <div class="account-name"><?php echo e($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'User'); ?></div>
                                    <div class="account-email"><?php echo e($_SESSION['email'] ?? 'No email found'); ?></div>
                                </div>
                            </div>
                            <div class="account-details">
                                <div class="account-row"><span>Plan</span><strong><?php echo e($_SESSION['subscription_type'] ?? 'Free'); ?></strong></div>
                                <div class="account-row"><span>Country</span><strong><?php echo e($_SESSION['country'] ?? 'Not set'); ?></strong></div>
                            </div>
                            <a class="profile-link" href="profile.php" data-dashboard-link>View profile</a>
                            <a class="logout-link" href="../backend/logout.php">Log out</a>
                        </div>
                    </div>
                </div>
            </header>

            <div id="dashboard-view">
                <?php if ($featuredTrack): ?>
                    <section class="hero-banner" style="--hero-image: url('<?php echo e($featuredTrack['cover_image']); ?>');">
                        <div class="hero-copy">
                            <p class="hero-eyebrow">Your listening space</p>
                            <h1>Good evening, <?php echo e($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Listener'); ?></h1>
                            <p>Continue listening</p>
                            <p class="hero-track"><?php echo e($featuredTrack['title']); ?></p>
                            <p class="hero-artist"><?php echo e($featuredTrack['artist_name']); ?></p>
                            <div class="hero-actions">
                                <button class="hero-play play-track" type="button" data-track-id="<?php echo (int)$featuredTrack['track_id']; ?>" data-audio-url="<?php echo e($featuredTrack['audio_url']); ?>" data-title="<?php echo e($featuredTrack['title']); ?>" data-artist="<?php echo e($featuredTrack['artist_name']); ?>" data-artist-id="<?php echo (int)($featuredTrack['artist_id'] ?? 0); ?>"><i class="fas fa-play"></i> Play now</button>
                                <a class="hero-secondary" href="recently-played.php" data-dashboard-link><i class="fas fa-clock-rotate-left"></i> View history</a>
                            </div>
                        </div>
                        <a class="hero-art play-track" href="track.php?id=<?php echo (int)$featuredTrack['track_id']; ?>" data-track-id="<?php echo (int)$featuredTrack['track_id']; ?>" data-audio-url="<?php echo e($featuredTrack['audio_url']); ?>" data-title="<?php echo e($featuredTrack['title']); ?>" data-artist="<?php echo e($featuredTrack['artist_name']); ?>" data-artist-id="<?php echo (int)($featuredTrack['artist_id'] ?? 0); ?>" aria-label="Play <?php echo e($featuredTrack['title']); ?>">
                            <img src="<?php echo e($featuredTrack['cover_image']); ?>" alt="<?php echo e($featuredTrack['title']); ?> cover">
                        </a>
                    </section>
                <?php endif; ?>
                <?php if ($message): ?>
                    <div class="alert <?php echo $messageType === 'error' ? 'error' : ''; ?>"><?php echo e($message); ?></div>
                <?php endif; ?>

                <section class="greeting-section">
                <h2>Welcome back, <?php echo e($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'User'); ?></h2>
                <div class="feature-bar">
                    <a href="recently-played.php" data-dashboard-link>Recently Played</a>
                    <a href="queue.php" data-dashboard-link>Up Next</a>
                    <a href="followed-artists.php" data-dashboard-link>Followed Artists</a>
                    <a href="recommendations.php" data-dashboard-link>Recommended For You</a>
                    <a href="analytics.php" data-dashboard-link>Listening Stats</a>
                    <a href="charts.php" data-dashboard-link>Global Charts</a>
                    <a href="charts.php?period=trending" data-dashboard-link>Trending Now</a>
                </div>
                </section>

            <section class="content-section">
                <div class="section-header">
                    <h2>Browse by Genre</h2>
                </div>
                <div class="genre-list">
                    <?php foreach ($genres as $genre): ?>
                        <a class="genre-pill" href="genre.php?id=<?php echo (int)$genre['genre_id']; ?>" data-dashboard-link><?php echo e($genre['genre_name']); ?></a>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php foreach ([['weekly','Top 100 Songs This Week'], ['trending','Trending Songs'], ['played','Most Played'], ['liked','Most Liked'], ['rated','Highest Rated Songs'], ['recent','Recently Played']] as [$key, $title]): ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2><?php echo $title; ?></h2>
                        <?php if ($key === 'recent'): ?>
                            <a href="recently-played.php">View all</a>
                        <?php endif; ?>
                    </div>

                    <div class="card-grid">
                        <?php foreach ($sections[$key] as $track): ?>
                            <article class="card">
                                <a href="track.php?id=<?php echo (int)$track['track_id']; ?>" class="play-track" data-track-id="<?php echo (int)$track['track_id']; ?>" data-audio-url="<?php echo e($track['audio_url']); ?>" data-title="<?php echo e($track['title']); ?>" data-artist="<?php echo e($track['artist_name']); ?>" data-artist-id="<?php echo (int)$track['artist_id']; ?>">
                                    <img src="<?php echo e($track['cover_image'] ?: 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=400&q=80'); ?>" alt="<?php echo e($track['title']); ?> cover">
                                </a>
                                <h4><a href="track.php?id=<?php echo (int)$track['track_id']; ?>" data-dashboard-link><?php echo e($track['title']); ?></a></h4>
                                <p><?php echo e($track['artist_name']); ?></p>
                                <div class="card-actions">
                                    <button class="small-action play-track" data-track-id="<?php echo (int)$track['track_id']; ?>" data-audio-url="<?php echo e($track['audio_url']); ?>" data-title="<?php echo e($track['title']); ?>" data-artist="<?php echo e($track['artist_name']); ?>" data-artist-id="<?php echo (int)$track['artist_id']; ?>" title="Play"><i class="fas fa-play"></i></button>
                                    <form action="api.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                                        <input type="hidden" name="action" value="favorite">
                                        <input type="hidden" name="track_id" value="<?php echo (int)$track['track_id']; ?>">
                                        <input type="hidden" name="redirect" value="user-dashbord.php">
                                        <button class="small-action" title="Favorite"><i class="fas fa-heart"></i></button>
                                    </form>
                                    <form action="api.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                                        <input type="hidden" name="action" value="queue_add">
                                        <input type="hidden" name="track_id" value="<?php echo (int)$track['track_id']; ?>">
                                        <input type="hidden" name="redirect" value="user-dashbord.php">
                                        <button class="small-action" title="Add to Up Next" aria-label="Add to Up Next"><i class="fas fa-list-ol"></i></button>
                                    </form>
                                    <?php if (isset($track['metric'])): ?>
                                        <span class="metric"><?php echo (int)$track['metric']; ?> plays</span>
                                    <?php elseif (isset($track['average_rating'])): ?>
                                        <span class="metric">★ <?php echo number_format((float)$track['average_rating'], 1); ?></span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>

                        <?php if (!$sections[$key]): ?>
                            <div class="empty-state">
                                <strong><?php echo $key === 'liked' ? 'No favorites yet.' : ($key === 'recent' ? 'Nothing played yet.' : 'This section is waiting for your listening history.'); ?></strong>
                                <a href="<?php echo $key === 'liked' ? 'search.php' : 'recommendations.php'; ?>" data-dashboard-link><?php echo $key === 'liked' ? 'Explore music' : 'Listen to a few tracks'; ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <section class="content-section">
                <div class="section-header">
                    <h2>New Releases</h2>
                </div>
                <div>
                    <?php foreach ($newReleases as $album): ?>
                        <a class="album-row" href="album.php?id=<?php echo (int)$album['album_id']; ?>">
                            <img src="<?php echo e($album['cover_image'] ?: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=200&q=80'); ?>" alt="">
                            <span>
                                <strong><?php echo e($album['title']); ?></strong><br>
                                <span class="metric"><?php echo e($album['artist_name']); ?> · <?php echo e($album['release_date']); ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            </div>
        </main>
    </div>

    <footer class="music-player">
        <div class="now-playing">
            <img id="player-cover" src="https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=200&q=80" alt="Current track cover">
            <div class="track-meta">
                <div class="track-info">
                    <h5><a id="player-title" href="#">Select a track</a></h5>
                    <p><a id="player-artist" href="#">Nothing playing</a></p>
                </div>
                <form class="player-favorite-form" method="post" action="api.php" data-player-favorite>
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                    <input type="hidden" name="action" value="favorite">
                    <input type="hidden" name="track_id" value="0">
                    <input type="hidden" name="redirect" value="user-dashbord.php">
                    <button type="submit" class="like-toggle" title="Add to liked songs" aria-label="Add to liked songs">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="player-controls">
            <div class="control-buttons">
                <button class="small-action" id="player-prev" title="Previous" aria-label="Previous track" disabled><i class="fas fa-backward-step"></i></button>
                <button class="small-action" id="player-shuffle" title="Shuffle off" aria-label="Shuffle off" aria-pressed="false"><i class="fas fa-shuffle"></i></button>
                <button class="small-action" id="player-play" title="Play or pause"><i class="fas fa-circle-play main-play"></i></button>
                <button class="small-action" id="player-repeat" title="Repeat off" aria-label="Repeat off" aria-pressed="false"><i class="fas fa-repeat"></i></button>
                <button class="small-action" id="player-next" title="Next" aria-label="Next track" disabled><i class="fas fa-forward-step"></i></button>
            </div>
            <div class="progress-bar-container">
                <span id="player-current">0:00</span>
                <input id="player-progress" type="range" min="0" max="100" value="0" aria-label="Playback progress">
                <span id="player-duration">0:00</span>
            </div>
        </div>

        <div class="volume-controls">
            <i class="fas fa-volume-low"></i>
            <input id="player-volume" type="range" min="0" max="100" value="70" aria-label="Volume">
        </div>

        <audio id="audio-player" preload="metadata"></audio>
    </footer>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <script>
        const audio = document.getElementById('audio-player');
        const progress = document.getElementById('player-progress');
        const volume = document.getElementById('player-volume');
        const favoriteForm = document.querySelector('[data-player-favorite]');
        const favoriteTrackInput = favoriteForm?.querySelector('input[name="track_id"]');
        const favoriteButton = favoriteForm?.querySelector('.like-toggle');
        const playerPlayButton = document.getElementById('player-play');
        const playerPlayIcon = playerPlayButton?.querySelector('i');
        const playerPreviousButton = document.getElementById('player-prev');
        const playerNextButton = document.getElementById('player-next');
        const playerShuffleButton = document.getElementById('player-shuffle');
        const playerRepeatButton = document.getElementById('player-repeat');
        const toast = document.getElementById('toast');
        const dashboardView = document.getElementById('dashboard-view');
        const csrfToken = '<?php echo e(csrfToken()); ?>';
        const userPlaylists = <?php echo json_encode(array_map(static fn (array $playlist): array => ['id' => (int)$playlist['playlist_id'], 'name' => $playlist['playlist_name']], $playlists), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        let dashboardRequest = 0;
        let currentTrack = 0;
        let streamSent = false;
        let currentPlayButton = null;
        let shuffleEnabled = false;
        let repeatEnabled = false;
        let toastTimer = null;
        const likedTrackIds = new Set(<?php echo json_encode(array_keys($favoriteTrackIds)); ?>);

        const showToast = message => {
            if (!toast) return;
            toast.textContent = message;
            toast.classList.add('visible');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('visible'), 2600);
        };

        const availablePlayButtons = () => Array.from(document.querySelectorAll('.play-track[data-audio-url]'));

        const enhancePlaylistActions = () => {
            document.querySelectorAll('.play-track[data-track-id]').forEach(playButton => {
                const card = playButton.closest('.card');
                if (!card || card.querySelector('.playlist-add-form, .playlist-create-link')) return;

                let actions = card.querySelector('.card-actions');
                if (!actions) {
                    actions = document.createElement('div');
                    actions.className = 'card-actions';
                    card.append(actions);
                }

                if (!userPlaylists.length) {
                    const createLink = document.createElement('a');
                    createLink.className = 'playlist-create-link';
                    createLink.href = 'playlists.php';
                    createLink.setAttribute('data-dashboard-link', '');
                    createLink.textContent = 'Create a playlist';
                    actions.append(createLink);
                    return;
                }

                const form = document.createElement('form');
                form.className = 'playlist-add-form';
                form.action = 'api.php';
                form.method = 'post';
                form.title = 'Add to playlist';
                form.innerHTML = `<input type="hidden" name="csrf_token" value="${csrfToken}"><input type="hidden" name="action" value="add_to_playlist"><input type="hidden" name="track_id" value="${playButton.dataset.trackId}"><input type="hidden" name="redirect" value="user-dashbord.php">`;

                const select = document.createElement('select');
                select.name = 'playlist_id';
                select.required = true;
                select.setAttribute('aria-label', `Add ${playButton.dataset.title || 'track'} to playlist`);
                const prompt = document.createElement('option');
                prompt.value = '';
                prompt.textContent = 'Add to playlist';
                prompt.disabled = true;
                prompt.selected = true;
                select.append(prompt);
                userPlaylists.forEach(playlist => {
                    const option = document.createElement('option');
                    option.value = playlist.id;
                    option.textContent = playlist.name;
                    select.append(option);
                });
                form.append(select);

                const submit = document.createElement('button');
                submit.type = 'submit';
                submit.className = 'playlist-add-submit';
                submit.setAttribute('aria-label', 'Add to selected playlist');
                submit.innerHTML = '<i class="fas fa-plus"></i>';
                form.append(submit);
                actions.append(form);
            });
        };

        const updateTransportState = () => {
            const buttons = availablePlayButtons();
            const currentIndex = currentPlayButton ? buttons.indexOf(currentPlayButton) : -1;
            const hasTracks = buttons.length > 0;
            playerPreviousButton.disabled = !hasTracks || (!repeatEnabled && currentIndex <= 0);
            playerNextButton.disabled = !hasTracks || (!repeatEnabled && currentIndex === buttons.length - 1);
            playerShuffleButton.setAttribute('aria-pressed', shuffleEnabled ? 'true' : 'false');
            playerShuffleButton.setAttribute('aria-label', shuffleEnabled ? 'Shuffle on' : 'Shuffle off');
            playerShuffleButton.title = shuffleEnabled ? 'Shuffle on' : 'Shuffle off';
            playerRepeatButton.setAttribute('aria-pressed', repeatEnabled ? 'true' : 'false');
            playerRepeatButton.setAttribute('aria-label', repeatEnabled ? 'Repeat on' : 'Repeat off');
            playerRepeatButton.title = repeatEnabled ? 'Repeat on' : 'Repeat off';
        };

        const updateFavoriteButtonState = () => {
            const playerTrackId = String(favoriteTrackInput?.value || '0');
            document.querySelectorAll('.like-toggle').forEach(button => {
                const trackId = playerTrackId;
                const isLiked = likedTrackIds.has(trackId) && trackId !== '0';
                button.classList.toggle('liked', isLiked);
                const icon = button.querySelector('i');
                icon?.classList.toggle('fa-solid', isLiked);
                icon?.classList.toggle('fa-regular', !isLiked);
                button.setAttribute('aria-pressed', isLiked ? 'true' : 'false');
                button.title = isLiked ? 'Remove from liked songs' : 'Add to liked songs';
                button.setAttribute('aria-label', button.title);
            });
        };

        const setActiveDashboardLink = url => {
            const target = new URL(url, window.location.href).pathname;
            document.querySelectorAll('[data-dashboard-link]').forEach(link => {
                link.classList.toggle('active', new URL(link.href, window.location.href).pathname === target);
            });
        };

        const loadDashboardView = async (url, pushState = true) => {
            const requestId = ++dashboardRequest;
            dashboardView.classList.add('is-loading');

            try {
                const response = await fetch(url, {
                    cache: 'no-store',
                    headers: { 'X-Requested-With': 'dashboard-view' }
                });
                const documentView = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextView = documentView.querySelector('#dashboard-view, .main-content.page, main');
                if (!nextView) throw new Error('Dashboard view is unavailable.');
                if (requestId !== dashboardRequest) return;

                const reportClass = ['charts', 'analytics', 'artist', 'queue-page'].find(className => nextView.classList.contains(className));
                dashboardView.innerHTML = reportClass
                    ? `<div class="reporting-shell ${reportClass}-shell">${nextView.innerHTML}</div>`
                    : nextView.innerHTML;
                dashboardView.querySelectorAll('.top-nav').forEach(header => header.remove());
                const embeddedBreadcrumb = dashboardView.querySelector('p:first-child a[href="user-dashbord.php"]');
                embeddedBreadcrumb?.parentElement.remove();
                setActiveDashboardLink(url);
                if (pushState) history.pushState({ dashboardUrl: url }, '', url);
                dashboardView.scrollTop = 0;
                enhancePlaylistActions();
                updateTransportState();
            } catch (error) {
                dashboardView.innerHTML = '<div class="alert error">Unable to load this section. Please try again.</div>';
            } finally {
                dashboardView.classList.remove('is-loading');
            }
        };

        document.addEventListener('click', event => {
            const searchTrigger = event.target.closest('[data-search-trigger]');
            if (searchTrigger && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
                event.preventDefault();
                const searchInput = document.querySelector('[data-dashboard-search] input[name="q"]');
                searchInput?.focus();
                return;
            }

            const link = event.target.closest('[data-dashboard-link]');
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            loadDashboardView(link.href);
        });

        document.addEventListener('error', event => {
            const image = event.target;
            if (!(image instanceof HTMLImageElement) || !image.closest('#dashboard-view, .music-player')) return;
            image.onerror = null;
            image.src = '../frontend/cover-placeholder.svg';
        }, true);

        document.addEventListener('submit', async event => {
            const form = event.target.closest('[data-dashboard-form]');
            if (!form) return;

            event.preventDefault();
            form.querySelectorAll('button').forEach(button => {
                button.disabled = true;
            });

            try {
                const response = await fetch(new URL(form.getAttribute('action'), window.location.href), {
                    method: form.method || 'POST',
                    body: new FormData(form),
                    cache: 'no-store',
                    redirect: 'follow'
                });

                if (response.redirected || response.ok) {
                    await loadDashboardView(window.location.href, false);
                } else {
                    throw new Error('Playlist update request failed.');
                }
            } catch (error) {
                dashboardView.innerHTML = '<div class="alert error">Unable to update this playlist. Please try again.</div>';
            } finally {
                form.querySelectorAll('button').forEach(button => {
                    button.disabled = false;
                });
            }
        });

        document.querySelector('[data-dashboard-search]').addEventListener('submit', event => {
            event.preventDefault();
            const form = event.currentTarget;
            const url = new URL(form.action, window.location.href);
            const query = new FormData(form).get('q');
            if (query) url.searchParams.set('q', query);
            loadDashboardView(url.href);
        });

        const searchInput = document.querySelector('[data-dashboard-search] input[name="q"]');
        const suggestionList = document.getElementById('search-suggestions');
        let suggestionTimer = null;
        let suggestionController = null;
        const clearSearchButton = document.querySelector('.search-clear');
        const updateSearchClear = () => { if (clearSearchButton) clearSearchButton.hidden = !searchInput.value; };
        clearSearchButton?.addEventListener('click', () => {
            searchInput.value = '';
            suggestionList.replaceChildren();
            updateSearchClear();
            searchInput.focus();
        });
        searchInput?.addEventListener('input', () => {
            clearTimeout(suggestionTimer);
            suggestionController?.abort();
            updateSearchClear();
            const query = searchInput.value.trim();
            if (query.length < 2) {
                suggestionList.replaceChildren();
                return;
            }
            suggestionTimer = setTimeout(async () => {
                try {
                    suggestionController = new AbortController();
                    const response = await fetch(`search_suggestions.php?q=${encodeURIComponent(query)}`, { credentials: 'same-origin', signal: suggestionController.signal });
                    if (!response.ok) return;
                    const suggestions = await response.json();
                    suggestionList.replaceChildren(...suggestions.map(suggestion => {
                        const option = document.createElement('option');
                        option.value = suggestion.label;
                        option.label = suggestion.type;
                        return option;
                    }));
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    suggestionList.replaceChildren();
                }
            }, 180);
        });
        updateSearchClear();

        window.addEventListener('popstate', event => {
            loadDashboardView(event.state?.dashboardUrl || window.location.href, false);
        });

        const formatTime = s => {
            s = Math.floor(s || 0);
            return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        };

        const playTrack = button => {
            if (!button.dataset.audioUrl) {
                alert('This demo track has no audio file yet.');
                return;
            }

            currentTrack = Number(button.dataset.trackId) || 0;
            currentPlayButton = button;
            streamSent = false;
            favoriteTrackInput.value = String(currentTrack);
            updateFavoriteButtonState();
            audio.src = button.dataset.audioUrl;
            const playerTitle = document.getElementById('player-title');
            const playerArtist = document.getElementById('player-artist');
            playerTitle.textContent = button.dataset.title;
            playerTitle.href = `track.php?id=${encodeURIComponent(currentTrack)}`;
            playerArtist.textContent = button.dataset.artist;
            if (button.dataset.artistId) {
                playerArtist.href = `artist.php?id=${encodeURIComponent(button.dataset.artistId)}`;
            } else {
                playerArtist.href = '#';
            }
            audio.play().catch(() => {});
            updateTransportState();
        };

        document.addEventListener('click', event => {
            const playButton = event.target.closest('.play-track');
            if (!playButton) return;
            event.preventDefault();
            playTrack(playButton);
        });

        document.addEventListener('click', async event => {
            const trackLink = event.target.closest('a[href*="track.php?id="]');
            if (!trackLink || !dashboardView.contains(trackLink) || trackLink.classList.contains('play-track')) return;

            event.preventDefault();
            try {
                const response = await fetch(trackLink.href, { cache: 'no-store', credentials: 'same-origin' });
                if (!response.ok) throw new Error('Track details could not be loaded.');
                const trackPage = new DOMParser().parseFromString(await response.text(), 'text/html');
                const audioSource = trackPage.querySelector('audio.track-audio')?.getAttribute('src');
                const trackHeading = trackPage.querySelector('.hero h1');
                const title = trackHeading?.textContent.trim();
                const artistLine = trackPage.querySelector('.hero .muted')?.textContent.trim() || '';
                const artist = artistLine.split('·')[0].trim();
                const trackId = new URL(trackLink.href).searchParams.get('id');
                if (!audioSource || !title) throw new Error('Track audio is unavailable.');

                playTrack({
                    dataset: {
                        trackId,
                        audioUrl: new URL(audioSource, response.url || trackLink.href).href,
                        title,
                        artist,
                        artistId: trackHeading.dataset.artistId,
                    },
                });
            } catch (error) {
                console.error('Unable to play selected track:', error);
            }
        });

        document.querySelectorAll('.nav-arrow-btn').forEach(button => {
            button.addEventListener('click', () => {
                if (button.dataset.nav === 'back') {
                    window.history.back();
                } else {
                    window.history.forward();
                }
            });
        });

        const userIcon = document.querySelector('.user-icon-btn');
        const accountMenu = document.querySelector('.account-menu');

        if (userIcon && accountMenu) {
            userIcon.addEventListener('click', (event) => {
                event.stopPropagation();
                accountMenu.classList.toggle('open');
            });

            document.addEventListener('click', (event) => {
                if (!accountMenu.contains(event.target) && !userIcon.contains(event.target)) {
                    accountMenu.classList.remove('open');
                }
            });
        }

        document.querySelector('.notification-btn')?.addEventListener('click', () => showToast('You are all caught up.'));
        const notificationButton = document.querySelector('[data-notifications]');
        const notificationPopover = document.querySelector('[data-notification-popover]');
        const notificationList = document.querySelector('[data-notification-list]');
        notificationButton?.addEventListener('click', async event => {
            event.stopPropagation();
            notificationPopover?.classList.toggle('open');
            if (!notificationPopover?.classList.contains('open')) return;
            try {
                const response = await fetch('notifications.php', { credentials: 'same-origin' });
                const data = await response.json();
                notificationList.replaceChildren();
                if (!data.items?.length) {
                    notificationList.innerHTML = '<div class="notification-empty">You are all caught up.</div>';
                } else {
                    data.items.forEach(item => {
                        const link = document.createElement('a');
                        link.className = 'notification-item';
                        link.href = item.link || '#';
                        link.textContent = item.message;
                        notificationList.append(link);
                    });
                    fetch('notifications.php', { method: 'POST', body: new URLSearchParams({ csrf_token: csrfToken }), credentials: 'same-origin' });
                    document.querySelector('.notification-count')?.remove();
                }
            } catch (error) {
                notificationList.innerHTML = '<div class="notification-empty">Notifications are unavailable.</div>';
            }
        });
        document.addEventListener('click', event => {
            if (notificationPopover && !notificationPopover.contains(event.target) && !notificationButton?.contains(event.target)) notificationPopover.classList.remove('open');
        });

        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const sidebarScrim = document.querySelector('.sidebar-scrim');
        const setMobileMenu = open => {
            document.body.classList.toggle('mobile-menu-open', open);
            mobileMenuToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        mobileMenuToggle?.addEventListener('click', () => setMobileMenu(!document.body.classList.contains('mobile-menu-open')));
        sidebarScrim?.addEventListener('click', () => setMobileMenu(false));
        document.querySelectorAll('.sidebar a').forEach(link => link.addEventListener('click', () => setMobileMenu(false)));

        playerPlayButton?.addEventListener('click', () => {
            if (!currentTrack) {
                showToast('Choose a track to start listening.');
                return;
            }
            audio.paused ? audio.play() : audio.pause();
        });

        const playAdjacentTrack = direction => {
            const buttons = availablePlayButtons();
            if (!buttons.length) {
                showToast('No playable tracks are available yet.');
                return;
            }

            const currentIndex = currentPlayButton ? buttons.indexOf(currentPlayButton) : -1;
            let nextIndex;
            if (shuffleEnabled) {
                const choices = buttons.filter(button => button !== currentPlayButton);
                nextIndex = buttons.indexOf(choices[Math.floor(Math.random() * choices.length)] || buttons[0]);
            } else {
                nextIndex = currentIndex + direction;
                if (nextIndex < 0 || nextIndex >= buttons.length) {
                    if (!repeatEnabled) return;
                    nextIndex = nextIndex < 0 ? buttons.length - 1 : 0;
                }
            }
            playTrack(buttons[nextIndex]);
        };

        playerPreviousButton?.addEventListener('click', () => playAdjacentTrack(-1));
        playerNextButton?.addEventListener('click', () => playAdjacentTrack(1));
        playerShuffleButton?.addEventListener('click', () => {
            shuffleEnabled = !shuffleEnabled;
            updateTransportState();
        });
        playerRepeatButton?.addEventListener('click', () => {
            repeatEnabled = !repeatEnabled;
            updateTransportState();
        });

        const updatePlayButtonState = () => {
            if (!playerPlayIcon) return;
            const isPlaying = !audio.paused && !audio.ended;
            playerPlayIcon.classList.toggle('fa-circle-play', !isPlaying);
            playerPlayIcon.classList.toggle('fa-circle-pause', isPlaying);
            playerPlayButton.setAttribute('aria-label', isPlaying ? 'Pause' : 'Play');
            playerPlayButton.setAttribute('title', isPlaying ? 'Pause' : 'Play');
        };

        audio.addEventListener('play', updatePlayButtonState);
        audio.addEventListener('pause', updatePlayButtonState);
        audio.addEventListener('ended', () => {
            updatePlayButtonState();
            if (repeatEnabled) {
                audio.currentTime = 0;
                audio.play().catch(() => {});
            } else {
                playAdjacentTrack(1);
            }
        });
        updatePlayButtonState();
        enhancePlaylistActions();
        updateTransportState();

        document.addEventListener('submit', async event => {
            const form = event.target.closest('.card-actions form, [data-player-favorite]');
            if (!form) return;
            event.preventDefault();

            const button = form.querySelector('button');
            if (button) button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'dashboard-action' }
                });
                const result = await response.json().catch(() => ({ ok: false }));
                if (!response.ok || !result.ok) throw new Error('Action failed.');

                const action = form.querySelector('input[name="action"]')?.value;
                const trackId = form.querySelector('input[name="track_id"]')?.value;
                if (action === 'favorite' && trackId) {
                    if (likedTrackIds.has(String(trackId))) likedTrackIds.delete(String(trackId));
                    else likedTrackIds.add(String(trackId));
                    updateFavoriteButtonState();
                }
                showToast(action === 'queue_add' ? 'Added to Up Next.' : action === 'favorite' ? 'Favorites updated.' : 'Action completed.');
            } catch (error) {
                showToast('Unable to complete that action.');
            } finally {
                if (button) button.disabled = false;
            }
        });

        if (volume) {
            audio.volume = Number(volume.value) / 100;
            volume.addEventListener('input', () => {
                audio.volume = Number(volume.value) / 100;
            });
        }

        updateFavoriteButtonState();

        audio.addEventListener('timeupdate', () => {
            progress.value = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
            document.getElementById('player-current').textContent = formatTime(audio.currentTime);

            if (currentTrack && !streamSent && audio.currentTime >= 3) {
                streamSent = true;
                fetch('api.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        csrf_token: '<?php echo e(csrfToken()); ?>',
                        action: 'stream',
                        track_id: currentTrack,
                        duration_played: String(Math.floor(audio.currentTime)),
                        session_id: '<?php echo e(session_id()); ?>'
                    })
                });
            }
        });

        audio.addEventListener('loadedmetadata', () => {
            document.getElementById('player-duration').textContent = formatTime(audio.duration);
        });

        progress.addEventListener('input', () => {
            if (audio.duration) {
                audio.currentTime = (progress.value / 100) * audio.duration;
            }
        });
    </script>
</body>
</html>
