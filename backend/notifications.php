<?php
require_once __DIR__ . '/functions.php';

$userId = requireUser();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf($_POST['csrf_token'] ?? null);
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    echo json_encode(['ok' => true]);
    exit;
}

$stmt = $pdo->prepare('SELECT notification_id, notification_type, message, link, is_read, created_at FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 12');
$stmt->execute(['user_id' => $userId]);
echo json_encode(['items' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);