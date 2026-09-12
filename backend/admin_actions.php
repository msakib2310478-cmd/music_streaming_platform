<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

requireRole('admin', '../frontend/admin-login.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'delete_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = :user_id AND role = :role');
        $stmt->execute([':user_id' => $userId, ':role' => 'user']);
    }
    header('Location: admin-dashboard.php?msg=user_deleted');
    exit;
}

if ($action === 'add_artist') {
    $artistName = trim($_POST['artist_name'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($artistName !== '') {
        $stmt = $pdo->prepare('INSERT INTO artists (artist_name, genre, country) VALUES (:artist_name, :genre, :country)');
        $stmt->execute([
            ':artist_name' => $artistName,
            ':genre' => $genre,
            ':country' => $country,
        ]);
    }

    header('Location: admin-dashboard.php?msg=artist_added');
    exit;
}

if ($action === 'add_album') {
    $artistId = (int)($_POST['artist_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $releaseDate = trim($_POST['release_date'] ?? '');
    $albumType = trim($_POST['album_type'] ?? '');

    if ($artistId > 0 && $title !== '') {
        $stmt = $pdo->prepare('INSERT INTO albums (artist_id, title, release_date, album_type) VALUES (:artist_id, :title, :release_date, :album_type)');
        $stmt->execute([
            ':artist_id' => $artistId,
            ':title' => $title,
            ':release_date' => $releaseDate !== '' ? $releaseDate : null,
            ':album_type' => $albumType !== '' ? $albumType : null,
        ]);
    }

    header('Location: admin-dashboard.php?msg=album_added');
    exit;
}

if ($action === 'add_track') {
    $albumId = (int)($_POST['album_id'] ?? 0);
    $title = trim($_POST['track_title'] ?? '');
    $duration = (int)($_POST['duration_seconds'] ?? 0);
    $trackNumber = (int)($_POST['track_number'] ?? 0);
    $explicit = isset($_POST['explicit']) ? 1 : 0;

    if ($albumId > 0 && $title !== '') {
        $stmt = $pdo->prepare('INSERT INTO tracks (album_id, title, duration_seconds, explicit, track_number) VALUES (:album_id, :title, :duration_seconds, :explicit, :track_number)');
        $stmt->execute([
            ':album_id' => $albumId,
            ':title' => $title,
            ':duration_seconds' => $duration,
            ':explicit' => $explicit,
            ':track_number' => $trackNumber,
        ]);
    }

    header('Location: admin-dashboard.php?msg=track_added');
    exit;
}

header('Location: admin-dashboard.php');
exit;
