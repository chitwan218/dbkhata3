<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function getUserRole() {
    return $_SESSION['user']['role'] ?? null;
}

function hasRole($role) {
    return getUserRole() === $role;
}

function requireRole($role) {
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

function isAdmin() {
    return hasRole('admin');
}
