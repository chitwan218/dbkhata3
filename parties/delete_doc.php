<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo 'Invalid document ID.';
    exit;
}

$db = new Database();

// Get the document record
$db->query("SELECT file_path FROM party_documents WHERE id = :id");
$db->bind(':id', $id);
$doc = $db->single();

if (!$doc) {
    http_response_code(404);
    echo 'Document not found.';
    exit;
}

$filePath = __DIR__ . '/../' . $doc['file_path'];

// Delete the physical file if it exists
if (file_exists($filePath) && is_file($filePath)) {
    unlink($filePath);
}

// Delete the record from the database
$db->query("DELETE FROM party_documents WHERE id = :id");
$db->bind(':id', $id);

if ($db->execute()) {
    echo 'success';
} else {
    http_response_code(500);
    echo 'Failed to delete document.';
}
