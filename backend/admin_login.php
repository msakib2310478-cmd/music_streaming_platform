<?php
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../frontend/admin-login.php');
    exit;
}

$email = trim(strtolower($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Admin email and password are required.';
    header('Location: ../frontend/admin-login.php');
    exit;
}

if (loginRateLimitExceeded($pdo, $email, $ipAddress)) {
    $_SESSION['error'] = 'Too many login attempts. Please wait 15 minutes and try again.';
    header('Location: ../frontend/admin-login.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT user_id, username, email, password_hash, role FROM users WHERE email = :email LIMIT 1'
);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin' || !password_verify($password, $user['password_hash'])) {
    recordLoginAttempt($pdo, $email, $ipAddress, false);
    $_SESSION['error'] = 'Invalid admin credentials.';
    header('Location: ../frontend/admin-login.php');
    exit;
}

recordLoginAttempt($pdo, $email, $ipAddress, true);

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['email'] = $user['email'];

$_SESSION['success'] = 'Admin login successful.';
header('Location: admin-dashboard.php');
exit;
