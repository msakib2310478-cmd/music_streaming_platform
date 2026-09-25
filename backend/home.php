<?php
require_once __DIR__ . '/functions.php';
$userId = requireUser();

$queries = [
    'weekly' => "SELECT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name, COUNT(sh.stream_id) AS metric FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id WHERE sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 7 DAY GROUP BY t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name ORDER BY metric DESC, t.title LIMIT 10",
    'trending' => "SELECT t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name, SUM(sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 7 DAY) AS recent_plays, SUM(sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 28 DAY) AS four_week_plays FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id WHERE sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 28 DAY GROUP BY t.track_id, t.title, t.audio_url, t.cover_image, ar.artist_name ORDER BY recent_plays DESC, four_week_plays DESC, t.title LIMIT 10",
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
$recentTrackIds = array_column($sections['recent'], 'track_id');
foreach ($sections['latest'] as $track) {
    if (!in_array($track['track_id'], $recentTrackIds, true)) {
        $sections['recent'][] = $track;
    }
}
$newReleases = $pdo->query("SELECT al.album_id, al.title, al.cover_image, al.release_date, ar.artist_name FROM albums al JOIN artists ar ON ar.artist_id = al.artist_id WHERE al.release_date IS NOT NULL ORDER BY al.release_date DESC LIMIT 8")->fetchAll();
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
$message = $_SESSION['success'] ?? $_SESSION['error'] ?? '';
$messageType = isset($_SESSION['error']) ? 'error' : 'success';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PulseFlow Dashboard</title>
    <link rel="stylesheet" href="../frontend/user-dashbord.css?v=20260925">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .feature-bar { display:flex; gap:10px; flex-wrap:wrap; margin:14px 0 24px; }
        .feature-bar a, .genre-pill { padding:9px 13px; border:1px solid var(--border); border-radius:999px; color:var(--text-muted); text-decoration:none; }
        .feature-bar a:hover, .genre-pill:hover { color:var(--text-white); border-color:var(--spotify-green); }
        .metric { color:var(--text-muted); font-size:.8rem; }
        .card-actions { display:flex; gap:8px; align-items:center; }
        .card-actions form { display:inline; }
        .small-action { background:none; border:0; color:var(--text-muted); cursor:pointer; padding:4px; }
        .small-action:hover { color:var(--spotify-green); }
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
        .user-icon-btn:hover { border-color: var(--spotify-green); }
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
            background: linear-gradient(135deg, #1ed760, #6ee7b7);
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
    </style>
</head>
<body>
    <div class="main-container">
        <aside class="sidebar">
            <div class="logo">
                <i class="fab fa-spotify"></i>
                <span>PulseFlow</span>
            </div>

            <nav class="nav-links">
                <a href="user-dashbord.php" class="active" data-dashboard-link><i class="fas fa-home"></i> Home</a>
                <a href="#search" data-search-trigger><i class="fas fa-search"></i> Search</a>
                <a href="favorites.php" data-dashboard-link><i class="fas fa-heart"></i> Favorites <span class="list-count" id="favorite-count"><?php echo $favoriteCount; ?></span></a>
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
                    <p class="empty">Create your first playlist.</p>
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
                        <input type="text" name="q" placeholder="What do you want to play?" aria-label="Search songs, albums, artists">
                        <button type="submit" aria-label="Search">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>

                <div class="user-actions">
                    <a class="badge-btn premium-btn" href="subscriptions.php">Explore Premium</a>
                    <div class="user-menu-wrap">
                        <button class="user-icon-btn" type="button" aria-label="Account information">
                            <i class="fas fa-user"></i>
                        </button>
                        <div class="account-menu" aria-live="polite">
                            <div class="menu-header">
                                <div class="account-avatar"><?php echo strtoupper(substr(e($_SESSION['username'] ?? 'U'), 0, 1)); ?></div>
                                <div>
                                    <div class="account-name"><?php echo e($_SESSION['username'] ?? 'User'); ?></div>
                                    <div class="account-email"><?php echo e($_SESSION['email'] ?? 'No email found'); ?></div>
                                </div>
                            </div>
                            <div class="account-details">
                                <div class="account-row"><span>Plan</span><strong><?php echo e($_SESSION['subscription_type'] ?? 'Free'); ?></strong></div>
                                <div class="account-row"><span>Country</span><strong><?php echo e($_SESSION['country'] ?? 'Not set'); ?></strong></div>
                            </div>
                            <a class="logout-link" href="../backend/logout.php">Log out</a>
                        </div>
                    </div>
                </div>
            </header>

            <div id="dashboard-view">
                <?php if ($message): ?>
                    <div class="alert <?php echo $messageType === 'error' ? 'error' : ''; ?>"><?php echo e($message); ?></div>
                <?php endif; ?>

                <section class="greeting-section">
                <h2>Welcome back, <?php echo e($_SESSION['username'] ?? 'User'); ?></h2>
                <div class="feature-bar">
                    <a href="recently-played.php" data-dashboard-link>Recently Played</a>
                    <a href="followed-artists.php" data-dashboard-link>Followed Artists</a>
                    <a href="recommendations.php" data-dashboard-link>Recommended For You</a>
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
                                <a href="track.php?id=<?php echo (int)$track['track_id']; ?>" class="play-track" data-track-id="<?php echo (int)$track['track_id']; ?>" data-audio-url="<?php echo e($track['audio_url']); ?>" data-title="<?php echo e($track['title']); ?>" data-artist="<?php echo e($track['artist_name']); ?>">
                                    <img src="<?php echo e($track['cover_image'] ?: 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=400&q=80'); ?>" alt="<?php echo e($track['title']); ?> cover">
                                </a>
                                <h4><a href="track.php?id=<?php echo (int)$track['track_id']; ?>" class="play-track" data-track-id="<?php echo (int)$track['track_id']; ?>" data-audio-url="<?php echo e($track['audio_url']); ?>" data-title="<?php echo e($track['title']); ?>" data-artist="<?php echo e($track['artist_name']); ?>"><?php echo e($track['title']); ?></a></h4>
                                <p><?php echo e($track['artist_name']); ?></p>
                                <div class="card-actions">
                                    <button class="small-action play-track" data-track-id="<?php echo (int)$track['track_id']; ?>" data-audio-url="<?php echo e($track['audio_url']); ?>" data-title="<?php echo e($track['title']); ?>" data-artist="<?php echo e($track['artist_name']); ?>" title="Play"><i class="fas fa-play"></i></button>
                                    <form action="api.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                                        <input type="hidden" name="action" value="favorite">
                                        <input type="hidden" name="track_id" value="<?php echo (int)$track['track_id']; ?>">
                                        <input type="hidden" name="redirect" value="user-dashbord.php">
                                        <button class="small-action" title="Favorite"><i class="fas fa-heart"></i></button>
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
                            <p class="empty">No data yet. Start listening to build this section.</p>
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
                    <h5 id="player-title">Select a track</h5>
                    <p id="player-artist">Nothing playing</p>
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
                <button class="small-action" title="Previous" aria-label="Previous track"><i class="fas fa-backward-step"></i></button>
                <button class="small-action" title="Shuffle" aria-label="Shuffle"><i class="fas fa-shuffle"></i></button>
                <button class="small-action" id="player-play" title="Play or pause"><i class="fas fa-circle-play main-play"></i></button>
                <button class="small-action" title="Repeat" aria-label="Repeat"><i class="fas fa-repeat"></i></button>
                <button class="small-action" title="Next" aria-label="Next track"><i class="fas fa-forward-step"></i></button>
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

    <script>
        const audio = document.getElementById('audio-player');
        const progress = document.getElementById('player-progress');
        const volume = document.getElementById('player-volume');
        const favoriteForm = document.querySelector('[data-player-favorite]');
        const favoriteTrackInput = favoriteForm?.querySelector('input[name="track_id"]');
        const favoriteButton = favoriteForm?.querySelector('.like-toggle');
        const playerPlayButton = document.getElementById('player-play');
        const playerPlayIcon = playerPlayButton?.querySelector('i');
        const dashboardView = document.getElementById('dashboard-view');
        let dashboardRequest = 0;
        let currentTrack = 0;
        let streamSent = false;
        const likedTrackIds = new Set(<?php echo json_encode(array_keys($favoriteTrackIds)); ?>);

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

                dashboardView.innerHTML = nextView.innerHTML;
                dashboardView.querySelectorAll('.top-nav').forEach(header => header.remove());
                const embeddedBreadcrumb = dashboardView.querySelector('p:first-child a[href="user-dashbord.php"]');
                embeddedBreadcrumb?.parentElement.remove();
                setActiveDashboardLink(url);
                if (pushState) history.pushState({ dashboardUrl: url }, '', url);
                dashboardView.scrollTop = 0;
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
            streamSent = false;
            favoriteTrackInput.value = String(currentTrack);
            updateFavoriteButtonState();
            audio.src = button.dataset.audioUrl;
            document.getElementById('player-title').textContent = button.dataset.title;
            document.getElementById('player-artist').textContent = button.dataset.artist;
            audio.play().catch(() => {});
        };

        document.addEventListener('click', event => {
            const playButton = event.target.closest('.play-track');
            if (!playButton) return;
            event.preventDefault();
            playTrack(playButton);
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

        playerPlayButton?.addEventListener('click', () => {
            audio.paused ? audio.play() : audio.pause();
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
        audio.addEventListener('ended', updatePlayButtonState);
        updatePlayButtonState();

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
