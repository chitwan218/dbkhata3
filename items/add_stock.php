<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id = $_POST['id'] ?? null;
$adjustment = $_POST['adjustment'] ?? null;

if (!$id || !is_numeric($id) || !is_numeric($adjustment)) {
    die("Invalid input.");
}

$db = new Database();

// Fetch current stock
$db->query("SELECT stock_quantity FROM items WHERE id = :id");
$db->bind(':id', $id);
$item = $db->single();

if (!$item) {
    die("Item not found.");
}

$newStock = $item['stock_quantity'] + floatval($adjustment);

if ($newStock < 0) {
    die("Stock cannot be negative.");
}

$db->query("UPDATE items SET stock_quantity = :stock_quantity WHERE id = :id");
$db->bind(':stock_quantity', $newStock);
$db->bind(':id', $id);

if ($db->execute()) {
    header("Location: stock_adjust.php?id=$id&success=1");
} else {
    die("Failed to update stock.");
}
