<?php
require_once __DIR__ . '/functions.php';
requireUser();

$query = trim($_GET['q'] ?? '');
$artists = $albums = $tracks = $genres = [];

if ($query !== '') {
    $like = '%' . $query . '%';

    $stmt = $pdo->prepare(
        'SELECT artist_id, artist_name, country
         FROM artists
         WHERE artist_name LIKE :artist_name OR genre LIKE :artist_genre
         ORDER BY artist_name
         LIMIT 20'
    );
    $stmt->execute([
        'artist_name' => $like,
        'artist_genre' => $like,
    ]);
    $artists = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT al.album_id, al.title, ar.artist_name
         FROM albums al
         JOIN artists ar ON ar.artist_id = al.artist_id
         WHERE al.title LIKE :album_title OR ar.artist_name LIKE :album_artist
         ORDER BY al.title
         LIMIT 20'
    );
    $stmt->execute([
        'album_title' => $like,
        'album_artist' => $like,
    ]);
    $albums = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        trackQuery() .
        ' WHERE t.title LIKE :track_title
          OR ar.artist_name LIKE :track_artist
          OR al.title LIKE :track_album
         ORDER BY t.title
         LIMIT 30'
    );
    $stmt->execute([
        'track_title' => $like,
        'track_artist' => $like,
        'track_album' => $like,
    ]);
    $tracks = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT genre_id, genre_name
         FROM genres
         WHERE genre_name LIKE :q
         ORDER BY genre_name'
    );
    $stmt->execute(['q' => $like]);
    $genres = $stmt->fetchAll();

    $log = $pdo->prepare(
        'INSERT INTO search_history (user_id, search_query)
         VALUES (:user_id, :query)'
    );
    $log->execute([
        'user_id' => currentUserId(),
        'query' => $query,
    ]);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search</title>
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
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
        }

        .muted {
            color: var(--text-muted);
        }

        input {
            padding: 12px;
            width: min(600px, 100%);
        }

        button {
            padding: 12px;
            background: var(--spotify-green);
            border: 0;
            border-radius: 8px;
        }

        a {
            color: inherit;
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
            cursor: pointer;
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
        <?php foreach ([
            ['Artists', $artists, 'artist.php', 'artist_id', 'artist_name'],
            ['Albums', $albums, 'album.php', 'album_id', 'title'],
            ['Tracks', $tracks, 'track.php', 'track_id', 'title'],
            ['Genres', $genres, 'genre.php', 'genre_id', 'genre_name'],
        ] as [$heading, $items, $url, $idKey, $nameKey]): ?>
            <section class="panel search-result-group">
                <h2><?php echo $heading; ?></h2>

                <?php foreach ($items as $item): ?>
                    <div class="row search-result-row">
                        <a href="<?php echo $url; ?>?id=<?php echo (int)$item[$idKey]; ?>">
                            <?php echo e($item[$nameKey]); ?>
                        </a>

                        <?php if (isset($item['artist_name'])): ?>
                            <span class="muted">
                                - <?php echo e($item['artist_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!$items && $query !== ''): ?>
                    <p class="muted">No matches.</p>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </main>

    <script>
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
            userIcon.addEventListener('click', event => {
                event.stopPropagation();
                accountMenu.classList.toggle('open');
            });

            document.addEventListener('click', event => {
                if (!accountMenu.contains(event.target) && !userIcon.contains(event.target)) {
                    accountMenu.classList.remove('open');
                }
            });
        }
    </script>
</body>
</html>
