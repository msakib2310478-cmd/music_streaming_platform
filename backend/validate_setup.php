<?php
require_once __DIR__ . '/db.php';

$requiredTables = [
    'users', 'artists', 'albums', 'tracks', 'playlists', 'stream_history',
    'genres', 'track_genres', 'playlist_tracks', 'favorites', 'artist_follows',
    'ratings', 'search_history', 'subscriptions',
];
$requiredColumns = [
    'tracks' => ['audio_url', 'cover_image', 'lyrics'],
    'stream_history' => ['duration_played', 'completed'],
    'artists' => ['bio', 'profile_image', 'verified'],
    'albums' => ['cover_image', 'description'],
];

$missingTables = [];
$missingColumns = [];
$tableCheck = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
foreach ($requiredTables as $table) {
    $tableCheck->execute(['table_name' => $table]);
    if ((int)$tableCheck->fetchColumn() !== 1) {
        $missingTables[] = $table;
    }
}

$columnCheck = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name');
foreach ($requiredColumns as $table => $columns) {
    foreach ($columns as $column) {
        $columnCheck->execute(['table_name' => $table, 'column_name' => $column]);
        if ((int)$columnCheck->fetchColumn() !== 1) {
            $missingColumns[] = $table . '.' . $column;
        }
    }
}

if ($missingTables || $missingColumns) {
    fwrite(STDERR, "Database setup is incomplete.\n");
    if ($missingTables) {
        fwrite(STDERR, 'Missing tables: ' . implode(', ', $missingTables) . "\n");
    }
    if ($missingColumns) {
        fwrite(STDERR, 'Missing columns: ' . implode(', ', $missingColumns) . "\n");
    }
    exit(1);
}

echo 'Database setup is valid: ' . count($requiredTables) . " required tables and all required columns found.\n";