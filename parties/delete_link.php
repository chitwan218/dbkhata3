<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo 'Invalid link ID.';
    exit;
}

$db = new Database();

// Ensure the link exists before deleting
$db->query("SELECT id FROM party_profile_links WHERE id = :id");
$db->bind(':id', $id);
$link = $db->single();

if (!$link) {
    http_response_code(404);
    echo 'Link not found.';
    exit;
}

// Delete the link
$db->query("DELETE FROM party_profile_links WHERE id = :id");
$db->bind(':id', $id);

if ($db->execute()) {
    echo 'success';
} else {
    http_response_code(500);
    echo 'Failed to delete link.';
}
