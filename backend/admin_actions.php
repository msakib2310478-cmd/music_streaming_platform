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
        $audioUrl = null;

        if ($albumId <= 0 || $title === '') {
            throw new InvalidArgumentException('Choose an album and enter a track title.');
        }

        if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('Choose an MP3 audio file.');
        }

        if ($_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The MP3 is larger than PHP upload_max_filesize (40 MB).',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded MP3 is too large for the form.',
                UPLOAD_ERR_PARTIAL => 'The MP3 upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'PHP temporary upload storage is unavailable.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded MP3.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the MP3 upload.',
            ];
            throw new RuntimeException($uploadErrors[$_FILES['audio_file']['error']] ?? 'Audio upload failed.');
        }

        if ($_FILES['audio_file']['size'] > 35 * 1024 * 1024) {
            throw new RuntimeException('The MP3 must be smaller than 35 MB.');
        }

        $allowedMimeTypes = [
            'audio/mp3' => 'mp3',
            'audio/wav' => 'wav',
            'audio/mpeg' => 'mp3',
            'audio/x-mpeg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'audio/x-mpeg-3' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/x-mp3' => 'mp3',
            'audio/mpa' => 'mp3',
            'application/x-id3' => 'mp3',
            'application/x-id3v2' => 'mp3',
            'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
        ];
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['audio_file']['tmp_name']);
        $extension = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        if (!isset($allowedMimeTypes[$mimeType]) && $extension === 'mp3') {
            $mimeType = 'audio/mpeg';
        }
        if (!isset($allowedMimeTypes[$mimeType])) {
            throw new RuntimeException('Unsupported audio format detected: ' . $mimeType);
        }

        $uploadDirectory = __DIR__ . '/uploads/audio';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Audio upload directory could not be created.');
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mimeType];
        if (!move_uploaded_file($_FILES['audio_file']['tmp_name'], $uploadDirectory . '/' . $fileName)) {
            throw new RuntimeException('Audio file could not be stored.');
        }
        $audioUrl = 'uploads/audio/' . $fileName;
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

header('Location: admin-dashboard.php');
exit;
