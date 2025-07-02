<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

requireLogin();

$party_id = $_GET['party_id'] ?? null;

if (!$party_id || !is_numeric($party_id)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Party ID']);
    exit;
}

$db = new Database();

// Get party type to determine balance calculation
$db->query("SELECT type FROM parties WHERE id = :id");
$db->bind(':id', $party_id);
$party = $db->single();

if (!$party) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Party not found']);
    exit;
}

// Calculate balance based on party type
if ($party['type'] === 'customer') {
    // Receivable = (Credit Sales) - (Payments Received)
    $db->query("SELECT (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE party_id = :pid AND type = 'sale' AND payment_mode = 'credit') - (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE party_id = :pid AND type = 'payment') AS balance");
} else { // vendor
    // Payable = (Credit Purchases) - (Payments Made)
    $db->query("SELECT (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE party_id = :pid AND type = 'purchase' AND payment_mode = 'credit') - (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE party_id = :pid AND type = 'payment') AS balance");
}

$db->bind(':pid', $party_id);
$result = $db->single();
$balance = $result['balance'] ?? 0;

header('Content-Type: application/json');
echo json_encode(['balance' => floatval($balance)]);
exit;