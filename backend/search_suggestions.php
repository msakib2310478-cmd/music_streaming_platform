<?php
require_once __DIR__ . '/functions.php';
requireUser();

header('Content-Type: application/json; charset=utf-8');
$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$like = '%' . $query . '%';
$suggestions = [];

$artistStmt = $pdo->prepare('SELECT artist_name AS label, \'artist\' AS type FROM artists WHERE artist_name LIKE :query ORDER BY artist_name LIMIT 5');
$artistStmt->execute(['query' => $like]);
$suggestions = array_merge($suggestions, $artistStmt->fetchAll());

$albumStmt = $pdo->prepare('SELECT al.title AS label, \'album\' AS type FROM albums al JOIN artists ar ON ar.artist_id = al.artist_id WHERE al.title LIKE :query OR ar.artist_name LIKE :artist_query ORDER BY al.title LIMIT 5');
$albumStmt->execute(['query' => $like, 'artist_query' => $like]);
$suggestions = array_merge($suggestions, $albumStmt->fetchAll());

$trackStmt = $pdo->prepare('SELECT t.title AS label, \'track\' AS type FROM tracks t JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id WHERE t.title LIKE :track_query OR ar.artist_name LIKE :artist_query ORDER BY t.title LIMIT 7');
$trackStmt->execute(['track_query' => $like, 'artist_query' => $like]);
$suggestions = array_merge($suggestions, $trackStmt->fetchAll());

echo json_encode(array_slice($suggestions, 0, 10), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);