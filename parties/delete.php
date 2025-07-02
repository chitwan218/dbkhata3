<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Allow only admin to delete
if (!isAdmin()) {
    // Redirect non-admin users away
    header("Location: " . BASE_URL . "/parties/index.php");
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: " . BASE_URL . "/parties/index.php");
    exit;
}

$db = new Database();

// Optionally check if party exists before deletion

$db->query("DELETE FROM parties WHERE id = :id");
$db->bind(':id', $id);
$db->execute();

header("Location: " . BASE_URL . "/parties/index.php?message=" . urlencode("Party deleted successfully.") . "&type=success");
exit;
