<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
$db = new Database();

// Fetch filters
$party_id = $_GET['party_id'] ?? '';
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

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
        ORDER BY t.date DESC, t.id DESC 
        LIMIT $limit OFFSET $offset";

$db->query($sql);
foreach ($params as $key => $val) {
    $db->bind($key, $val);
}
$transactions = $db->resultSet();

// Count total for pagination
$db->query("SELECT COUNT(*) AS total FROM transactions t $where");
foreach ($params as $key => $val) {
    $db->bind($key, $val);
}
$total = $db->single()['total'] ?? 0;
$total_pages = ceil($total / $limit);

// Fetch party list
$db->query("SELECT id, name FROM parties ORDER BY name");
$parties = $db->resultSet();

// Running balance logic (manual)
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

include __DIR__ . '/../includes/header.php';
?>

<h2>Ledger Report</h2>

<form method="get" class="row mb-3 g-2">
    <div class="col-md-3">
        <label>Party</label>
        <select name="party_id" class="form-select">
            <option value="">All</option>
            <?php foreach ($parties as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $party_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label>Type</label>
        <select name="type" class="form-select">
            <option value="">All</option>
            <?php foreach (['sale','purchase','receipt','payment','income','expense'] as $t): ?>
                <option value="<?= $t ?>" <?= $type == $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label>From Date</label>
        <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control">
    </div>
    <div class="col-md-2">
        <label>To Date</label>
        <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control">
    </div>
    <div class="col-md-3 d-grid">
        <label>&nbsp;</label>
        <button class="btn btn-primary">Filter</button>
    </div>
</form>

<?php if (count($running) > 0): ?>
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
                <?php foreach ($running as $row): $trn = $row['trn']; ?>
                    <tr>
                        <td><?= $trn['date'] ?></td>
                        <td><?= htmlspecialchars($trn['party_name']) ?></td>
                        <td><?= ucfirst($trn['type']) ?></td>
                        <td><?= ucfirst($trn['payment_mode']) ?></td>
                        <td><?= number_format($trn['amount'], 2) ?></td>
                        <td><?= number_format($row['balance'], 2) ?></td>
                        <td><?= htmlspecialchars($trn['description']) ?></td>
                        <td class="text-center">
                            <?php if (in_array($trn['type'], ['sale', 'purchase', 'receipt', 'payment'])): ?>
                                <a href="<?= BASE_URL ?>/transactions/invoice.php?id=<?= $trn['id'] ?>" class="btn btn-sm btn-outline-primary" title="Invoice">🧾</a>
                            <?php else: ?>
                                &mdash;
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
    <div class="alert alert-info">No transactions found.</div>
<?php endif; ?>
<a href="export_ledger.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-success mt-2">Export to CSV</a>

<?php include __DIR__ . '/../includes/footer.php'; ?>
