<?php
require_once __DIR__ . '/functions.php';

$userId = requireUser();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verifyCsrf($_POST['csrf_token'] ?? null);

$action = $_POST['action'] ?? '';
$trackId = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT) ?: 0;
$artistId = filter_input(INPUT_POST, 'artist_id', FILTER_VALIDATE_INT) ?: 0;
$playlistId = filter_input(INPUT_POST, 'playlist_id', FILTER_VALIDATE_INT) ?: 0;
$actionSucceeded = true;

try {
    if ($action === 'favorite' && $trackId > 0) {
        $stmt = $pdo->prepare('SELECT 1 FROM favorites WHERE user_id = :user_id AND track_id = :track_id');
        $stmt->execute(['user_id' => $userId, 'track_id' => $trackId]);
        if ($stmt->fetchColumn()) {
            $stmt = $pdo->prepare('DELETE FROM favorites WHERE user_id = :user_id AND track_id = :track_id');
            $stmt->execute(['user_id' => $userId, 'track_id' => $trackId]);
            flash('success', 'Removed from favorites.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO favorites (user_id, track_id) VALUES (:user_id, :track_id)');
            $stmt->execute(['user_id' => $userId, 'track_id' => $trackId]);
            flash('success', 'Added to favorites.');
        }
    } elseif ($action === 'rate' && $trackId > 0) {
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        if (!$rating || $rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }
        $stmt = $pdo->prepare('INSERT INTO ratings (user_id, track_id, rating) VALUES (:user_id, :track_id, :rating) ON DUPLICATE KEY UPDATE rating = VALUES(rating), rated_at = CURRENT_TIMESTAMP');
        $stmt->execute(['user_id' => $userId, 'track_id' => $trackId, 'rating' => $rating]);
        flash('success', 'Rating saved.');
    } elseif ($action === 'follow' && $artistId > 0) {
        $stmt = $pdo->prepare('SELECT 1 FROM artist_follows WHERE user_id = :user_id AND artist_id = :artist_id');
        $stmt->execute(['user_id' => $userId, 'artist_id' => $artistId]);
        if ($stmt->fetchColumn()) {
            $stmt = $pdo->prepare('DELETE FROM artist_follows WHERE user_id = :user_id AND artist_id = :artist_id');
            $stmt->execute(['user_id' => $userId, 'artist_id' => $artistId]);
            flash('success', 'Artist unfollowed.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO artist_follows (user_id, artist_id) VALUES (:user_id, :artist_id)');
            $stmt->execute(['user_id' => $userId, 'artist_id' => $artistId]);
            flash('success', 'Artist followed.');
        }
    } elseif ($action === 'stream' && $trackId > 0) {
        $duration = filter_input(INPUT_POST, 'duration_played', FILTER_VALIDATE_INT);
        recordStream($pdo, $userId, $trackId, $_POST['device_type'] ?? 'web', $_POST['session_id'] ?? session_id(), $duration ?: null, !empty($_POST['completed']));
    } elseif ($action === 'change_subscription') {
        $plan = $_POST['plan'] ?? '';
        if (!in_array($plan, ['free', 'premium'], true)) {
            throw new InvalidArgumentException('Invalid subscription plan.');
        }

        $pdo->beginTransaction();
        if ($plan === 'premium') {
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelled', end_date = CURRENT_DATE WHERE user_id = :user_id AND status = 'active'");
            $stmt->execute(['user_id' => $userId]);
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_name, start_date, status, amount) VALUES (:user_id, 'Premium', CURRENT_DATE, 'active', 9.99)");
            $stmt->execute(['user_id' => $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelled', end_date = CURRENT_DATE WHERE user_id = :user_id AND status = 'active'");
            $stmt->execute(['user_id' => $userId]);
        }
        $stmt = $pdo->prepare('UPDATE users SET subscription_type = :plan WHERE user_id = :user_id');
        $stmt->execute(['plan' => $plan, 'user_id' => $userId]);
        $pdo->commit();
        $_SESSION['subscription_type'] = $plan;
        flash('success', $plan === 'premium' ? 'Premium plan activated.' : 'Premium plan cancelled.');
    } elseif ($action === 'create_playlist') {
        $name = trim($_POST['playlist_name'] ?? '');
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('Playlist name is required.');
        }
        $stmt = $pdo->prepare('INSERT INTO playlists (user_id, playlist_name, is_public) VALUES (:user_id, :playlist_name, 0)');
        $stmt->execute(['user_id' => $userId, 'playlist_name' => $name]);
        flash('success', 'Playlist created.');
    } elseif ($action === 'add_to_playlist' && $trackId > 0 && $playlistId > 0) {
        $stmt = $pdo->prepare('INSERT INTO playlist_tracks (playlist_id, track_id, track_order) SELECT p.playlist_id, :track_id, COALESCE(MAX(pt.track_order), 0) + 1 FROM playlists p LEFT JOIN playlist_tracks pt ON pt.playlist_id = p.playlist_id WHERE p.playlist_id = :playlist_id AND p.user_id = :user_id GROUP BY p.playlist_id ON DUPLICATE KEY UPDATE track_order = VALUES(track_order)');
        $stmt->execute(['track_id' => $trackId, 'playlist_id' => $playlistId, 'user_id' => $userId]);
        flash('success', 'Track added to playlist.');
    } elseif ($action === 'remove_from_playlist' && $trackId > 0 && $playlistId > 0) {
        $stmt = $pdo->prepare('DELETE pt FROM playlist_tracks pt JOIN playlists p ON p.playlist_id = pt.playlist_id WHERE pt.playlist_id = :playlist_id AND pt.track_id = :track_id AND p.user_id = :user_id');
        $stmt->execute(['track_id' => $trackId, 'playlist_id' => $playlistId, 'user_id' => $userId]);
        flash('success', 'Track removed from playlist.');
    } elseif ($action === 'rename_playlist' && $playlistId > 0) {
        $name = trim($_POST['playlist_name'] ?? '');
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('Playlist name is required.');
        }
        $stmt = $pdo->prepare('UPDATE playlists SET playlist_name = :playlist_name WHERE playlist_id = :playlist_id AND user_id = :user_id');
        $stmt->execute(['playlist_name' => $name, 'playlist_id' => $playlistId, 'user_id' => $userId]);
        flash('success', 'Playlist renamed.');
    } elseif ($action === 'delete_playlist' && $playlistId > 0) {
        $stmt = $pdo->prepare('DELETE FROM playlists WHERE playlist_id = :playlist_id AND user_id = :user_id');
        $stmt->execute(['playlist_id' => $playlistId, 'user_id' => $userId]);
        flash('success', 'Playlist deleted.');
        $redirect = 'playlists.php';
    } elseif ($action === 'toggle_playlist_visibility' && $playlistId > 0) {
        $stmt = $pdo->prepare('UPDATE playlists SET is_public = NOT is_public WHERE playlist_id = :playlist_id AND user_id = :user_id');
        $stmt->execute(['playlist_id' => $playlistId, 'user_id' => $userId]);
        flash('success', 'Playlist visibility updated.');
    } elseif ($action === 'reorder_playlist' && $playlistId > 0) {
        $orders = $_POST['track_order'] ?? [];
        if (!is_array($orders)) {
            throw new InvalidArgumentException('Invalid playlist order.');
        }
        $pdo->beginTransaction();
        $check = $pdo->prepare('SELECT 1 FROM playlists WHERE playlist_id = :playlist_id AND user_id = :user_id');
        $check->execute(['playlist_id' => $playlistId, 'user_id' => $userId]);
        if (!$check->fetchColumn()) {
            throw new InvalidArgumentException('Playlist not found.');
        }
        $update = $pdo->prepare('UPDATE playlist_tracks SET track_order = :track_order WHERE playlist_id = :playlist_id AND track_id = :track_id');
        foreach ($orders as $orderedTrackId => $order) {
            $order = filter_var($order, FILTER_VALIDATE_INT);
            $orderedTrackId = filter_var($orderedTrackId, FILTER_VALIDATE_INT);
            if ($order < 1 || !$orderedTrackId) {
                throw new InvalidArgumentException('Invalid playlist order.');
            }
            $update->execute(['track_order' => $order, 'playlist_id' => $playlistId, 'track_id' => $orderedTrackId]);
        }
        $pdo->commit();
        flash('success', 'Playlist order updated.');
    } else {
        throw new InvalidArgumentException('Unknown action.');
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $actionSucceeded = false;
    flash('error', 'The requested action could not be completed.');
}

$redirect = $_POST['redirect'] ?? 'user-dashbord.php';
if (!str_contains($redirect, '/') && !str_contains($redirect, ':')) {
    header('Location: ' . $redirect);
} else {
    header('Location: user-dashbord.php');
}
exit;
