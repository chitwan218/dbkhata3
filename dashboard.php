<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$db = new Database();

// Calculate net balance per party
$db->query("
    SELECT
        p.id,
        p.name,
        COALESCE(SUM(CASE WHEN t.type = 'sale' AND t.payment_mode = 'credit' THEN t.amount ELSE 0 END), 0) AS total_sale_credit,
        COALESCE(SUM(CASE WHEN t.type = 'receipt' AND t.payment_mode IN ('cash','bank') THEN t.amount ELSE 0 END), 0) AS total_receipt_cash,
        COALESCE(SUM(CASE WHEN t.type = 'purchase' AND t.payment_mode = 'credit' THEN t.amount ELSE 0 END), 0) AS total_purchase_credit,
        COALESCE(SUM(CASE WHEN t.type = 'payment' AND t.payment_mode IN ('cash','bank') THEN t.amount ELSE 0 END), 0) AS total_payment_cash
    FROM parties p
    LEFT JOIN transactions t ON t.party_id = p.id AND t.type NOT IN ('income', 'expense')
    GROUP BY p.id
");
$partyBalances = $db->resultSet();

$totalReceivable = 0;
$totalPayable = 0;

// Calculate dashboard totals
foreach ($partyBalances as $party) {
    $receivable = $party['total_sale_credit'] - $party['total_receipt_cash'];
    $payable = $party['total_purchase_credit'] - $party['total_payment_cash'];
    $netBalance = $receivable - $payable;

    if ($netBalance > 0) {
        $totalReceivable += $netBalance;
    } elseif ($netBalance < 0) {
        $totalPayable += abs($netBalance);
    }
}

// Fetch recent 10 transactions excluding income/expense
$db->query("
    SELECT t.*, p.name AS party_name 
    FROM transactions t
    JOIN parties p ON p.id = t.party_id
    WHERE t.type NOT IN ('income', 'expense')
    ORDER BY t.date DESC, t.id DESC
    LIMIT 10
");
$recent = $db->resultSet();

// Calculate running balance for recent transactions (in chronological order)
$runningBalance = 0;
$runningTxns = [];
foreach (array_reverse($recent) as $tx) {
    if ($tx['type'] === 'sale' && $tx['payment_mode'] === 'credit') {
        $runningBalance += $tx['amount'];
    } elseif ($tx['type'] === 'purchase' && $tx['payment_mode'] === 'credit') {
        $runningBalance -= $tx['amount'];
    } elseif ($tx['type'] === 'receipt' && in_array($tx['payment_mode'], ['cash', 'bank'])) {
        $runningBalance -= $tx['amount'];
    } elseif ($tx['type'] === 'payment' && in_array($tx['payment_mode'], ['cash', 'bank'])) {
        $runningBalance += $tx['amount'];
    }
    $tx['balance'] = $runningBalance;
    $runningTxns[] = $tx;
}
// Reverse back to show newest first
$runningTxns = array_reverse($runningTxns);

include __DIR__ . '/includes/header.php';
?>

<h2 class="mb-4">Dashboard</h2>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Total Receivable</h5>
                <p class="display-6 text-success">Rs. <?= number_format($totalReceivable, 2) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Total Payable</h5>
                <p class="display-6 text-danger">Rs. <?= number_format($totalPayable, 2) ?></p>
            </div>
        </div>
    </div>
</div>

<h4>Recent Transactions</h4>

<?php if (count($runningTxns) > 0): ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Party</th>
                <th>Type</th>
                <th>Mode</th>
                <th>Amount</th>
                <th>Balance</th>
                <th>Description</th>
                <th>Invoice</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($runningTxns as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['date']) ?></td>
                    <td><?= htmlspecialchars($t['party_name']) ?></td>
                    <td><?= ucfirst($t['type']) ?></td>
                    <td><?= ucfirst($t['payment_mode']) ?></td>
                    <td><?= number_format($t['amount'], 2) ?></td>
                    <td><?= number_format($t['balance'], 2) ?></td>
                    <td><?= htmlspecialchars($t['description']) ?></td>
                    <td class="text-center">
                        <?php if (in_array($t['type'], ['sale', 'purchase', 'receipt', 'payment'])): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/transactions/invoice.php?id=<?= $t['id'] ?>">🧾</a>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p>No recent transactions.</p>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
