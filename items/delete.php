<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid item ID.");
}

$db = new Database();

// Optional: check if item exists before deletion
$db->query("SELECT id FROM items WHERE id = :id");
$db->bind(':id', $id);
$item = $db->single();

if (!$item) {
    die("Item not found.");
}

// Delete the item (will also cascade delete any related transaction_items if foreign keys are set)
$db->query("DELETE FROM items WHERE id = :id");
$db->bind(':id', $id);

if ($db->execute()) {
    // Redirect back to items index with success message
    header("Location: index.php?msg=deleted");
    exit;
} else {
    die("Failed to delete item.");
}
