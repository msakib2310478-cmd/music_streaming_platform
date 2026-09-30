<?php
require_once __DIR__ . '/functions.php';

$userId = requireUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

verifyCsrf($_POST['csrf_token'] ?? null);
$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if ($username === '' || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_ .-]+$/', $username)) {
        $_SESSION['error'] = 'Choose a username using letters, numbers, spaces, dots, hyphens, or underscores.';
        header('Location: profile.php');
        exit;
    }

    $duplicate = $pdo->prepare('SELECT user_id FROM users WHERE username = :username AND user_id <> :user_id LIMIT 1');
    $duplicate->execute(['username' => $username, 'user_id' => $userId]);
    if ($duplicate->fetch()) {
        $_SESSION['error'] = 'That username is already in use.';
        header('Location: profile.php');
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET first_name = :first_name, last_name = :last_name, display_name = :display_name, username = :username, country = :country, bio = :bio WHERE user_id = :user_id');
    $stmt->execute([
        'first_name' => $firstName ?: null,
        'last_name' => $lastName ?: null,
        'display_name' => $displayName ?: null,
        'username' => $username,
        'country' => $country ?: null,
        'bio' => $bio ?: null,
        'user_id' => $userId,
    ]);

    $_SESSION['username'] = $username;
    $_SESSION['display_name'] = $displayName ?: $username;
    $_SESSION['country'] = $country;
    $_SESSION['success'] = 'Your profile was updated.';
    header('Location: profile.php');
    exit;
}

if ($action === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $passwordHash = $stmt->fetchColumn();

    if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
        $_SESSION['error'] = 'Your current password is incorrect.';
    } elseif (strlen($newPassword) < 6) {
        $_SESSION['error'] = 'Your new password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = 'The new passwords do not match.';
    } elseif ($newPassword === $currentPassword) {
        $_SESSION['error'] = 'Your new password must be different from the current password.';
    } else {
        $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
        $update->execute(['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'user_id' => $userId]);
        $_SESSION['success'] = 'Your password was updated.';
    }

    header('Location: profile.php');
    exit;
}

$_SESSION['error'] = 'Unsupported profile action.';
header('Location: profile.php');
exit;
