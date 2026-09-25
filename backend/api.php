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
$queueId = filter_input(INPUT_POST, 'queue_id', FILTER_VALIDATE_INT) ?: 0;
$collaboratorId = filter_input(INPUT_POST, 'collaborator_id', FILTER_VALIDATE_INT) ?: 0;
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
    } elseif ($action === 'queue_add' && $trackId > 0) {
        $trackCheck = $pdo->prepare('SELECT 1 FROM tracks WHERE track_id = :track_id');
        $trackCheck->execute(['track_id' => $trackId]);
        if (!$trackCheck->fetchColumn()) {
            throw new InvalidArgumentException('Track not found.');
        }
        $positionStmt = $pdo->prepare('SELECT COALESCE(MAX(queue_position), 0) + 1 FROM playback_queue WHERE user_id = :user_id');
        $positionStmt->execute(['user_id' => $userId]);
        $insert = $pdo->prepare('INSERT INTO playback_queue (user_id, track_id, queue_position) VALUES (:user_id, :track_id, :queue_position)');
        $insert->execute(['user_id' => $userId, 'track_id' => $trackId, 'queue_position' => (int)$positionStmt->fetchColumn()]);
        flash('success', 'Track added to Up Next.');
    } elseif ($action === 'queue_remove' && $queueId > 0) {
        $delete = $pdo->prepare('DELETE FROM playback_queue WHERE queue_id = :queue_id AND user_id = :user_id');
        $delete->execute(['queue_id' => $queueId, 'user_id' => $userId]);
        normalizeQueue($pdo, $userId);
        flash('success', 'Track removed from Up Next.');
    } elseif ($action === 'queue_move' && $queueId > 0) {
        $direction = $_POST['direction'] ?? '';
        if (!in_array($direction, ['up', 'down'], true)) {
            throw new InvalidArgumentException('Invalid queue direction.');
        }
        $current = $pdo->prepare('SELECT queue_position FROM playback_queue WHERE queue_id = :queue_id AND user_id = :user_id');
        $current->execute(['queue_id' => $queueId, 'user_id' => $userId]);
        $position = $current->fetchColumn();
        if ($position === false) {
            throw new InvalidArgumentException('Queue item not found.');
        }
        $neighborPosition = (int)$position + ($direction === 'up' ? -1 : 1);
        $neighbor = $pdo->prepare('SELECT queue_id FROM playback_queue WHERE user_id = :user_id AND queue_position = :queue_position');
        $neighbor->execute(['user_id' => $userId, 'queue_position' => $neighborPosition]);
        $neighborId = $neighbor->fetchColumn();
        if ($neighborId !== false) {
            $pdo->beginTransaction();
            $temporary = (int)$pdo->query('SELECT COALESCE(MAX(queue_position), 0) + 1 FROM playback_queue')->fetchColumn();
            $swap = $pdo->prepare('UPDATE playback_queue SET queue_position = :position WHERE queue_id = :queue_id AND user_id = :user_id');
            $swap->execute(['position' => $temporary, 'queue_id' => $queueId, 'user_id' => $userId]);
            $swap->execute(['position' => (int)$position, 'queue_id' => $neighborId, 'user_id' => $userId]);
            $swap->execute(['position' => $neighborPosition, 'queue_id' => $queueId, 'user_id' => $userId]);
            $pdo->commit();
        }
        flash('success', 'Up Next order updated.');
    } elseif ($action === 'queue_clear') {
        $delete = $pdo->prepare('DELETE FROM playback_queue WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);
        flash('success', 'Up Next cleared.');
    } elseif ($action === 'queue_reorder') {
        $orderedQueueIds = $_POST['queue_order'] ?? [];
        if (!is_array($orderedQueueIds)) {
            throw new InvalidArgumentException('Invalid queue order.');
        }
        $pdo->beginTransaction();
        $update = $pdo->prepare('UPDATE playback_queue SET queue_position = :position WHERE queue_id = :queue_id AND user_id = :user_id');
        foreach (array_values($orderedQueueIds) as $position => $orderedQueueId) {
            $orderedQueueId = filter_var($orderedQueueId, FILTER_VALIDATE_INT);
            if (!$orderedQueueId) {
                throw new InvalidArgumentException('Invalid queue item.');
            }
            $ownership = $pdo->prepare('SELECT 1 FROM playback_queue WHERE queue_id = :queue_id AND user_id = :user_id');
            $ownership->execute(['queue_id' => $orderedQueueId, 'user_id' => $userId]);
            if (!$ownership->fetchColumn()) {
                throw new InvalidArgumentException('Queue item does not belong to this user.');
            }
            $update->execute(['position' => $position + 1, 'queue_id' => $orderedQueueId, 'user_id' => $userId]);
        }
        $pdo->commit();
        normalizeQueue($pdo, $userId);
        flash('success', 'Up Next order updated.');
    } elseif ($action === 'invite_collaborator' && $playlistId > 0) {
        if (!playlistIsOwner($pdo, $playlistId, $userId)) {
            throw new RuntimeException('Only the playlist owner can invite collaborators.');
        }
        $email = trim(strtolower($_POST['collaborator_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid collaborator email.');
        }
        $lookup = $pdo->prepare('SELECT user_id FROM users WHERE email = :email AND role = \'user\' LIMIT 1');
        $lookup->execute(['email' => $email]);
        $inviteeId = (int)$lookup->fetchColumn();
        if (!$inviteeId || $inviteeId === $userId) {
            throw new InvalidArgumentException('That user cannot be added as a collaborator.');
        }
        $insert = $pdo->prepare('INSERT INTO playlist_collaborators (playlist_id, user_id, invited_by) VALUES (:playlist_id, :user_id, :invited_by) ON DUPLICATE KEY UPDATE invited_by = VALUES(invited_by)');
        $insert->execute(['playlist_id' => $playlistId, 'user_id' => $inviteeId, 'invited_by' => $userId]);
        flash('success', 'Collaborator added.');
    } elseif ($action === 'remove_collaborator' && $playlistId > 0 && $collaboratorId > 0) {
        if (!playlistIsOwner($pdo, $playlistId, $userId)) {
            throw new RuntimeException('Only the playlist owner can remove collaborators.');
        }
        $delete = $pdo->prepare('DELETE FROM playlist_collaborators WHERE playlist_id = :playlist_id AND user_id = :user_id');
        $delete->execute(['playlist_id' => $playlistId, 'user_id' => $collaboratorId]);
        flash('success', 'Collaborator removed.');
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
    } elseif ($action === 'add_to_playlist' && $trackId > 0 && $playlistId > 0 && playlistCanEdit($pdo, $playlistId, $userId)) {
        $stmt = $pdo->prepare('INSERT INTO playlist_tracks (playlist_id, track_id, track_order) SELECT p.playlist_id, :track_id, COALESCE(MAX(pt.track_order), 0) + 1 FROM playlists p LEFT JOIN playlist_tracks pt ON pt.playlist_id = p.playlist_id WHERE p.playlist_id = :playlist_id GROUP BY p.playlist_id ON DUPLICATE KEY UPDATE track_order = VALUES(track_order)');
        $stmt->execute(['track_id' => $trackId, 'playlist_id' => $playlistId]);
        flash('success', 'Track added to playlist.');
    } elseif ($action === 'remove_from_playlist' && $trackId > 0 && $playlistId > 0 && playlistCanEdit($pdo, $playlistId, $userId)) {
        $stmt = $pdo->prepare('DELETE FROM playlist_tracks WHERE playlist_id = :playlist_id AND track_id = :track_id');
        $stmt->execute(['track_id' => $trackId, 'playlist_id' => $playlistId]);
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
    } elseif ($action === 'playlist_move' && $trackId > 0 && $playlistId > 0 && playlistCanEdit($pdo, $playlistId, $userId)) {
        $direction = $_POST['direction'] ?? '';
        if (!in_array($direction, ['up', 'down'], true)) {
            throw new InvalidArgumentException('Invalid playlist direction.');
        }
        $current = $pdo->prepare('SELECT track_order FROM playlist_tracks WHERE playlist_id = :playlist_id AND track_id = :track_id');
        $current->execute(['playlist_id' => $playlistId, 'track_id' => $trackId]);
        $position = $current->fetchColumn();
        if ($position === false) {
            throw new InvalidArgumentException('Playlist track not found.');
        }
        $neighborPosition = (int)$position + ($direction === 'up' ? -1 : 1);
        $neighbor = $pdo->prepare('SELECT track_id FROM playlist_tracks WHERE playlist_id = :playlist_id AND track_order = :track_order');
        $neighbor->execute(['playlist_id' => $playlistId, 'track_order' => $neighborPosition]);
        $neighborId = $neighbor->fetchColumn();
        if ($neighborId !== false) {
            $pdo->beginTransaction();
            $temporaryStmt = $pdo->prepare('SELECT COALESCE(MAX(track_order), 0) + 1 FROM playlist_tracks WHERE playlist_id = :playlist_id');
            $temporaryStmt->execute(['playlist_id' => $playlistId]);
            $temporary = (int)$temporaryStmt->fetchColumn();
            $swap = $pdo->prepare('UPDATE playlist_tracks SET track_order = :track_order WHERE playlist_id = :playlist_id AND track_id = :track_id');
            $swap->execute(['track_order' => $temporary, 'playlist_id' => $playlistId, 'track_id' => $trackId]);
            $swap->execute(['track_order' => (int)$position, 'playlist_id' => $playlistId, 'track_id' => $neighborId]);
            $swap->execute(['track_order' => $neighborPosition, 'playlist_id' => $playlistId, 'track_id' => $trackId]);
            $pdo->commit();
        }
        flash('success', 'Playlist order updated.');
    } elseif ($action === 'reorder_playlist' && $playlistId > 0 && playlistCanEdit($pdo, $playlistId, $userId)) {
        $orders = $_POST['track_order'] ?? [];
        if (!is_array($orders)) {
            throw new InvalidArgumentException('Invalid playlist order.');
        }
        $pdo->beginTransaction();
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
