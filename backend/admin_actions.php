<?php
require_once __DIR__ . '/functions.php';

requireRole('admin', '../frontend/admin-login.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-dashboard.php');
    exit;
}

verifyCsrf($_POST['csrf_token'] ?? null);

$action = $_POST['action'] ?? '';

if ($action === 'delete_album') {
    $albumId = (int)($_POST['album_id'] ?? 0);
    if ($albumId > 0) {
        $stmt = $pdo->prepare('DELETE FROM albums WHERE album_id = :album_id');
        $stmt->execute(['album_id' => $albumId]);
    }
    header('Location: admin-dashboard.php?msg=album_deleted');
    exit;
}

if ($action === 'delete_track') {
    $trackId = (int)($_POST['track_id'] ?? 0);
    if ($trackId > 0) {
        $stmt = $pdo->prepare('DELETE FROM tracks WHERE track_id = :track_id');
        $stmt->execute(['track_id' => $trackId]);
    }
    header('Location: admin-dashboard.php?msg=track_deleted');
    exit;
}

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
    try {
        $albumId = (int)($_POST['album_id'] ?? 0);
        $title = trim($_POST['track_title'] ?? '');
        $duration = (int)($_POST['duration_seconds'] ?? 0);
        $trackNumber = (int)($_POST['track_number'] ?? 0);
        $explicit = (int)($_POST['explicit'] ?? 0) === 1 ? 1 : 0;

        if ($albumId <= 0 || $title === '') {
            throw new InvalidArgumentException('Choose an album and enter a track title.');
        }
        $audioUrl = storeUploadedAudio($_FILES['audio_file'] ?? []);
        $stmt = $pdo->prepare('INSERT INTO tracks (album_id, title, duration_seconds, explicit, track_number, audio_url) VALUES (:album_id, :title, :duration_seconds, :explicit, :track_number, :audio_url)');
        $stmt->execute([
            ':album_id' => $albumId,
            ':title' => $title,
            ':duration_seconds' => $duration,
            ':explicit' => $explicit,
            ':track_number' => $trackNumber,
            ':audio_url' => $audioUrl,
        ]);
        flash('success', 'Track added successfully.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }
    header('Location: admin-dashboard.php');
    exit;
}

if ($action === 'replace_track_audio') {
    try {
        $trackId = (int)($_POST['track_id'] ?? 0);
        if ($trackId <= 0) {
            throw new InvalidArgumentException('Invalid track.');
        }

        $audioUrl = storeUploadedAudio($_FILES['audio_file'] ?? []);
        $stmt = $pdo->prepare('UPDATE tracks SET audio_url = :audio_url WHERE track_id = :track_id');
        $stmt->execute([
            ':audio_url' => $audioUrl,
            ':track_id' => $trackId,
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Track was not found.');
        }
        flash('success', 'Track audio replaced successfully.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }
    header('Location: admin-dashboard.php#tracks');
    exit;
}

header('Location: admin-dashboard.php');
exit;
