<?php
/**
 * Handles user logout.
 *
 * This script starts the session, clears all session data,
 * destroys the session, and then redirects the user to the login page.
 */

require_once '../config/config.php';

session_start();

// Unset all of the session variables.
$_SESSION = [];

// Destroy the session.
session_destroy();

// Redirect to the login page.
header("Location: " . BASE_URL . "/auth/login.php");
exit;
?>