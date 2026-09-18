<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../frontend/user-login.html');
    exit;
}

$email = trim(strtolower($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

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

if ($user['role'] === 'admin') {
    header('Location: admin-dashboard.php');
    exit;
}

header('Location: user-dashbord.php');
exit;
