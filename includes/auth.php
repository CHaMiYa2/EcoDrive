<?php
// includes/auth.php
// Authentication and session helpers

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false, // Set to true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

function checkSessionTimeout(): void {
    if (isset($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header('Location: /login.php?timeout=1');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

function requireLogin(string $role = ''): void {
    startSecureSession();
    checkSessionTimeout();

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    if ($role && $_SESSION['role'] !== $role) {
        header('Location: /dashboard.php');
        exit;
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'   => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['role'],
    ];
}

function loginAdmin(string $username, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        startSecureSession();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $admin['id'];
        $_SESSION['user_name'] = $admin['full_name'];
        $_SESSION['username']  = $admin['username'];
        $_SESSION['role']      = 'admin';
        $_SESSION['last_activity'] = time();
        return ['success' => true];
    }

    return ['success' => false, 'message' => 'Invalid username or password.'];
}

function loginDriver(string $username, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM drivers WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $driver = $stmt->fetch();

    if ($driver && password_verify($password, $driver['password'])) {
        startSecureSession();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $driver['id'];
        $_SESSION['user_name'] = $driver['full_name'];
        $_SESSION['username']  = $driver['username'];
        $_SESSION['role']      = 'driver';
        $_SESSION['last_activity'] = time();
        return ['success' => true];
    }

    return ['success' => false, 'message' => 'Invalid username or password.'];
}

function logout(): void {
    startSecureSession();
    session_unset();
    session_destroy();
    header('Location: /login.php?logged_out=1');
    exit;
}

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function validateAge(string $birthday): bool {
    $birthDate = new DateTime($birthday);
    $today     = new DateTime();
    $age       = $today->diff($birthDate)->y;
    return $age >= MIN_DRIVER_AGE;
}

function getClientIP(): string {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    // Fallback for local development
    return '8.8.8.8';
}
