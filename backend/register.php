<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../frontend/user-register.html');
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim(strtolower($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $confirmPassword === '') {
    $_SESSION['error'] = 'Please fill in all fields.';
    header('Location: ../frontend/user-register.html');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    header('Location: ../frontend/user-register.html');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters long.';
    header('Location: ../frontend/user-register.html');
    exit;
}

if ($password !== $confirmPassword) {
    $_SESSION['error'] = 'Passwords do not match.';
    header('Location: ../frontend/user-register.html');
    exit;
}

$username = strtolower(trim($firstName . '_' . $lastName));
$username = preg_replace('/[^a-z0-9_]/', '', $username);
if ($username === '') {
    $username = 'user_' . time();
}

$checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
$checkStmt->execute([':email' => $email]);

if ($checkStmt->fetch()) {
    $_SESSION['error'] = 'This email is already registered.';
    header('Location: ../frontend/user-register.html');
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertStmt = $pdo->prepare(
    'INSERT INTO users (username, email, password_hash, subscription_type, role) VALUES (:username, :email, :password_hash, :subscription_type, :role)'
);

$insertStmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':password_hash' => $hashedPassword,
    ':subscription_type' => 'free',
    ':role' => 'user',
]);

$_SESSION['success'] = 'Registration successful! Please log in.';
header('Location: ../frontend/user-login.html');
exit;
