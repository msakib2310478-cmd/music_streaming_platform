<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function redirectIfNotLoggedIn(string $redirectPage): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $redirectPage);
        exit;
    }
}

function requireRole(string $role, string $redirectPage): void
{
    redirectIfNotLoggedIn($redirectPage);

    if (($role === 'admin' && ($_SESSION['role'] ?? '') !== 'admin') || ($role === 'user' && ($_SESSION['role'] ?? '') !== 'user')) {
        header('Location: ' . $redirectPage);
        exit;
    }
}

function logoutAndRedirect(string $redirectPage): void
{
    session_unset();
    session_destroy();
    header('Location: ' . $redirectPage);
    exit;
}
