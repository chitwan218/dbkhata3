<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = new Database();

// Handle filters
$party_id = $_GET['party_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Pagination settings
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build WHERE clause dynamically
$where = [];
$params = [];

if ($party_id !== '') {
    $where[] = 't.party_id = :party_id';
    $params[':party_id'] = $party_id;
}

if ($start_date !== '') {
    $where[] = 't.date >= :start_date';
    $params[':start_date'] = $start_date;
}

if ($end_date !== '') {
    $where[] = 't.date <= :end_date';
    $params[':end_date'] = $end_date;
}

$whereSql = '';
if ($where) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Get total count for pagination
$db->query("SELECT COUNT(*) as total FROM transactions t $whereSql");
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$totalRows = $db->single()['total'];
$totalPages = ceil($totalRows / $perPage);

// Fetch transactions with joins
$sql = "
    SELECT 
        t.*, 
        p.name AS party_name
    FROM transactions t
    JOIN parties p ON t.party_id = p.id
    $whereSql
    ORDER BY t.date DESC, t.id DESC
    LIMIT :offset, :perPage
";

$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$db->bind(':offset', $offset, PDO::PARAM_INT);
$db->bind(':perPage', $perPage, PDO::PARAM_INT);

$transactions = $db->resultSet();

// Fetch all parties for filter dropdown
$db->query("SELECT id, name FROM parties ORDER BY name");
$parties = $db->resultSet();

include __DIR__ . '/../includes/header.php';
?>

<h2>Transactions</h2>

<form method="get" class="row g-3 mb-4">
    <div class="col-md-4">
        <label for="party_id" class="form-label">Party</label>
        <select name="party_id" id="party_id" class="form-select">
            <option value="">-- All Parties --</option>
            <?php foreach ($parties as $party): ?>
                <option value="<?= $party['id'] ?>" <?= $party_id == $party['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($party['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label for="start_date" class="form-label">Start Date</label>
        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control">
    </div>
    <div class="col-md-3">
        <label for="end_date" class="form-label">End Date</label>
        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
</form>

<div class="mb-3">
    <a href="add.php" class="btn btn-success">Add New Transaction</a>
</div>

<?php if (count($transactions) > 0): ?>
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Party</th>
                <th>Type</th>
                <th>Payment Mode</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tr): ?>
            <tr>
                <td><?= htmlspecialchars($tr['date']) ?></td>
                <td><?= htmlspecialchars($tr['party_name']) ?></td>
                <td><?= ucfirst(htmlspecialchars($tr['type'])) ?></td>
                <td><?= ucfirst(htmlspecialchars($tr['payment_mode'])) ?></td>
                <td>Rs. <?= number_format($tr['amount'], 2) ?></td>
                <td><?= htmlspecialchars($tr['description']) ?></td>
                <td>
                    <a href="edit.php?id=<?= $tr['id'] ?>" class="btn btn-sm btn-primary" title="Edit">✏️</a>
                    <a href="delete.php?id=<?= $tr['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure to delete this transaction?');">🗑️</a>
                    <?php if (in_array($tr['type'], ['sale', 'purchase'])): ?>
                        <a href="invoice.php?id=<?= $tr['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View Invoice">🧾</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation">
  <ul class="pagination justify-content-center">
    <?php if ($page > 1): ?>
    <li class="page-item">
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
    </li>
    <?php else: ?>
    <li class="page-item disabled"><span class="page-link">Previous</span></li>
    <?php endif; ?>

    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <li class="page-item <?= $p == $page ? 'active' : '' ?>">
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
    <li class="page-item">
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
    </li>
    <?php else: ?>
    <li class="page-item disabled"><span class="page-link">Next</span></li>
    <?php endif; ?>
  </ul>
</nav>

<?php else: ?>
<div class="alert alert-info">No transactions found.</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
