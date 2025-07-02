<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = new Database();


$transaction_id = $_GET['id'] ?? null;
if (!$transaction_id || !is_numeric($transaction_id)) {
    die("Invalid transaction ID.");
}

$db->query("SELECT t.*, p.name AS party_name, p.phone, p.email, p.address 
            FROM transactions t 
            JOIN parties p ON t.party_id = p.id 
            WHERE t.id = :id");
$db->bind(':id', $transaction_id);
$transaction = $db->single();

if (!$transaction) {
    die("Transaction not found.");
}

// If sale or purchase, fetch items
$items = [];
if (in_array($transaction['type'], ['sale', 'purchase'])) {
    $db->query("SELECT * FROM transaction_items WHERE transaction_id = :tid");
    $db->bind(':tid', $transaction_id);
    $items = $db->resultSet();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="d-print-none my-3">
        <a href="javascript:window.print()" class="btn btn-primary">🖨️ Print</a>
        <a href="javascript:history.back()" class="btn btn-secondary">⬅️ Back</a>
    </div>

    <div class="border p-4" style="max-width: 800px; margin: auto;">
        <h3 class="text-center mb-4"><?= ucfirst($transaction['type']) ?> Invoice</h3>
        <p><strong>Date:</strong> <?= htmlspecialchars($transaction['date']) ?></p>
        <p><strong>Invoice No:</strong> <?= $transaction['id'] ?></p>
        <p><strong>Party:</strong> <?= htmlspecialchars($transaction['party_name']) ?></p>
        <p><strong>Contact:</strong> <?= $transaction['phone'] ?> | <?= $transaction['email'] ?></p>
        <p><strong>Address:</strong> <?= nl2br($transaction['address']) ?></p>

        <hr>

        <?php if (in_array($transaction['type'], ['sale', 'purchase'])): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><?= $item['qty'] ?></td>
                            <td><?= number_format($item['rate'], 2) ?></td>
                            <td><?= number_format($item['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th colspan="4" class="text-end">Grand Total</th>
                        <th><?= number_format($transaction['amount'], 2) ?></th>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <p><strong>Amount:</strong> Rs. <?= number_format($transaction['amount'], 2) ?></p>
            <p><strong>Mode:</strong> <?= ucfirst($transaction['payment_mode']) ?></p>
            <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($transaction['description'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
