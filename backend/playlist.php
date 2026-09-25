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

$favoriteCountStmt = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = :user_id');
$favoriteCountStmt->execute(['user_id' => $userId]);
$favoriteCount = (int) $favoriteCountStmt->fetchColumn();

$playlistsStmt = $pdo->prepare('SELECT playlist_id, playlist_name FROM playlists WHERE user_id = :user_id ORDER BY playlist_name');
$playlistsStmt->execute(['user_id' => $userId]);
$playlists = $playlistsStmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT pt.track_order, t.track_id, t.title, t.duration_seconds, t.audio_url, ar.artist_name
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
    <link rel="stylesheet" href="../frontend/user-dashbord.css?v=20260926">
    <style>
        body {
            min-width: 1100px;
            overflow-x: auto;
            margin: 0;
            background: #0d0f12;
            color: #fff;
            font-family: 'Inter', sans-serif;
        }

        .app-shell {
            display: flex;
            min-height: calc(100vh - 90px);
            background: rgba(12, 12, 15, 0.96);
        }

        .sidebar {
            width: 270px;
            background: rgba(12, 12, 15, 0.98);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding: 18px 18px 0;
            display: flex;
            flex-direction: column;
            gap: 18px;
            position: sticky;
            top: 0;
            height: calc(100vh - 90px);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            padding: 8px 4px 10px;
        }

        .logo i {
            color: var(--spotify-green, #1ed760);
            font-size: 1.8rem;
        }

        .nav-links {
            display: grid;
            gap: 8px;
        }

        .nav-links a,
        .list-item {
            color: var(--text-muted, #a7b0c0);
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
            transition: 0.2s ease;
        }

        .nav-links a.active,
        .nav-links a:hover,
        .list-item.active-item,
        .list-item:hover {
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .library-box {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .library-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
            font-weight: 700;
        }

        .icon-btn {
            color: var(--text-muted, #a7b0c0);
            text-decoration: none;
        }

        .list-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            padding: 10px 12px;
            font-size: 0.95rem;
        }

        .list-count {
            margin-left: auto;
            font-size: 0.75rem;
            color: var(--text-muted, #a7b0c0);
        }

        .main-shell {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .page {
            max-width: 1500px !important;
            width: calc(100% - 40px) !important;
            min-width: 1100px;
            margin: 0 auto !important;
            padding: 0 0 120px !important;
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
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
            gap: 20px;
        }

        .playlist-track-row {
            min-height: 54px;
        }

        .playlist-track-row .track-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .playlist-track-row .track-title-wrap a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .playlist-track-row .track-title-wrap a:hover {
            color: var(--spotify-green);
        }

        .play-track-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.04);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .play-track-btn:hover {
            border-color: var(--spotify-green);
            color: var(--spotify-green);
        }

        .control-buttons .small-action.active {
            color: var(--spotify-green);
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
    <div class="app-shell">
        <aside class="sidebar">
            <div class="logo">
                <i class="fab fa-spotify"></i>
                <span>PulseFlow</span>
            </div>

            <nav class="nav-links">
                <a href="user-dashbord.php"><i class="fas fa-home"></i> Home</a>
                <a href="search.php"><i class="fas fa-search"></i> Search</a>
                <a href="favorites.php"><i class="fas fa-heart"></i> Favorites <span class="list-count"><?php echo (int) $favoriteCount; ?></span></a>
                <a href="playlists.php" class="active"><i class="fas fa-book"></i> Playlists</a>
                <a href="subscriptions.php"><i class="fas fa-crown"></i> Subscription</a>
                <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>

            <div class="library-box">
                <div class="library-header">
                    <h3>Your Playlists</h3>
                    <a class="icon-btn" href="playlists.php" aria-label="Manage playlists"><i class="fas fa-plus"></i></a>
                </div>

                <?php foreach ($playlists as $playlistItem): ?>
                    <a class="list-item <?php echo (int) $playlistItem['playlist_id'] === $playlistId ? 'active-item' : ''; ?>" href="playlist.php?id=<?php echo (int) $playlistItem['playlist_id']; ?>">
                        <span><?php echo e($playlistItem['playlist_name']); ?></span>
                    </a>
                <?php endforeach; ?>

                <?php if (!$playlists): ?>
                    <p class="empty">Create your first playlist.</p>
                <?php endif; ?>
            </div>
        </aside>

        <div class="main-shell">
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
                            <input name="playlist_name" value="<?php echo e($playlist['playlist_name']); ?>" required>
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
                                        <?php echo e($candidate['title']); ?> - <?php echo e($candidate['artist_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button>Add track</button>
                        </form>
                    <?php endif; ?>

                    <?php foreach ($tracks as $track): ?>
                        <div class="row playlist-track-row">
                            <div class="track-title-wrap">
                                <button
                                    type="button"
                                    class="play-track-btn play-track"
                                    data-track-id="<?php echo (int) $track['track_id']; ?>"
                                    data-audio-url="<?php echo e($track['audio_url']); ?>"
                                    data-title="<?php echo e($track['title']); ?>"
                                    data-artist="<?php echo e($track['artist_name']); ?>"
                                    aria-label="Play <?php echo e($track['title']); ?>"
                                    title="Play"
                                >
                                    <i class="fas fa-play"></i>
                                </button>

                                <a href="track.php?id=<?php echo (int) $track['track_id']; ?>">
                                    <?php echo (int) $track['track_order']; ?>.
                                    <?php echo e($track['title']); ?>
                                    <span class="muted">- <?php echo e($track['artist_name']); ?></span>
                                </a>
                            </div>

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
        </div>
    </div>

    <footer class="music-player">
        <div class="now-playing">
            <img id="player-cover" src="https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=200&q=80" alt="Current track cover">
            <div class="track-meta">
                <div class="track-info">
                    <h5 id="player-title">Select a track</h5>
                    <p id="player-artist">Nothing playing</p>
                </div>
            </div>
        </div>

        <div class="player-controls">
            <div class="control-buttons">
                <button id="player-prev" class="small-action" title="Previous" aria-label="Previous track"><i class="fas fa-backward-step"></i></button>
                <button id="player-shuffle" class="small-action" title="Shuffle" aria-label="Shuffle"><i class="fas fa-shuffle"></i></button>
                <button class="small-action" id="player-play" title="Play or pause"><i class="fas fa-circle-play main-play"></i></button>
                <button id="player-repeat" class="small-action" title="Repeat" aria-label="Repeat"><i class="fas fa-repeat"></i></button>
                <button id="player-next" class="small-action" title="Next" aria-label="Next track"><i class="fas fa-forward-step"></i></button>
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
        const playerTitle = document.getElementById('player-title');
        const playerArtist = document.getElementById('player-artist');
        const playerCover = document.getElementById('player-cover');
        const prevButton = document.getElementById('player-prev');
        const nextButton = document.getElementById('player-next');
        const playButton = document.getElementById('player-play');
        const shuffleButton = document.getElementById('player-shuffle');
        const repeatButton = document.getElementById('player-repeat');
        const playlistQueue = <?php echo json_encode(array_map(static function ($track) {
            return [
                'track_id' => (int) $track['track_id'],
                'audio_url' => $track['audio_url'] ?? '',
                'title' => $track['title'],
                'artist' => $track['artist_name'],
            ];
        }, $tracks)); ?>;
        let currentTrack = 0;
        let currentQueueIndex = -1;
        let streamSent = false;
        let shuffleEnabled = false;
        let repeatEnabled = false;
        let pendingAutoplay = false;

        const formatTime = s => {
            s = Math.floor(s || 0);
            return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        };

        const updateTransportState = () => {
            shuffleButton?.classList.toggle('active', shuffleEnabled);
            repeatButton?.classList.toggle('active', repeatEnabled);
        };

        const refreshPlayButtonState = () => {
            if (!playButton) return;
            const icon = playButton.querySelector('i');
            if (!icon) return;
            icon.classList.remove('fa-circle-play', 'fa-circle-pause');
            icon.classList.add(audio.paused ? 'fa-circle-play' : 'fa-circle-pause');
            playButton.setAttribute('aria-label', audio.paused ? 'Play' : 'Pause');
            playButton.title = audio.paused ? 'Play or pause' : 'Pause';
        };

        const setPlayer = (trackId, audioUrl, title, artist, shouldAutoplay = true) => {
            currentTrack = Number(trackId) || 0;
            streamSent = false;
            pendingAutoplay = Boolean(audioUrl && shouldAutoplay);
            audio.pause();
            audio.src = audioUrl || '';
            audio.load();
            playerTitle.textContent = title || 'Select a track';
            playerArtist.textContent = artist || 'Nothing playing';
            playerCover.src = 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=200&q=80';
            refreshPlayButtonState();
        };

        const playByQueueIndex = index => {
            if (!playlistQueue.length) return;
            const total = playlistQueue.length;
            let safeIndex = index;

            if (safeIndex < 0) {
                safeIndex = total - 1;
            }
            if (safeIndex >= total) {
                safeIndex = 0;
            }

            const track = playlistQueue[safeIndex];
            if (!track || !track.audio_url) {
                return;
            }

            currentQueueIndex = safeIndex;
            setPlayer(track.track_id, track.audio_url, track.title, track.artist);
        };

        const playNext = () => {
            if (!playlistQueue.length) return;
            if (repeatEnabled && currentQueueIndex >= 0) {
                playByQueueIndex(currentQueueIndex);
                return;
            }
            if (shuffleEnabled) {
                const nextIndex = Math.floor(Math.random() * playlistQueue.length);
                playByQueueIndex(nextIndex);
                return;
            }
            playByQueueIndex((currentQueueIndex + 1) % playlistQueue.length);
        };

        const playPrevious = () => {
            if (!playlistQueue.length) return;
            if (shuffleEnabled) {
                const nextIndex = Math.floor(Math.random() * playlistQueue.length);
                playByQueueIndex(nextIndex);
                return;
            }
            playByQueueIndex(currentQueueIndex - 1);
        };

        document.querySelectorAll('.play-track').forEach(button => {
            button.addEventListener('click', () => {
                const audioUrl = button.dataset.audioUrl;
                if (!audioUrl) {
                    alert('This track has no audio file yet.');
                    return;
                }

                const clickedId = Number(button.dataset.trackId) || 0;
                const matchedIndex = playlistQueue.findIndex(track => Number(track.track_id) === clickedId);
                const targetIndex = matchedIndex >= 0 ? matchedIndex : 0;
                currentQueueIndex = targetIndex;
                const selectedTrack = playlistQueue[targetIndex] || {
                    track_id: clickedId,
                    audio_url: audioUrl,
                    title: button.dataset.title,
                    artist: button.dataset.artist,
                };
                setPlayer(selectedTrack.track_id, selectedTrack.audio_url, selectedTrack.title, selectedTrack.artist, true);
            });
        });

        playButton?.addEventListener('click', () => {
            if (audio.paused) {
                audio.play().catch(() => {});
            } else {
                audio.pause();
            }
            refreshPlayButtonState();
        });

        prevButton?.addEventListener('click', playPrevious);
        nextButton?.addEventListener('click', playNext);

        shuffleButton?.addEventListener('click', () => {
            shuffleEnabled = !shuffleEnabled;
            updateTransportState();
        });

        repeatButton?.addEventListener('click', () => {
            repeatEnabled = !repeatEnabled;
            updateTransportState();
        });

        document.querySelector('.user-icon-btn')?.addEventListener('click', event => {
            event.stopPropagation();
            const menu = document.querySelector('.account-menu');
            menu?.classList.toggle('open');
        });

        document.addEventListener('click', event => {
            const menu = document.querySelector('.account-menu');
            const button = document.querySelector('.user-icon-btn');
            if (menu && button && !menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.remove('open');
            }
        });

        if (volume) {
            audio.volume = Number(volume.value) / 100;
            volume.addEventListener('input', () => {
                audio.volume = Number(volume.value) / 100;
            });
        }

        progress.addEventListener('input', () => {
            if (audio.duration) {
                audio.currentTime = (progress.value / 100) * audio.duration;
            }
        });

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
                        track_id: String(currentTrack),
                        duration_played: String(Math.floor(audio.currentTime)),
                        session_id: '<?php echo e(session_id()); ?>'
                    })
                });
            }
        });

        audio.addEventListener('loadedmetadata', () => {
            document.getElementById('player-duration').textContent = formatTime(audio.duration);
            refreshPlayButtonState();
        });

        audio.addEventListener('canplay', () => {
            if (pendingAutoplay) {
                audio.play().catch(() => {});
                pendingAutoplay = false;
            }
            refreshPlayButtonState();
        });

        audio.addEventListener('play', refreshPlayButtonState);
        audio.addEventListener('pause', refreshPlayButtonState);

        audio.addEventListener('ended', () => {
            if (repeatEnabled && currentQueueIndex >= 0) {
                playByQueueIndex(currentQueueIndex);
                return;
            }
            if (playlistQueue.length) {
                playNext();
            }
        });

        updateTransportState();
        refreshPlayButtonState();
    </script>
</body>
</html>
