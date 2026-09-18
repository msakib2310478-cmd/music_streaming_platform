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
    return 'SELECT t.track_id, t.title, t.duration_seconds, t.audio_url, t.cover_image, '
        . 't.lyrics, al.album_id, al.title AS album_title, al.cover_image AS album_cover, '
        . 'ar.artist_id, ar.artist_name FROM tracks t '
        . 'JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id';
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
