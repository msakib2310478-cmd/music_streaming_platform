<?php
require_once __DIR__ . '/functions.php';

requireRole('admin', '../frontend/admin-login.html');
ensureArtistAccountsTable($pdo);

$users = $pdo->query("SELECT user_id, username, email, subscription_type, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$artistApplications = $pdo->query("SELECT aa.user_id, aa.artist_id, aa.requested_at, u.username, u.email, ar.artist_name FROM artist_accounts aa JOIN users u ON u.user_id = aa.user_id JOIN artists ar ON ar.artist_id = aa.artist_id WHERE aa.status = 'pending' ORDER BY aa.requested_at")->fetchAll();
$artists = $pdo->query("SELECT artist_id, artist_name, genre, country FROM artists ORDER BY artist_name ASC")->fetchAll();
$albums = $pdo->query("SELECT a.album_id, a.title, ar.artist_name, a.release_date, a.album_type FROM albums a JOIN artists ar ON ar.artist_id = a.artist_id ORDER BY a.title ASC")->fetchAll();
$tracks = $pdo->query("SELECT t.track_id, t.title, a.title AS album_title, t.duration_seconds, t.explicit, t.track_number, t.audio_url FROM tracks t JOIN albums a ON a.album_id = t.album_id ORDER BY a.title ASC, t.track_number ASC")->fetchAll();
$stats = $pdo->query("SELECT (SELECT COUNT(*) FROM users) total_users, (SELECT COUNT(*) FROM artists) total_artists, (SELECT COUNT(*) FROM albums) total_albums, (SELECT COUNT(*) FROM tracks) total_tracks, (SELECT COUNT(*) FROM playlists) total_playlists, (SELECT COUNT(*) FROM stream_history) total_streams, (SELECT COUNT(*) FROM favorites) total_favorites, (SELECT COUNT(*) FROM ratings) total_ratings")->fetch();
$weeklyStreams = (int)$pdo->query("SELECT COUNT(*) FROM stream_history WHERE played_at >= CURRENT_TIMESTAMP - INTERVAL 7 DAY")->fetchColumn();
$monthlyStreams = (int)$pdo->query("SELECT COUNT(*) FROM stream_history WHERE played_at >= CURRENT_TIMESTAMP - INTERVAL 30 DAY")->fetchColumn();
$topWeekly = $pdo->query("SELECT t.title, ar.artist_name, COUNT(*) stream_count FROM stream_history sh JOIN tracks t ON t.track_id = sh.track_id JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id WHERE sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 7 DAY GROUP BY t.track_id, t.title, ar.artist_name ORDER BY stream_count DESC LIMIT 5")->fetchAll();
$deviceUsage = $pdo->query("SELECT COALESCE(device_type, 'unknown') device_type, COUNT(*) stream_count FROM stream_history GROUP BY device_type ORDER BY stream_count DESC")->fetchAll();
$genreUsage = $pdo->query("SELECT g.genre_name, COUNT(sh.stream_id) stream_count FROM genres g JOIN track_genres tg ON tg.genre_id = g.genre_id JOIN stream_history sh ON sh.track_id = tg.track_id WHERE sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 30 DAY GROUP BY g.genre_id, g.genre_name ORDER BY stream_count DESC")->fetchAll();
$activeListeners = $pdo->query("SELECT u.username, COUNT(sh.stream_id) stream_count FROM users u JOIN stream_history sh ON sh.user_id = u.user_id WHERE sh.played_at >= CURRENT_TIMESTAMP - INTERVAL 30 DAY GROUP BY u.user_id, u.username ORDER BY stream_count DESC LIMIT 10")->fetchAll();

$msg = $_GET['msg'] ?? ($_SESSION['success'] ?? $_SESSION['error'] ?? '');
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PulseFlow Admin Dashboard</title>
  <style>
    :root {
      --bg: #0f1115;
      --panel: #171b22;
      --panel-alt: #1d232d;
      --line: rgba(255,255,255,0.08);
      --text: #edf2f7;
      --muted: #a3b0c2;
      --green: #1db954;
      --green-dark: #169642;
      --red: #ef4444;
      --shadow: 0 20px 40px rgba(0,0,0,0.25);
    }

    * { box-sizing: border-box; }
    body {
      margin:0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(180deg, #0b0d10, #12181d);
      color: var(--text);
    }

    .layout {
      display:flex;
      min-height:100vh;
    }

    .sidebar {
      width: 260px;
      background: rgba(15,17,21,0.95);
      border-right:1px solid var(--line);
      padding:24px 18px;
    }

    .brand {
      display:flex;
      align-items:center;
      gap:12px;
      font-weight:800;
      font-size:1.6rem;
      margin-bottom:28px;
    }

    .brand-dot {
      width:36px;
      height:36px;
      border-radius:50%;
      display:grid;
      place-items:center;
      background:var(--green);
      color:#08140d;
      font-weight:900;
    }

    .nav {
      display:grid;
      gap:10px;
    }

    .nav a {
      color:var(--muted);
      text-decoration:none;
      padding:12px 14px;
      border-radius:12px;
      border:1px solid transparent;
    }

    .nav a.active,
    .nav a:hover {
      background: rgba(255,255,255,0.04);
      border-color: var(--line);
      color: var(--text);
    }

    .main {
      flex:1;
      padding:32px;
    }

    .topbar {
      display:flex;
      justify-content:space-between;
      align-items:center;
      background: rgba(23,27,34,0.8);
      border:1px solid var(--line);
      padding:16px 20px;
      border-radius:18px;
      box-shadow: var(--shadow);
      margin-bottom:24px;
    }

    .topbar h1 {
      margin:0;
      font-size:2rem;
    }

    .userbox {
      display:flex;
      align-items:center;
      gap:12px;
    }

    .avatar {
      width:38px;
      height:38px;
      border-radius:50%;
      background:var(--green);
      color:#07130b;
      display:grid;
      place-items:center;
      font-weight:700;
    }

    .btn {
      display:inline-block;
      padding:10px 16px;
      border-radius:999px;
      border:1px solid var(--line);
      color:var(--text);
      text-decoration:none;
      background:transparent;
      font-weight:600;
      cursor:pointer;
    }

    .btn.primary {
      background:var(--green);
      border-color:var(--green);
      color:#081910;
    }

    .btn.danger {
      background:rgba(239,68,68,0.12);
      border-color: rgba(239,68,68,0.25);
      color:#ffd9d9;
    }

    .cards {
      display:grid;
      grid-template-columns: repeat(3, minmax(180px, 1fr));
      gap:20px;
      margin-bottom:28px;
    }

    .card {
      background: var(--panel);
      border:1px solid var(--line);
      border-radius:18px;
      padding:20px;
      box-shadow: var(--shadow);
    }

    .card h3 {
      margin:0 0 10px;
      color:var(--muted);
      font-size:0.9rem;
      text-transform:uppercase;
      letter-spacing:0.08em;
    }

    .card strong {
      display:block;
      font-size:2rem;
    }

    .section {
      background: var(--panel);
      border:1px solid var(--line);
      border-radius:20px;
      padding:20px;
      margin-bottom:24px;
      overflow:auto;
    }

    .section h2 {
      margin:0 0 16px;
      font-size:1.4rem;
    }

    table {
      width:100%;
      border-collapse: collapse;
      min-width: 700px;
    }

    th, td {
      text-align:left;
      padding:12px 10px;
      border-bottom:1px solid var(--line);
      vertical-align:top;
    }

    th {
      color: var(--muted);
      font-size:0.8rem;
      text-transform:uppercase;
      letter-spacing:0.08em;
    }

    .badge {
      display:inline-block;
      padding:5px 10px;
      border-radius:999px;
      font-size:0.75rem;
      background: rgba(29,185,84,0.14);
      border:1px solid rgba(29,185,84,0.28);
      color:#b9f7d1;
    }

    .badge.free {
      background: rgba(255,255,255,0.06);
      border-color: var(--line);
      color: var(--muted);
    }

    form.grid {
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap:14px;
    }

    .field {
      display:flex;
      flex-direction:column;
      gap:7px;
    }

    .field label {
      color: var(--muted);
      font-size:0.82rem;
    }

    .field input,
    .field select {
      width:100%;
      padding:11px 12px;
      background: rgba(255,255,255,0.02);
      border:1px solid var(--line);
      border-radius:10px;
      color: var(--text);
    }

    .message {
      padding:12px 14px;
      margin-bottom:18px;
      border-radius:12px;
      border:1px solid rgba(29,185,84,0.25);
      background: rgba(29,185,84,0.12);
      color:#d9f9ea;
    }

    @media (max-width: 900px) {
      .layout { display:flex !important; }
      .sidebar { width:260px !important; border-right:1px solid var(--line) !important; border-bottom:none !important; }
      .cards { grid-template-columns: repeat(3, minmax(180px, 1fr)) !important; }
    }

    /* Force desktop layout everywhere */
    html, body {
      min-width: 1100px;
    }

    body {
      overflow-x: auto;
    }

    .layout {
      display:flex !important;
    }

    .sidebar {
      width: 260px !important;
      border-right:1px solid var(--line) !important;
    }

    .cards {
      grid-template-columns: repeat(3, minmax(180px, 1fr)) !important;
    }
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">
        <span class="brand-dot">♫</span>
        <span>PulseFlow</span>
      </div>

      <nav class="nav">
        <a class="active" href="#">Overview</a>
        <a href="#users">Users</a>
        <a href="#artists">Artists</a>
        <a href="#albums">Albums</a>
        <a href="#tracks">Tracks</a>
        <a href="../backend/logout.php">Logout</a>
      </nav>
    </aside>

    <main class="main">
      <div class="topbar">
        <h1>Admin Dashboard</h1>
        <div class="userbox">
          <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
          <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
        </div>
      </div>

      <?php if ($msg !== ''): ?>
        <div class="message"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>

      <section class="cards">
        <div class="card">
          <h3>Total Users</h3>
          <strong><?php echo (int)$stats['total_users']; ?></strong>
        </div>
        <div class="card">
          <h3>Artists</h3>
          <strong><?php echo (int)$stats['total_artists']; ?></strong>
        </div>
        <div class="card">
          <h3>Tracks</h3>
          <strong><?php echo (int)$stats['total_tracks']; ?></strong>
        </div>
        <div class="card"><h3>Streams</h3><strong><?php echo (int)$stats['total_streams']; ?></strong></div>
        <div class="card"><h3>Favorites</h3><strong><?php echo (int)$stats['total_favorites']; ?></strong></div>
        <div class="card"><h3>Ratings</h3><strong><?php echo (int)$stats['total_ratings']; ?></strong></div>
      </section>

      <section class="section" id="analytics">
        <h2>Analytics</h2>
        <p>Streams this week: <strong><?php echo $weeklyStreams; ?></strong> · Streams this month: <strong><?php echo $monthlyStreams; ?></strong></p>
        <h3>Top tracks this week</h3>
        <table><thead><tr><th>Track</th><th>Artist</th><th>Streams</th></tr></thead><tbody>
          <?php foreach ($topWeekly as $row): ?><tr><td><?php echo htmlspecialchars($row['title']); ?></td><td><?php echo htmlspecialchars($row['artist_name']); ?></td><td><?php echo (int)$row['stream_count']; ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <h3>Device usage</h3>
        <table><thead><tr><th>Device</th><th>Streams</th></tr></thead><tbody>
          <?php foreach ($deviceUsage as $row): ?><tr><td><?php echo htmlspecialchars($row['device_type']); ?></td><td><?php echo (int)$row['stream_count']; ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <h3>Monthly genre distribution</h3>
        <table><thead><tr><th>Genre</th><th>Streams</th></tr></thead><tbody>
          <?php foreach ($genreUsage as $row): ?><tr><td><?php echo htmlspecialchars($row['genre_name']); ?></td><td><?php echo (int)$row['stream_count']; ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <h3>Most active listeners this month</h3>
        <table><thead><tr><th>User</th><th>Streams</th></tr></thead><tbody>
          <?php foreach ($activeListeners as $row): ?><tr><td><?php echo htmlspecialchars($row['username']); ?></td><td><?php echo (int)$row['stream_count']; ?></td></tr><?php endforeach; ?>
        </tbody></table>
      </section>

      <section class="section" id="users">
        <h2>Artist Applications</h2>
        <?php if ($artistApplications): ?>
          <table>
            <thead><tr><th>Applicant</th><th>Email</th><th>Artist profile</th><th>Applied</th><th>Review</th></tr></thead>
            <tbody>
              <?php foreach ($artistApplications as $application): ?>
                <tr>
                  <td><?php echo e($application['username']); ?></td>
                  <td><?php echo e($application['email']); ?></td>
                  <td><?php echo e($application['artist_name']); ?></td>
                  <td><?php echo e($application['requested_at']); ?></td>
                  <td>
                    <form action="../backend/admin_actions.php" method="POST" style="display:flex;gap:8px;">
                      <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                      <input type="hidden" name="action" value="review_artist_application">
                      <input type="hidden" name="user_id" value="<?php echo (int) $application['user_id']; ?>">
                      <button class="btn primary" type="submit" name="decision" value="approved">Approve</button>
                      <button class="btn danger" type="submit" name="decision" value="rejected">Reject</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No pending artist applications.</p>
        <?php endif; ?>

        <h2>Users</h2>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Plan</th>
              <th>Role</th>
              <th>Created</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?php echo (int)$user['user_id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><span class="badge <?php echo ($user['subscription_type'] === 'free') ? 'free' : ''; ?>"><?php echo htmlspecialchars($user['subscription_type']); ?></span></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                <td>
                  <?php if (($user['role'] ?? '') !== 'admin'): ?>
                    <form action="../backend/admin_actions.php" method="POST" onsubmit="return confirm('Delete this user?');">
                      <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                      <button class="btn danger" type="submit">Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="section" id="artists">
        <h2>Add Artist</h2>
        <form class="grid" action="../backend/admin_actions.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
          <input type="hidden" name="action" value="add_artist">
          <div class="field">
            <label>Artist Name</label>
            <input type="text" name="artist_name" required>
          </div>
          <div class="field">
            <label>Genre</label>
            <input type="text" name="genre">
          </div>
          <div class="field">
            <label>Country</label>
            <input type="text" name="country">
          </div>
          <div class="field" style="justify-content:end;">
            <button class="btn primary" type="submit">Add Artist</button>
          </div>
        </form>

        <table style="margin-top:20px;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Genre</th>
              <th>Country</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($artists as $artist): ?>
              <tr>
                <td><?php echo (int)$artist['artist_id']; ?></td>
                <td><?php echo htmlspecialchars($artist['artist_name']); ?></td>
                <td><?php echo htmlspecialchars($artist['genre'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($artist['country'] ?? '-'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="section" id="albums">
        <h2>Add Album</h2>
        <form class="grid" action="../backend/admin_actions.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
          <input type="hidden" name="action" value="add_album">
          <div class="field">
            <label>Artist</label>
            <select name="artist_id" required>
              <?php foreach ($artists as $artist): ?>
                <option value="<?php echo (int)$artist['artist_id']; ?>"><?php echo htmlspecialchars($artist['artist_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Album Title</label>
            <input type="text" name="title" required>
          </div>
          <div class="field">
            <label>Release Date</label>
            <input type="date" name="release_date">
          </div>
          <div class="field">
            <label>Album Type</label>
            <input type="text" name="album_type" placeholder="Studio / Soundtrack">
          </div>
          <div class="field" style="justify-content:end;">
            <button class="btn primary" type="submit">Add Album</button>
          </div>
        </form>

        <table style="margin-top:20px;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Artist</th>
              <th>Release</th>
              <th>Type</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($albums as $album): ?>
              <tr>
                <td><?php echo (int)$album['album_id']; ?></td>
                <td><?php echo htmlspecialchars($album['title']); ?></td>
                <td><?php echo htmlspecialchars($album['artist_name']); ?></td>
                <td><?php echo htmlspecialchars($album['release_date'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($album['album_type'] ?? '-'); ?></td>
                <td><form action="../backend/admin_actions.php" method="POST" onsubmit="return confirm('Delete this album and its tracks?');"><input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="action" value="delete_album"><input type="hidden" name="album_id" value="<?php echo (int)$album['album_id']; ?>"><button class="btn danger" type="submit">Delete</button></form></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="section" id="tracks">
        <h2>Add Track</h2>
        <form class="grid" action="../backend/admin_actions.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
          <input type="hidden" name="action" value="add_track">
          <div class="field">
            <label>Album</label>
            <select name="album_id" required>
              <?php foreach ($albums as $album): ?>
                <option value="<?php echo (int)$album['album_id']; ?>"><?php echo htmlspecialchars($album['title']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Track Title</label>
            <input type="text" name="track_title" required>
          </div>
          <div class="field">
            <label>Duration (seconds)</label>
            <input type="number" name="duration_seconds" value="180">
          </div>
          <div class="field">
            <label>Track Number</label>
            <input type="number" name="track_number" value="1">
          </div>
          <div class="field">
            <label>Explicit</label>
            <select name="explicit">
              <option value="0">No</option>
              <option value="1">Yes</option>
            </select>
          </div>
          <div class="field">
            <label>Audio file</label>
            <input type="file" name="audio_file" accept="audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/mp4" required>
          </div>
          <div class="field" style="justify-content:end;">
            <button class="btn primary" type="submit">Add Track</button>
          </div>
        </form>

        <table style="margin-top:20px;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Album</th>
              <th>Duration</th>
              <th>Explicit</th>
              <th>Track #</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tracks as $track): ?>
              <tr>
                <td><?php echo (int)$track['track_id']; ?></td>
                <td><?php echo htmlspecialchars($track['title']); ?></td>
                <td><?php echo htmlspecialchars($track['album_title']); ?></td>
                <td><?php echo (int)$track['duration_seconds']; ?>s</td>
                <td><?php echo ($track['explicit'] ? 'Yes' : 'No'); ?></td>
                <td><?php echo (int)$track['track_number']; ?></td>
                <td>
                  <form action="../backend/admin_actions.php" method="POST" enctype="multipart/form-data" style="display:grid;gap:8px;min-width:190px;margin-bottom:8px;">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                    <input type="hidden" name="action" value="replace_track_audio">
                    <input type="hidden" name="track_id" value="<?php echo (int)$track['track_id']; ?>">
                    <input type="file" name="audio_file" accept="audio/mpeg,audio/mp3" required>
                    <button class="btn primary" type="submit">Replace Audio</button>
                  </form>
                  <form action="../backend/admin_actions.php" method="POST" onsubmit="return confirm('Delete this track?');"><input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="action" value="delete_track"><input type="hidden" name="track_id" value="<?php echo (int)$track['track_id']; ?>"><button class="btn danger" type="submit">Delete</button></form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>
