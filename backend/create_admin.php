<?php
require __DIR__ . '/db.php';

$email = 'admin@example.com';
$username = 'adminuser';
$password = 'admin123';

$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

if ($user) {
    $update = $pdo->prepare(
        'UPDATE users SET username = :username, password_hash = :password_hash, role = :role, subscription_type = :subscription_type WHERE email = :email'
    );
    $update->execute([
        ':username' => $username,
        ':password_hash' => $hashedPassword,
        ':role' => 'admin',
        ':subscription_type' => 'premium',
        ':email' => $email,
    ]);
    echo 'Admin account updated successfully. Email: ' . $email . ' | Password: ' . $password;
} else {
    $insert = $pdo->prepare(
        'INSERT INTO users (username, email, password_hash, subscription_type, role) VALUES (:username, :email, :password_hash, :subscription_type, :role)'
    );
    $insert->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $hashedPassword,
        ':subscription_type' => 'premium',
        ':role' => 'admin',
    ]);
    echo 'Admin account created successfully. Email: ' . $email . ' | Password: ' . $password;
}
