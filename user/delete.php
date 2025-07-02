<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';

requireLogin();
if (!isAdmin()) {
    die('<div class="alert alert-danger m-4">Access denied. Admins only.</div>');
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die('<div class="alert alert-danger m-4">Invalid user ID.</div>');
}

// Prevent admin from deleting themselves
if ($_SESSION['user']['id'] == $id) {
    die('<div class="alert alert-warning m-4">You cannot delete your own account.</div>');
}

$db = new Database();
$db->query("DELETE FROM users WHERE id = :id");
$db->bind(':id', $id);
$db->execute();

header("Location: index.php?deleted=1");
exit;
