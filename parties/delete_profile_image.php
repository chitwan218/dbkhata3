<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$party_id = $_GET['party_id'] ?? null;
if (!$party_id || !is_numeric($party_id)) {
    http_response_code(400);
    echo 'Invalid party ID.';
    exit;
}

$db = new Database();

// Get the party record to find the image filename
$db->query("SELECT profile_image FROM parties WHERE id = :id");
$db->bind(':id', $party_id);
$party = $db->single();

if (!$party || empty($party['profile_image'])) {
    http_response_code(404);
    echo 'Profile image not found or already deleted.';
    exit;
}

// Construct file path
$filePath = __DIR__ . '/../uploads/profile/' . $party['profile_image'];

// Delete the physical file if it exists
if (file_exists($filePath) && is_file($filePath)) {
    unlink($filePath);
}

// Update the database record to remove the image reference
$db->query("UPDATE parties SET profile_image = '' WHERE id = :id");
$db->bind(':id', $party_id);

if ($db->execute()) {
    echo 'success';
} else {
    http_response_code(500);
    echo 'Failed to update party record.';
}