<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../frontend/user-login.html');
    exit;
}

$email = trim(strtolower($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

ensureArtistAccountsTable($pdo);

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Email and password are required.';
    header('Location: ../frontend/user-login.html');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT user_id, username, email, password_hash, role, subscription_type FROM users WHERE email = :email LIMIT 1'
);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['error'] = 'Invalid email or password.';
    header('Location: ../frontend/user-login.html');
    exit;
}

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['email'] = $user['email'];
$_SESSION['subscription_type'] = $user['subscription_type'];
unset($_SESSION['artist_id'], $_SESSION['artist_application_status']);

if ($user['role'] === 'admin') {
    header('Location: admin-dashboard.php');
    exit;
}

$artistAccountStmt = $pdo->prepare('SELECT artist_id, status FROM artist_accounts WHERE user_id = :user_id LIMIT 1');
$artistAccountStmt->execute(['user_id' => (int) $user['user_id']]);
$artistAccount = $artistAccountStmt->fetch();
if ($artistAccount) {
    if ($artistAccount['status'] === 'approved') {
        $_SESSION['artist_id'] = (int) $artistAccount['artist_id'];
        header('Location: artist-dashboard.php');
    } else {
        $_SESSION['artist_application_status'] = $artistAccount['status'];
        header('Location: artist-application-status.php');
    }
    exit;
}

header('Location: user-dashbord.php');
exit;
