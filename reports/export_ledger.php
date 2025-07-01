<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
$db = new Database();

// Get filters from query
$party_id = $_GET['party_id'] ?? '';
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$conditions = [];
$params = [];

if ($party_id) {
    $conditions[] = 't.party_id = :party_id';
    $params[':party_id'] = $party_id;
}

if ($type) {
    $conditions[] = 't.type = :type';
    $params[':type'] = $type;
}

if ($start_date) {
    $conditions[] = 't.date >= :start';
    $params[':start'] = $start_date;
}

if ($end_date) {
    $conditions[] = 't.date <= :end';
    $params[':end'] = $end_date;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "SELECT t.*, p.name AS party_name 
        FROM transactions t 
        JOIN parties p ON p.id = t.party_id 
        $where 
        ORDER BY t.date DESC, t.id DESC";

$db->query($sql);
foreach ($params as $key => $val) {
    $db->bind($key, $val);
}
$transactions = $db->resultSet();

// Calculate running balance
$balance = 0;
$running = [];

foreach (array_reverse($transactions) as $trn) {
    if ($trn['type'] === 'sale' && $trn['payment_mode'] === 'credit') {
        $balance += $trn['amount'];
    } elseif ($trn['type'] === 'purchase' && $trn['payment_mode'] === 'credit') {
        $balance -= $trn['amount'];
    } elseif ($trn['type'] === 'receipt' && in_array($trn['payment_mode'], ['cash', 'bank'])) {
        $balance -= $trn['amount'];
    } elseif ($trn['type'] === 'payment' && in_array($trn['payment_mode'], ['cash', 'bank'])) {
        $balance += $trn['amount'];
    }
    $running[] = ['trn' => $trn, 'balance' => $balance];
}
$running = array_reverse($running);

// Set headers for CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="ledger_export.csv"');

$output = fopen('php://output', 'w');

// CSV headers
fputcsv($output, ['Date', 'Party', 'Type', 'Mode', 'Amount', 'Balance', 'Description']);

// Rows
foreach ($running as $row) {
    $trn = $row['trn'];
    fputcsv($output, [
        $trn['date'],
        $trn['party_name'],
        ucfirst($trn['type']),
        ucfirst($trn['payment_mode']),
        number_format($trn['amount'], 2),
        number_format($row['balance'], 2),
        $trn['description']
    ]);
}

fclose($output);
exit;
