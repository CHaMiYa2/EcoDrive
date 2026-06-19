<?php
// includes/session.php

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    startSession();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: /login.php');
        exit;
    }
}

function requireDriver() {
    startSession();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
        header('Location: /login.php');
        exit;
    }
}
