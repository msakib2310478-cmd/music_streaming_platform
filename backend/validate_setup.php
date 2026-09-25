<?php
require_once __DIR__ . '/db.php';

$requiredTables = [
    'users', 'artists', 'albums', 'tracks', 'playlists', 'stream_history',
    'genres', 'track_genres', 'playlist_tracks', 'favorites', 'artist_follows',
    'ratings', 'search_history', 'subscriptions', 'playback_queue',
    'playlist_collaborators', 'track_artists',
];
$requiredViews = [
    'vw_user_listening_daily', 'vw_track_chart_metrics',
    'vw_trending_tracks', 'vw_artist_dashboard_metrics',
];
$requiredColumns = [
    'tracks' => ['audio_url', 'cover_image', 'lyrics'],
    'stream_history' => ['duration_played', 'completed'],
    'artists' => ['bio', 'profile_image', 'verified'],
    'albums' => ['cover_image', 'description'],
];

$missingTables = [];
$missingViews = [];
$missingColumns = [];
$tableCheck = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name AND table_type = \'BASE TABLE\'');
foreach ($requiredTables as $table) {
    $tableCheck->execute(['table_name' => $table]);
    if ((int)$tableCheck->fetchColumn() !== 1) {
        $missingTables[] = $table;
    }
}

$viewCheck = $pdo->prepare('SELECT COUNT(*) FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = :view_name');
foreach ($requiredViews as $view) {
    $viewCheck->execute(['view_name' => $view]);
    if ((int)$viewCheck->fetchColumn() !== 1) {
        $missingViews[] = $view;
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

if ($missingTables || $missingViews || $missingColumns) {
    fwrite(STDERR, "Database setup is incomplete.\n");
    if ($missingTables) {
        fwrite(STDERR, 'Missing tables: ' . implode(', ', $missingTables) . "\n");
    }
    if ($missingViews) {
        fwrite(STDERR, 'Missing views: ' . implode(', ', $missingViews) . "\n");
    }
    if ($missingColumns) {
        fwrite(STDERR, 'Missing columns: ' . implode(', ', $missingColumns) . "\n");
    }
    exit(1);
}

echo 'Database setup is valid: ' . count($requiredTables) . " required tables, " . count($requiredViews) . " reporting views, and all required columns found.\n";