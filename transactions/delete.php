<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';

requireLogin();
$db = new Database();

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid transaction ID.");
}

$db->query("DELETE FROM transactions WHERE id = :id");
$db->bind(':id', $id);
$db->execute();

header("Location: index.php");
exit;
