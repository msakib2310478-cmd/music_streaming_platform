<?php
$host = getenv('MUSIC_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('MUSIC_DB_NAME') ?: 'music_streaming_db';
$dbUser = getenv('MUSIC_DB_USER') ?: 'music_app';
$dbPass = getenv('MUSIC_DB_PASS') ?: 'music_app_local';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Check the server configuration.');
}
?>
