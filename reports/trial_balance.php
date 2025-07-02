<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = new Database();

$db->query("
    SELECT p.id, p.name,
        IFNULL(SUM(CASE 
            WHEN t.type = 'sale' AND t.payment_mode = 'credit' THEN t.amount
            WHEN t.type = 'receipt' AND t.payment_mode IN ('cash', 'bank') THEN -t.amount
            ELSE 0 END), 0) AS receivable,
        IFNULL(SUM(CASE 
            WHEN t.type = 'purchase' AND t.payment_mode = 'credit' THEN t.amount
            WHEN t.type = 'payment' AND t.payment_mode IN ('cash', 'bank') THEN -t.amount
            ELSE 0 END), 0) AS payable
    FROM parties p
    LEFT JOIN transactions t ON t.party_id = p.id AND t.type NOT IN ('income', 'expense')
    GROUP BY p.id
    HAVING receivable != 0 OR payable != 0
    ORDER BY p.name
");
$parties = $db->resultSet();

include __DIR__ . '/../includes/header.php';
?>
<h2>Trial Balance</h2>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead><tr><th>Party</th><th>Receivable</th><th>Payable</th><th>Net</th></tr></thead>
        <tbody>
        <?php foreach ($parties as $p): 
            $net = $p['receivable'] - $p['payable'];
        ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/parties/profile.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
                <td><?= $p['receivable'] > 0 ? 'Rs. ' . number_format($p['receivable'], 2) : '-' ?></td>
                <td><?= $p['payable'] > 0 ? 'Rs. ' . number_format($p['payable'], 2) : '-' ?></td>
                <td>
                    <?= $net == 0 ? 'Rs. 0.00' : ($net > 0 ? 'Receivable: Rs. ' . number_format($net, 2) : 'Payable: Rs. ' . number_format(abs($net), 2)) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
