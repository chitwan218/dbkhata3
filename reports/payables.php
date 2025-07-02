<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = new Database();

$db->query("SELECT id, name FROM parties ORDER BY name");
$partiesList = $db->resultSet();

$payables = [];

foreach ($partiesList as $p) {
    $db->query("
        SELECT type, payment_mode, amount
        FROM transactions
        WHERE party_id = :id AND type NOT IN ('income', 'expense')
        ORDER BY date ASC, id ASC
    ");
    $db->bind(':id', $p['id']);
    $transactions = $db->resultSet();

    $balance = 0;
    foreach ($transactions as $t) {
        if ($t['type'] === 'sale' && $t['payment_mode'] === 'credit') {
            $balance += $t['amount'];
        } elseif ($t['type'] === 'purchase' && $t['payment_mode'] === 'credit') {
            $balance -= $t['amount'];
        } elseif ($t['type'] === 'receipt' && in_array($t['payment_mode'], ['cash', 'bank'])) {
            $balance -= $t['amount'];
        } elseif ($t['type'] === 'payment' && in_array($t['payment_mode'], ['cash', 'bank'])) {
            $balance += $t['amount'];
        }
    }

    if ($balance < 0) {
        $payables[] = [
            'id' => $p['id'],
            'name' => $p['name'],
            'amount' => abs($balance)
        ];
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Payables Report</h2>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead><tr><th>Party</th><th>Payable Amount</th></tr></thead>
        <tbody>
        <?php foreach ($payables as $r): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/parties/profile.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></a></td>
                <td>Rs. <?= number_format($r['amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
