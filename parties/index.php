<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = new Database();

// Filters
$search = trim($_GET['search'] ?? '');
$balance_type = $_GET['balance_type'] ?? ''; // 'receivable', 'payable', or ''
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Base query with net balance calculation
$baseQuery = "
    FROM parties p
    LEFT JOIN transactions t ON p.id = t.party_id AND t.type NOT IN ('income', 'expense')
";
$whereClause = '';
$havingClause = '';
$params = [];

// Apply search filter
if ($search !== '') {
    $whereClause = " WHERE p.name LIKE :search";
    $params[':search'] = "%$search%";
}

// Apply balance type filter using HAVING clause
if ($balance_type === 'receivable') {
    $havingClause = " HAVING net_balance > 0";
} elseif ($balance_type === 'payable') {
    $havingClause = " HAVING net_balance < 0";
}

// Count total rows with all filters applied
$countQuery = "
    SELECT COUNT(*) as total FROM (
        SELECT p.id, SUM(CASE 
                WHEN t.type = 'sale' AND t.payment_mode = 'credit' THEN t.amount
                WHEN t.type = 'purchase' AND t.payment_mode = 'credit' THEN -t.amount
                WHEN t.type = 'receipt' AND t.payment_mode IN ('cash', 'bank') THEN -t.amount
                WHEN t.type = 'payment' AND t.payment_mode IN ('cash', 'bank') THEN t.amount
                ELSE 0
            END) as net_balance
        $baseQuery $whereClause GROUP BY p.id $havingClause
    ) as subquery
";
$db->query($countQuery);
foreach ($params as $key => $value) $db->bind($key, $value);
$total_rows = $db->single()['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);

// Fetch paginated data with all filters
$dataQuery = "
    SELECT p.id, p.name, IFNULL(SUM(CASE 
            WHEN t.type = 'sale' AND t.payment_mode = 'credit' THEN t.amount
            WHEN t.type = 'purchase' AND t.payment_mode = 'credit' THEN -t.amount
            WHEN t.type = 'receipt' AND t.payment_mode IN ('cash', 'bank') THEN -t.amount
            WHEN t.type = 'payment' AND t.payment_mode IN ('cash', 'bank') THEN t.amount
            ELSE 0
        END), 0) as net_balance
    $baseQuery $whereClause GROUP BY p.id $havingClause ORDER BY p.name ASC LIMIT $limit OFFSET $offset
";
$db->query($dataQuery);
foreach ($params as $key => $value) $db->bind($key, $value);
$parties = $db->resultSet();

include __DIR__ . '/../includes/header.php';
?>

<h2>Parties</h2>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Search by party name..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3">
        <select name="balance_type" class="form-select">
            <option value="">-- All Balances --</option>
            <option value="receivable" <?= $balance_type === 'receivable' ? 'selected' : '' ?>>Only Receivable</option>
            <option value="payable" <?= $balance_type === 'payable' ? 'selected' : '' ?>>Only Payable</option>
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>

<?php if (count($parties) > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Party Name</th>
                    <th>Net Balance</th>
                    <th>Profile</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parties as $index => $p): ?>
                    <tr>
                        <td><?= $offset + $index + 1 ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td>
                            <?php if ($p['net_balance'] > 0): ?>
                                Receivable: Rs. <?= number_format($p['net_balance'], 2) ?>
                            <?php elseif ($p['net_balance'] < 0): ?>
                                Payable: Rs. <?= number_format(abs($p['net_balance']), 2) ?>
                            <?php else: ?>
                                Rs. 0.00
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/parties/profile.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">View</a>
                            <a href="<?= BASE_URL ?>/parties/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <?php if (isAdmin()): ?>
                                <a href="<?= BASE_URL ?>/parties/delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this party and all their transactions? This cannot be undone.');">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php else: ?>
    <div class="alert alert-info">No parties found.</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
