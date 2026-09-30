<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function coverImageUrl(?string $coverImage): string
{
    $coverImage = trim((string) $coverImage);
    $isRemoteCover = filter_var($coverImage, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $coverImage);
    $localCoverPath = $coverImage !== '' ? __DIR__ . '/../' . ltrim(parse_url($coverImage, PHP_URL_PATH) ?: $coverImage, '/') : '';

    if ($isRemoteCover || ($coverImage !== '' && is_file($localCoverPath))) {
        return $coverImage;
    }

    return '../frontend/cover-placeholder.svg';
}

function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function requireUser(): int
{
    redirectIfNotLoggedIn('../frontend/user-login.php');
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin-dashboard.php');
        exit;
    }
    return currentUserId();
}

function requireArtist(): int
{
    redirectIfNotLoggedIn('../frontend/user-login.php');
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin-dashboard.php');
        exit;
    }

    ensureArtistAccountsTable($GLOBALS['pdo']);
    $stmt = $GLOBALS['pdo']->prepare("SELECT artist_id FROM artist_accounts WHERE user_id = :user_id AND status = 'approved'");
    $stmt->execute(['user_id' => currentUserId()]);
    $artistId = (int) $stmt->fetchColumn();
    if ($artistId <= 0) {
        header('Location: artist-application-status.php');
        exit;
    }

    $_SESSION['artist_id'] = $artistId;
    return $artistId;
}

function ensureArtistAccountsTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS artist_accounts (
            user_id INT NOT NULL,
            artist_id INT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (user_id),
            UNIQUE KEY uq_artist_accounts_artist (artist_id),
            CONSTRAINT fk_artist_accounts_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
            CONSTRAINT fk_artist_accounts_artist FOREIGN KEY (artist_id) REFERENCES artists (artist_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
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

function addNotification(PDO $pdo, int $userId, string $type, string $message, ?string $link = null): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, notification_type, message, link) VALUES (:user_id, :notification_type, :message, :link)');
    $stmt->execute(['user_id' => $userId, 'notification_type' => $type, 'message' => $message, 'link' => $link]);
}

function loginRateLimitExceeded(PDO $pdo, string $email, string $ipAddress): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = :email AND ip_address = :ip_address AND succeeded = 0 AND attempted_at >= CURRENT_TIMESTAMP - INTERVAL 15 MINUTE');
    $stmt->execute(['email' => $email, 'ip_address' => $ipAddress]);
    return (int) $stmt->fetchColumn() >= 5;
}

function recordLoginAttempt(PDO $pdo, string $email, string $ipAddress, bool $succeeded): void
{
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, succeeded) VALUES (:email, :ip_address, :succeeded)');
    $stmt->execute(['email' => $email, 'ip_address' => $ipAddress, 'succeeded' => $succeeded ? 1 : 0]);
    if ($succeeded) {
        $cleanup = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email AND ip_address = :ip_address');
        $cleanup->execute(['email' => $email, 'ip_address' => $ipAddress]);
    }
}

function trackQuery(): string
{
    global $pdo;
    static $hasTrackArtists = null;

    if ($hasTrackArtists === null) {
        try {
            $tableCheck = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'track_artists' LIMIT 1");
            $hasTrackArtists = (bool) $tableCheck->fetchColumn();
        } catch (PDOException $exception) {
            $hasTrackArtists = false;
        }
    }

    $artistNames = $hasTrackArtists
        ? '(SELECT GROUP_CONCAT(a2.artist_name ORDER BY ta2.display_order, a2.artist_name SEPARATOR \' & \') FROM track_artists ta2 JOIN artists a2 ON a2.artist_id = ta2.artist_id WHERE ta2.track_id = t.track_id)'
        : 'ar.artist_name';

    return 'SELECT t.track_id, t.title, t.duration_seconds, t.track_number, t.audio_url, t.cover_image, '
        . 't.lyrics, al.album_id, al.title AS album_title, al.cover_image AS album_cover, '
        . 'ar.artist_id, ar.artist_name, ' . $artistNames . ' AS artist_names FROM tracks t '
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

    if (($file['size'] ?? 0) <= 0 || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The uploaded audio file is invalid.');
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
    if (!isset($allowedMimeTypes[$mimeType]) || !in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'mp4'], true)) {
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
