<?php
require_once __DIR__ . '/functions.php';

$userId = requireUser();

$stmt = $pdo->prepare('SELECT user_id, username, email, first_name, last_name, display_name, country, bio, subscription_type, role, created_at FROM users WHERE user_id = :user_id LIMIT 1');
$stmt->execute(['user_id' => $userId]);
$user = $stmt->fetch();
if (!$user) {
    http_response_code(404);
    exit('Profile not found.');
}

$displayName = $user['display_name'] ?: $user['username'];
$initials = strtoupper(substr(trim(($user['first_name'] ?: '') . ' ' . ($user['last_name'] ?: $displayName)), 0, 1));
$initials .= strtoupper(substr(trim(($user['last_name'] ?: $displayName)), 0, 1));
$message = $_SESSION['success'] ?? $_SESSION['error'] ?? '';
$messageType = isset($_SESSION['error']) ? 'error' : 'success';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile - FulseFLow</title>
    <link rel="stylesheet" href="../frontend/user-dashbord.css">
    <link rel="stylesheet" href="../frontend/profile.css">
</head>
<body>
    <main class="main-content page profile-page">
        <p class="profile-back"><a href="user-dashbord.php" data-dashboard-link>&larr; Back to dashboard</a></p>
        <header class="profile-heading">
            <div>
                <p class="profile-eyebrow">Account settings</p>
                <h1>Your profile</h1>
                <p class="profile-subtitle">Keep your personal details and listening account up to date.</p>
            </div>
            <div class="profile-avatar" aria-hidden="true"><?php echo e($initials); ?></div>
        </header>

        <?php if ($message): ?>
            <div class="profile-alert <?php echo $messageType === 'error' ? 'error' : ''; ?>" role="status"><?php echo e($message); ?></div>
        <?php endif; ?>

        <section class="profile-grid">
            <form class="profile-card" method="post" action="profile_actions.php" data-dashboard-form>
                <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                <input type="hidden" name="action" value="update_profile">
                <div class="profile-card-heading">
                    <div><p class="profile-eyebrow">Personal information</p><h2>About you</h2></div>
                    <span class="profile-status"><?php echo e(ucfirst($user['subscription_type'])); ?> plan</span>
                </div>
                <div class="profile-fields two-column">
                    <label>First name<input name="first_name" value="<?php echo e($user['first_name']); ?>" autocomplete="given-name" maxlength="80"></label>
                    <label>Last name<input name="last_name" value="<?php echo e($user['last_name']); ?>" autocomplete="family-name" maxlength="80"></label>
                    <label>Display name<input name="display_name" value="<?php echo e($user['display_name']); ?>" autocomplete="nickname" maxlength="80"></label>
                    <label>Username<input name="username" value="<?php echo e($user['username']); ?>" autocomplete="username" maxlength="50" required></label>
                    <label>Country<input name="country" value="<?php echo e($user['country']); ?>" autocomplete="country-name" maxlength="100"></label>
                    <label>Email address<input value="<?php echo e($user['email']); ?>" autocomplete="email" readonly></label>
                    <label class="full-width">Bio<textarea name="bio" rows="4" maxlength="500" placeholder="Tell us a little about yourself"><?php echo e($user['bio']); ?></textarea></label>
                </div>
                <button class="profile-primary" type="submit">Save profile</button>
            </form>

            <form class="profile-card" method="post" action="profile_actions.php" data-dashboard-form>
                <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="profile-card-heading">
                    <div><p class="profile-eyebrow">Security</p><h2>Change password</h2></div>
                </div>
                <div class="profile-fields">
                    <label>Current password<input name="current_password" type="password" autocomplete="current-password" required></label>
                    <label>New password<input name="new_password" type="password" autocomplete="new-password" minlength="6" required></label>
                    <label>Confirm new password<input name="confirm_password" type="password" autocomplete="new-password" minlength="6" required></label>
                </div>
                <p class="profile-help">Use at least 6 characters. Your password is never displayed in your profile.</p>
                <button class="profile-secondary" type="submit">Update password</button>
            </form>
        </section>

        <section class="profile-card account-summary">
            <div><p class="profile-eyebrow">Account</p><h2>Account summary</h2></div>
            <dl>
                <div><dt>Member since</dt><dd><?php echo e(date('F j, Y', strtotime($user['created_at']))); ?></dd></div>
                <div><dt>Role</dt><dd><?php echo e(ucfirst($user['role'])); ?></dd></div>
                <div><dt>Plan</dt><dd><?php echo e(ucfirst($user['subscription_type'])); ?></dd></div>
            </dl>
        </section>
    </main>
</body>
</html>
