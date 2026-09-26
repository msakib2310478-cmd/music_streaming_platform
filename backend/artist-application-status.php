<?php
require_once __DIR__ . '/functions.php';
redirectIfNotLoggedIn('../frontend/user-login.html');
ensureArtistAccountsTable($pdo);

$stmt = $pdo->prepare('SELECT aa.status, ar.artist_name FROM artist_accounts aa JOIN artists ar ON ar.artist_id = aa.artist_id WHERE aa.user_id = :user_id');
$stmt->execute(['user_id' => currentUserId()]);
$application = $stmt->fetch();
$status = $application['status'] ?? ($_SESSION['artist_application_status'] ?? 'none');
$artistName = $application['artist_name'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artist application status - PulseFlow</title>
    <link rel="stylesheet" href="../frontend/user-dashbord.css">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #101114; color: #f4f5f7; }
        main { width: min(560px, 100%); padding: 28px; border: 1px solid rgba(255,255,255,.1); background: #191a1e; }
        p { color: #b5bac3; line-height: 1.55; }
        a { color: #7ee29a; }
    </style>
</head>
<body>
<main>
    <h1>Artist dashboard access</h1>
    <?php if ($status === 'pending'): ?>
        <p>Your application for <?php echo e($artistName ?: 'an artist profile'); ?> is awaiting admin approval. Your dashboard will be available after it is approved.</p>
    <?php elseif ($status === 'rejected'): ?>
        <p>Your artist dashboard application was not approved. Contact the site administrator if you think this is a mistake.</p>
    <?php else: ?>
        <p>No artist application is associated with this account yet.</p>
        <p><a href="artist-register.php">Apply for an artist account</a></p>
    <?php endif; ?>
    <p><a href="../backend/logout.php">Log out</a></p>
</main>
</body>
</html>
