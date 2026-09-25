<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function requireUser(): int
{
    redirectIfNotLoggedIn('../frontend/user-login.html');
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin-dashboard.php');
        exit;
    }
    return currentUserId();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): void
{
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid request token.');
    }
}

function trackQuery(): string
{
    return 'SELECT t.track_id, t.title, t.duration_seconds, t.track_number, t.audio_url, t.cover_image, '
        . 't.lyrics, al.album_id, al.title AS album_title, al.cover_image AS album_cover, '
        . 'ar.artist_id, ar.artist_name, '
        . '(SELECT GROUP_CONCAT(a2.artist_name ORDER BY ta2.display_order, a2.artist_name SEPARATOR \' & \') '
        . 'FROM track_artists ta2 JOIN artists a2 ON a2.artist_id = ta2.artist_id '
        . 'WHERE ta2.track_id = t.track_id) AS artist_names FROM tracks t '
        . 'JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id';
}

function normalizeQueue(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('SELECT queue_id FROM playback_queue WHERE user_id = :user_id ORDER BY queue_position, queue_id');
    $stmt->execute(['user_id' => $userId]);
    $update = $pdo->prepare('UPDATE playback_queue SET queue_position = :position WHERE queue_id = :queue_id AND user_id = :user_id');
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $position => $queueId) {
        $update->execute(['position' => $position + 1, 'queue_id' => $queueId, 'user_id' => $userId]);
    }
}

function playlistCanEdit(PDO $pdo, int $playlistId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM playlists p WHERE p.playlist_id = :playlist_id AND (p.user_id = :owner_id OR EXISTS (SELECT 1 FROM playlist_collaborators pc WHERE pc.playlist_id = p.playlist_id AND pc.user_id = :collaborator_id))');
    $stmt->execute(['playlist_id' => $playlistId, 'owner_id' => $userId, 'collaborator_id' => $userId]);
    return (bool)$stmt->fetchColumn();
}

function playlistIsOwner(PDO $pdo, int $playlistId, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM playlists WHERE playlist_id = :playlist_id AND user_id = :user_id');
    $stmt->execute(['playlist_id' => $playlistId, 'user_id' => $userId]);
    return (bool)$stmt->fetchColumn();
}

function storeUploadedAudio(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new InvalidArgumentException('Choose an audio file.');
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The audio file is larger than PHP upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded audio file is too large for the form.',
            UPLOAD_ERR_PARTIAL => 'The audio upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'PHP temporary upload storage is unavailable.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded audio file.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the audio upload.',
        ];
        throw new RuntimeException($uploadErrors[$file['error']] ?? 'Audio upload failed.');
    }

    if (($file['size'] ?? 0) > 35 * 1024 * 1024) {
        throw new RuntimeException('The audio file must be smaller than 35 MB.');
    }

    $allowedMimeTypes = [
        'audio/mp3' => 'mp3',
        'audio/mpeg' => 'mp3',
        'audio/x-mpeg' => 'mp3',
        'audio/mpeg3' => 'mp3',
        'audio/x-mpeg-3' => 'mp3',
        'audio/mpg' => 'mp3',
        'audio/x-mp3' => 'mp3',
        'audio/mpa' => 'mp3',
        'application/x-id3' => 'mp3',
        'application/x-id3v2' => 'mp3',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/ogg' => 'ogg',
        'audio/mp4' => 'm4a',
    ];
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
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
    if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . '/' . $fileName)) {
        throw new RuntimeException('Audio file could not be stored.');
    }

    return 'uploads/audio/' . $fileName;
}

function fetchTracks(PDO $pdo, string $orderBy, int $limit = 10, array $params = []): array
{
    $limit = max(1, min($limit, 100));
    $stmt = $pdo->prepare(trackQuery() . ' ' . $orderBy . ' LIMIT ' . $limit);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function userHasFavorite(PDO $pdo, int $userId, int $trackId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM favorites WHERE user_id = :user_id AND track_id = :track_id');
    $stmt->execute(['user_id' => $userId, 'track_id' => $trackId]);
    return (bool)$stmt->fetchColumn();
}

function recordStream(PDO $pdo, int $userId, int $trackId, ?string $deviceType = null, ?string $sessionId = null, ?int $durationPlayed = null, bool $completed = false): void
{
    $valid = $pdo->prepare('SELECT duration_seconds FROM tracks WHERE track_id = :track_id');
    $valid->execute(['track_id' => $trackId]);
    if (!$valid->fetchColumn()) {
        throw new InvalidArgumentException('Track not found.');
    }

    $stmt = $pdo->prepare('INSERT INTO stream_history (user_id, track_id, device_type, session_id, duration_played, completed) VALUES (:user_id, :track_id, :device_type, :session_id, :duration_played, :completed)');
    $stmt->execute([
        'user_id' => $userId,
        'track_id' => $trackId,
        'device_type' => $deviceType ?: 'web',
        'session_id' => $sessionId ?: session_id(),
        'duration_played' => $durationPlayed,
        'completed' => $completed ? 1 : 0,
    ]);
}

function flash(string $type, string $message): void
{
    $_SESSION[$type] = $message;
}
