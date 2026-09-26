<?php
require_once __DIR__ . '/functions.php';

ensureArtistAccountsTable($pdo);
$artists = $pdo->query('SELECT artist_id, artist_name, genre, country FROM artists ORDER BY artist_name')->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf($_POST['csrf_token'] ?? null);

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $artistId = filter_input(INPUT_POST, 'artist_id', FILTER_VALIDATE_INT) ?: 0;

    if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $password !== $confirmPassword || $artistId <= 0) {
        $error = 'Enter your details, choose your artist profile, and make sure both passwords match (at least 6 characters).';
    } else {
        $artistCheck = $pdo->prepare('SELECT artist_id FROM artists WHERE artist_id = :artist_id');
        $artistCheck->execute(['artist_id' => $artistId]);
        $emailCheck = $pdo->prepare('SELECT user_id FROM users WHERE email = :email');
        $emailCheck->execute(['email' => $email]);
        $artistAccountCheck = $pdo->prepare('SELECT user_id FROM artist_accounts WHERE artist_id = :artist_id');
        $artistAccountCheck->execute(['artist_id' => $artistId]);

        if (!$artistCheck->fetchColumn()) {
            $error = 'Choose a valid artist profile.';
        } elseif ($emailCheck->fetchColumn()) {
            $error = 'An account with that email already exists. Log in or use another email.';
        } elseif ($artistAccountCheck->fetchColumn()) {
            $error = 'An artist account application already exists for that profile. Contact an administrator if it needs correction.';
        } else {
            $username = strtolower(preg_replace('/[^a-z0-9_]/', '', $firstName . '_' . $lastName));
            $username = $username !== '' ? $username : 'artist_' . bin2hex(random_bytes(4));
            $pdo->beginTransaction();
            try {
                $insertUser = $pdo->prepare('INSERT INTO users (username, email, password_hash, subscription_type, role) VALUES (:username, :email, :password_hash, :subscription_type, :role)');
                $insertUser->execute([
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'subscription_type' => 'free',
                    'role' => 'user',
                ]);
                $insertApplication = $pdo->prepare("INSERT INTO artist_accounts (user_id, artist_id, status) VALUES (:user_id, :artist_id, 'pending')");
                $insertApplication->execute(['user_id' => (int) $pdo->lastInsertId(), 'artist_id' => $artistId]);
                $pdo->commit();
                $success = 'Application submitted. An administrator must approve it before the artist dashboard is available.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'The artist application could not be submitted. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artist account application - PulseFlow</title>
    <link rel="stylesheet" href="../frontend/auth-style.css">
    <style>
        .artist-application { max-width: 560px; margin: 7vh auto; padding: 32px; background: #171b22; border: 1px solid rgba(255,255,255,.1); border-radius: 12px; color: #edf2f7; }
        .artist-application form { display: grid; gap: 14px; }
        .artist-application label { display: grid; gap: 6px; color: #a3b0c2; }
        .artist-application input, .artist-application select { width: 100%; padding: 12px; border: 1px solid rgba(255,255,255,.12); border-radius: 8px; background: #1d232d; color: #edf2f7; }
        .artist-application button { padding: 12px 16px; border: 0; border-radius: 999px; background: #1db954; color: #07130b; font-weight: 700; cursor: pointer; }
        .notice { padding: 12px; margin: 14px 0; border-radius: 8px; background: rgba(29,185,84,.14); }
        .notice.error { background: rgba(239,68,68,.16); }
        .artist-application a { color: #b9f7d1; }
    </style>
</head>
<body>
    <main class="artist-application">
        <p><a href="../frontend/user-register.html">Listener registration</a></p>
        <h1>Artist account application</h1>
        <p>Apply to manage the dashboard for an existing artist profile. Access is enabled after admin approval.</p>
        <?php if ($error !== ''): ?><div class="notice error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($success !== ''): ?><div class="notice"><?php echo e($success); ?> <a href="../frontend/user-login.html">Log in</a></div><?php endif; ?>
        <form method="post" action="artist-register.php">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
            <label>First name<input name="first_name" autocomplete="given-name" required></label>
            <label>Last name<input name="last_name" autocomplete="family-name" required></label>
            <label>Email<input type="email" name="email" autocomplete="email" required></label>
            <label>Artist profile<select name="artist_id" required><option value="">Choose profile</option><?php foreach ($artists as $artist): ?><option value="<?php echo (int) $artist['artist_id']; ?>"><?php echo e($artist['artist_name']); ?><?php echo $artist['genre'] ? ' · ' . e($artist['genre']) : ''; ?></option><?php endforeach; ?></select></label>
            <label>Password<input type="password" name="password" minlength="6" autocomplete="new-password" required></label>
            <label>Confirm password<input type="password" name="confirm_password" minlength="6" autocomplete="new-password" required></label>
            <button type="submit">Submit artist application</button>
        </form>
    </main>
</body>
</html>
