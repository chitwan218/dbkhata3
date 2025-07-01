<?php
session_start();

// If user logged in, redirect to dashboard; else to login page
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
} else {
    header("Location: auth/login.php");
}
exit;
