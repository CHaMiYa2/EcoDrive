<?php
// api/login.php
// Handles login POST for both drivers and admins

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$username = sanitize($_POST['username'] ?? '');
$password =           $_POST['password'] ?? '';
$role     = sanitize($_POST['role']     ?? 'driver'); // 'driver' or 'admin'

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

if ($role === 'admin') {
    $result = loginAdmin($username, $password);
} else {
    $result = loginDriver($username, $password);
}

if ($result['success']) {
    $redirect = ($_SESSION['role'] === 'admin') ? '/admin/dashboard.php' : '/dashboard.php';
    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    echo json_encode($result);
}

exit;
