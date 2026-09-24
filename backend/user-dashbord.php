<?php
require __DIR__ . '/home.php';
exit;
require __DIR__ . '/../backend/auth.php';
requireRole('user', '../frontend/user-login.html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PulseFlow Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --bg: #0c0c0f;
            --bg-2: #121419;
            --panel: rgba(18, 20, 25, 0.96);
            --panel-soft: rgba(255,255,255,0.04);
            --line: rgba(255,255,255,0.08);
            --text: #f5f7fa;
            --muted: #a3afc2;
            --green: #1db954;
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #101215, #171b22 28%, #0d0f12 100%);
            color: var(--text);
        }
        a { text-decoration: none; color: inherit; }

        .main-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: calc(100vh - 90px);
        }

        .sidebar {
            background: rgba(12,12,15,0.96);
            border-right: 1px solid var(--line);
            padding: 22px 18px;
        }

        .logo {
            display:flex;
            align-items:center;
            gap:10px;
            font-weight:800;
            font-size:1.7rem;
            margin-bottom:24px;
        }

        .logo i {
            color: var(--green);
            font-size: 1.8rem;
        }

        .nav-links {
            display:grid;
            gap:10px;
        }

        .nav-links a {
            color: var(--muted);
            padding: 12px 14px;
            border-radius: 12px;
            display:flex;
            align-items:center;
            gap:10px;
            border: 1px solid transparent;
        }

        .nav-links a.active,
        .nav-links a:hover {
            background: rgba(255,255,255,0.03);
            color: var(--text);
            border-color: var(--line);
        }

        .library-box {
            margin-top: 26px;
            border-top: 1px solid var(--line);
            padding-top: 18px;
        }

        .library-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            color: var(--muted);
            margin-bottom: 18px;
        }

        .icon-btn {
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 1rem;
        }

        .library-empty {
            background: rgba(255,255,255,0.02);
            border:1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
        }

        .box-title {
            margin: 0 0 8px;
            font-weight:700;
        }

        .box-subtitle {
            margin:0 0 14px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .badge-btn {
            background: var(--green);
            color: #0b140d;
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .library-list {
            margin-top: 18px;
            display:grid;
            gap:10px;
        }

        .list-item {
            display:flex;
            justify-content:space-between;
            gap:12px;
            color: var(--muted);
            padding: 6px 4px;
        }

        .list-item.active-item {
            color: var(--text);
        }

        .main-content {
            padding: 24px 24px 0;
        }

        .top-nav {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom: 22px;
        }

        .nav-arrows {
            display:flex;
            gap:18px;
            color: var(--muted);
            font-size: 1rem;
        }

        .user-actions {
            display:flex;
            align-items:center;
            gap:12px;
        }

        .premium-btn {
            padding: 10px 18px;
            border-radius: 999px;
            border: none;
            background: rgba(255,255,255,0.08);
            color: var(--text);
            font-weight:700;
        }

        .user-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.04);
            color: var(--text);
        }

        .greeting-section h2 {
            margin: 0 0 20px;
            font-size: 2rem;
        }

        .greeting-grid {
            display:grid;
            grid-template-columns: repeat(3, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 34px;
        }

        .mini-card {
            display:flex;
            align-items:center;
            gap:14px;
            background: rgba(255,255,255,0.03);
            border:1px solid var(--line);
            border-radius:16px;
            padding: 12px;
        }

        .mini-card img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 12px;
        }

        .mini-card h4 {
            margin: 0 0 6px;
        }

        .mini-card span {
            color: var(--muted);
            font-size: 0.82rem;
        }

        .content-section {
            margin-top: 10px;
            margin-bottom: 30px;
        }

        .section-header {
            display:flex;
            justify-content: space-between;
            align-items:center;
            margin-bottom: 18px;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }

        .section-header a {
            color: var(--muted);
            font-size: 0.88rem;
        }

        .card-grid {
            display:grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 18px;
        }

        .card {
            background: rgba(255,255,255,0.02);
            border:1px solid var(--line);
            border-radius: 18px;
            padding: 12px;
            position: relative;
        }

        .card img {
            width:100%;
            height: 170px;
            object-fit: cover;
            border-radius: 14px;
            margin-bottom: 12px;
        }

        .card h4 {
            margin: 0 0 6px;
            font-size: 1.1rem;
        }

        .card p {
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .play-btn-hover {
            position:absolute;
            right: 22px;
            bottom: 72px;
            width: 42px;
            height: 42px;
            border-radius:50%;
            background: var(--green);
            display:grid;
            place-items:center;
            color: #08140d;
            box-shadow: var(--shadow);
        }

        .music-player {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 20px;
            background: rgba(18,20,24,0.96);
            border-top: 1px solid var(--line);
            padding: 16px 22px;
            height: 90px;
        }

        .now-playing {
            display:flex;
            align-items:center;
            gap: 12px;
            min-width: 250px;
        }

        .now-playing img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
        }

        .track-info h5 {
            margin: 0 0 3px;
            font-size: 0.95rem;
        }

        .track-info p {
            margin: 0;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .heart-icon {
            color: var(--muted);
            margin-left: 8px;
        }

        .player-controls {
            flex:1;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap: 8px;
        }

        .control-buttons {
            display:flex;
            align-items:center;
            gap: 18px;
            color: var(--muted);
            font-size: 1rem;
        }

        .main-play {
            font-size: 1.8rem;
            color: var(--text);
        }

        .progress-bar-container {
            width: min(520px, 100%);
            display:flex;
            align-items:center;
            gap:10px;
            color: var(--muted);
            font-size: 0.72rem;
        }

        .progress-bar,
        .volume-bar {
            flex:1;
            height: 5px;
            background: rgba(255,255,255,0.12);
            border-radius: 999px;
            overflow:hidden;
        }

        .progress-fill {
            width: 38%;
            height:100%;
            background: var(--green);
        }

        .volume-controls {
            display:flex;
            align-items:center;
            gap: 12px;
            color: var(--muted);
            min-width: 180px;
        }

        .volume-fill {
            width: 60%;
            height:100%;
            background: var(--text);
        }

        @media (max-width: 980px) {
            .main-container {
                grid-template-columns: 260px 1fr !important;
            }
            .sidebar {
                border-right: 1px solid var(--line) !important;
                border-bottom: none !important;
            }
            .greeting-grid,
            .card-grid {
                grid-template-columns: repeat(3, minmax(180px, 1fr)) !important;
            }
        }

        @media (max-width: 620px) {
            .greeting-grid,
            .card-grid {
                grid-template-columns: repeat(3, minmax(180px, 1fr)) !important;
            }
            .music-player {
                flex-wrap: nowrap !important;
                height: 90px !important;
            }
        }

        /* Force desktop layout everywhere */
        html, body {
            min-width: 1100px;
        }

        body {
            overflow-x: auto;
        }

        .main-container {
            grid-template-columns: 260px 1fr !important;
        }

        .sidebar {
            border-right: 1px solid var(--line) !important;
            border-bottom: none !important;
        }

        .greeting-grid,
        .card-grid {
            grid-template-columns: repeat(3, minmax(180px, 1fr)) !important;
        }
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
                <a href="#" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="#"><i class="fas fa-search"></i> Search</a>
                <a href="#"><i class="fas fa-book"></i> Your Library</a>
                <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>

            <div class="library-box">
                <div class="library-header">
                    <h3>Your Library</h3>
                    <button class="icon-btn" aria-label="Add playlist"><i class="fas fa-plus"></i></button>
                </div>

                <div class="library-empty">
                    <p class="box-title">Create your first playlist</p>
                    <p class="box-subtitle">It's easy, we'll help you</p>
                    <button class="badge-btn">Create playlist</button>
                </div>

                <div class="library-list">
                    <div class="list-item active-item">
                        <span class="list-tag">Liked Songs</span>
                        <span class="list-count">2,112</span>
                    </div>
                    <div class="list-item">
                        <span class="list-tag">Daily Mix 1</span>
                        <span class="list-count">23 songs</span>
                    </div>
                    <div class="list-item">
                        <span class="list-tag">Chill Mix</span>
                        <span class="list-count">12 songs</span>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-nav">
                <div class="nav-arrows">
                    <i class="fas fa-chevron-left"></i>
                    <i class="fas fa-chevron-right"></i>
                </div>

                <div class="user-actions">
                    <button class="premium-btn">Explore Premium</button>
                    <button class="user-icon" aria-label="User profile"><i class="fas fa-user"></i></button>
                </div>
            </header>

            <section class="greeting-section">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h2>
                <div class="greeting-grid">
                    <article class="mini-card">
                        <img src="https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=300&q=80" alt="Liked songs cover">
                        <div>
                            <h4>Liked Songs</h4>
                            <span>2,112 songs</span>
                        </div>
                    </article>

                    <article class="mini-card">
                        <img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=300&q=80" alt="Daily mix cover">
                        <div>
                            <h4>Daily Mix 1</h4>
                            <span>Drake, The Weeknd...</span>
                        </div>
                    </article>

                    <article class="mini-card">
                        <img src="https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=300&q=80" alt="Chill mix cover">
                        <div>
                            <h4>Chill Mix</h4>
                            <span>Lo-fi and mellow</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="content-section">
                <div class="section-header">
                    <h2>Featured Playlists</h2>
                    <a href="#">Show all</a>
                </div>

                <div class="card-grid">
                    <div class="card">
                        <img src="https://images.unsplash.com/photo-1511379938547-c1f69419868d?auto=format&fit=crop&w=400&q=80" alt="Today's Top Hits cover">
                        <div class="play-btn-hover"><i class="fas fa-play"></i></div>
                        <h4>Today's Top Hits</h4>
                        <p>Jung Kook is on top of the Hottest 50!</p>
                    </div>

                    <div class="card">
                        <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=400&q=80" alt="Chill Vibes cover">
                        <div class="play-btn-hover"><i class="fas fa-play"></i></div>
                        <h4>Chill Vibes</h4>
                        <p>Kick back and relax with these lo-fi beats.</p>
                    </div>

                    <div class="card">
                        <img src="https://images.unsplash.com/photo-1496293455970-f8581aae0e3b?auto=format&fit=crop&w=400&q=80" alt="Rock Classics cover">
                        <div class="play-btn-hover"><i class="fas fa-play"></i></div>
                        <h4>Rock Classics</h4>
                        <p>Legends never die. Heavy rock anthems.</p>
                    </div>

                    <div class="card">
                        <img src="https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&fit=crop&w=400&q=80" alt="Focus flow cover">
                        <div class="play-btn-hover"><i class="fas fa-play"></i></div>
                        <h4>Focus Flow</h4>
                        <p>Deep work, calm beats, and clean focus.</p>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <footer class="music-player">
        <div class="now-playing">
            <img src="https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=200&q=80" alt="Current Song Cover">
            <div class="track-info">
                <h5>Seven (feat. Latto)</h5>
                <p>Jung Kook</p>
            </div>
            <i class="far fa-heart heart-icon"></i>
        </div>

        <div class="player-controls">
            <div class="control-buttons">
                <i class="fas fa-shuffle"></i>
                <i class="fas fa-backward-step"></i>
                <i class="fas fa-circle-play main-play"></i>
                <i class="fas fa-forward-step"></i>
                <i class="fas fa-repeat"></i>
            </div>

            <div class="progress-bar-container">
                <span>1:24</span>
                <div class="progress-bar"><div class="progress-fill"></div></div>
                <span>3:45</span>
            </div>
        </div>

        <div class="volume-controls">
            <i class="fas fa-microphone"></i>
            <i class="fas fa-layer-group"></i>
            <i class="fas fa-volume-low"></i>
            <div class="volume-bar"><div class="volume-fill"></div></div>
        </div>
    </footer>
</body>
</html>
