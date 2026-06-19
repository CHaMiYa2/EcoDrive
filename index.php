<?php
require_once 'includes/session.php';
startSession();

if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /admin_dashboard.php');
    } else {
        header('Location: /driver_dashboard.php');
    }
} else {
    header('Location: /login.php');
}
exit;
