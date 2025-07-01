<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirects user to login page if not logged in
 */
function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Returns true if a user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user']);
}

/**
 * Returns true if the logged-in user is an admin
 */
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Redirects non-admins to dashboard
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}
