<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = new Database();

// Validate party
$party_id = $_GET['id'] ?? null;
if (!$party_id || !is_numeric($party_id)) {
    header("Location: index.php?message=Invalid party ID.&type=danger");
    exit;
}

// Fetch party
$db->query("SELECT * FROM parties WHERE id = :id");
$db->bind(':id', $party_id);
$party = $db->single();
if (!$party) {
    header("Location: index.php?message=Party not found.&type=danger");
    exit;
}

// Balance logic matching ledger
$db->query("
    SELECT type, payment_mode, amount
    FROM transactions
    WHERE party_id = :id AND type NOT IN ('income', 'expense')
    ORDER BY date ASC, id ASC
");
$db->bind(':id', $party_id);
$allTransactions = $db->resultSet();

$balance = 0;
foreach ($allTransactions as $t) {
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

// Date filter + pagination
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$filterSQL = "WHERE party_id = :id";
$params = [':id' => $party_id];

if ($start_date) {
    $filterSQL .= " AND date >= :start_date";
    $params[':start_date'] = $start_date;
}
if ($end_date) {
    $filterSQL .= " AND date <= :end_date";
    $params[':end_date'] = $end_date;
}

// Fetch transactions
$db->query("SELECT * FROM transactions $filterSQL ORDER BY date DESC, id DESC LIMIT $limit OFFSET $offset");
foreach ($params as $k => $v) $db->bind($k, $v);
$transactions = $db->resultSet();

// Count total for pagination
$db->query("SELECT COUNT(*) as total FROM transactions $filterSQL");
foreach ($params as $k => $v) $db->bind($k, $v);
$total_rows = $db->single()['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);

// Fetch documents
$db->query("SELECT * FROM party_documents WHERE party_id = :id ORDER BY uploaded_at DESC");
$db->bind(':id', $party_id);
$documents = $db->resultSet();

// Fetch links
$db->query("SELECT * FROM party_profile_links WHERE party_id = :id ORDER BY id DESC");
$db->bind(':id', $party_id);
$links = $db->resultSet();

include __DIR__ . '/../includes/header.php';
?>

<h2>Party Profile: <?= htmlspecialchars($party['name']) ?></h2>

<div class="row mb-4">
    <div class="col-md-4 text-center">
        <?php if ($party['profile_image'] && file_exists(__DIR__ . '/../uploads/profile/' . $party['profile_image'])): ?>
            <img src="<?= BASE_URL ?>/secure_view.php?file=profile/<?= urlencode($party['profile_image']) ?>" 
                 alt="Profile Image" class="img-fluid rounded mb-3" style="max-height: 250px;">
        <?php else: ?>
            <div class="border bg-light p-5 mb-3">No Profile Image</div>
        <?php endif; ?>

        <a href="edit.php?id=<?= $party_id ?>" class="btn btn-primary w-100 mb-2">Edit Party</a>
        <a href="receipt.php?party_id=<?= $party_id ?>" class="btn btn-info w-100 mb-2">Record Receipt</a>
        <a href="payment.php?party_id=<?= $party_id ?>" class="btn btn-warning w-100 mb-2">Record Payment</a>
        <a href="index.php" class="btn btn-secondary w-100">Back</a>
    </div>

    <div class="col-md-8">
        <h5>Contact Info</h5>
        <p><strong>Phone:</strong> <?= htmlspecialchars($party['phone']) ?: '-' ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($party['email']) ?: '-' ?></p>
        <p><strong>Address:</strong><br><?= nl2br(htmlspecialchars($party['address'])) ?: '-' ?></p>

        <hr>
        <h5>Net Balance</h5>
        <p><strong>Balance:</strong> Rs. <?= number_format($balance, 2) ?> 
            <?= $balance > 0 ? '(Receivable)' : ($balance < 0 ? '(Payable)' : '(Settled)') ?>
        </p>

        <hr>
        <h5>Profile Links</h5>
        <?php if (count($links)): ?>
            <ul>
                <?php foreach ($links as $link): ?>
                    <li><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank"><?= htmlspecialchars($link['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No links added.</p>
        <?php endif; ?>

        <hr>
        <h5>Documents</h5>
        <?php if (count($documents)): ?>
            <div class="row">
                <?php foreach ($documents as $doc): 
                    $filePath = 'document/parties/' . $doc['file_name'];
                    $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg','jpeg','png','gif']);
                    $isPDF = $ext === 'pdf';
                ?>
                <div class="col-md-6 mb-2">
                    <strong><?= htmlspecialchars($doc['custom_name'] ?: $doc['file_name']) ?></strong><br>
                    <?php if ($isImage): ?>
                        <img src="<?= BASE_URL ?>/secure_view.php?file=<?= urlencode($filePath) ?>" class="img-thumbnail" style="max-height:150px;">
                    <?php elseif ($isPDF): ?>
                        <iframe src="<?= BASE_URL ?>/secure_view.php?file=<?= urlencode($filePath) ?>" width="100%" height="200px"></iframe>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/secure_view.php?file=<?= urlencode($filePath) ?>" target="_blank">Download</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No documents uploaded.</p>
        <?php endif; ?>
    </div>
</div>

<hr>
<h5>Recent Transactions</h5>

<form method="get" class="row g-2 mb-3">
    <input type="hidden" name="id" value="<?= $party_id ?>">
    <div class="col-md-3">
        <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control" placeholder="Start date">
    </div>
    <div class="col-md-3">
        <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control" placeholder="End date">
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary">Filter</button>
    </div>
</form>

<?php if (count($transactions)): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Mode</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['date']) ?></td>
                        <td><?= ucfirst($t['type']) ?></td>
                        <td><?= ucfirst($t['payment_mode']) ?></td>
                        <td><?= number_format($t['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($t['description']) ?></td>
                        <td class="text-center">
                            <?php if (in_array($t['type'], ['sale', 'purchase', 'receipt', 'payment'])): ?>
                                <a href="<?= BASE_URL ?>/transactions/invoice.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">🧾</a>
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
    <div class="alert alert-warning">No transactions found.</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
