<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
$db = new Database();

function getProfitLoss($db, $start_date, $end_date) {
    $db->query("
        SELECT
            IFNULL(SUM(CASE WHEN type = 'receipt' AND payment_mode IN ('cash', 'bank') THEN amount
                            WHEN type = 'income' THEN amount ELSE 0 END), 0) AS cash_in,
            IFNULL(SUM(CASE WHEN type = 'payment' AND payment_mode IN ('cash', 'bank') THEN amount
                            WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS cash_out
        FROM transactions
        WHERE date BETWEEN :start_date AND :end_date
    ");
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);
    $result = $db->single();
    $result['profit_loss'] = $result['cash_in'] - $result['cash_out'];
    return $result;
}

$now = new DateTime();
$startThisMonth = $now->format('Y-m-01');
$endThisMonth = $now->format('Y-m-t');
$lastMonth = (clone $now)->modify('first day of last month');
$startLastMonth = $lastMonth->format('Y-m-01');
$endLastMonth = $lastMonth->format('Y-m-t');
$startThisYear = $now->format('Y-01-01');
$endThisYear = $now->format('Y-12-31');

$thisMonthPL = getProfitLoss($db, $startThisMonth, $endThisMonth);
$lastMonthPL = getProfitLoss($db, $startLastMonth, $endLastMonth);
$thisYearPL = getProfitLoss($db, $startThisYear, $endThisYear);

// Fetch last 15 transactions with pagination support
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$db->query("SELECT COUNT(*) as count FROM transactions WHERE type NOT IN ('income', 'expense')");
$totalRecords = $db->single()['count'];
$totalPages = ceil($totalRecords / $limit);

$db->query("SELECT t.*, p.name AS party_name
            FROM transactions t
            JOIN parties p ON p.id = t.party_id
            WHERE t.type NOT IN ('income', 'expense')
            ORDER BY t.date DESC, t.id DESC
            LIMIT :limit OFFSET :offset");
$db->bind(':limit', $limit);
$db->bind(':offset', $offset);
$recent = $db->resultSet();

include __DIR__ . '/includes/header.php';
?>

<h2 class="mb-4">Dashboard</h2>
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card border-success shadow-sm">
      <div class="card-body">
        <h5 class="card-title text-success fw-bold"><i class="bi bi-calendar-event me-1"></i> This Month</h5>
        <p class="mb-1">Cash In: <strong class="text-success">Rs. <?= number_format($thisMonthPL['cash_in'], 2) ?></strong></p>
        <p class="mb-1">Cash Out: <strong class="text-danger">Rs. <?= number_format($thisMonthPL['cash_out'], 2) ?></strong></p>
        <p class="mt-2 fw-bold">Profit/Loss: <span class="<?= $thisMonthPL['profit_loss'] >= 0 ? 'text-success' : 'text-danger' ?>">
          Rs. <?= number_format($thisMonthPL['profit_loss'], 2) ?></span></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-info shadow-sm">
      <div class="card-body">
        <h5 class="card-title text-info fw-bold"><i class="bi bi-calendar3 me-1"></i> Last Month</h5>
        <p class="mb-1">Cash In: <strong class="text-success">Rs. <?= number_format($lastMonthPL['cash_in'], 2) ?></strong></p>
        <p class="mb-1">Cash Out: <strong class="text-danger">Rs. <?= number_format($lastMonthPL['cash_out'], 2) ?></strong></p>
        <p class="mt-2 fw-bold">Profit/Loss: <span class="<?= $lastMonthPL['profit_loss'] >= 0 ? 'text-success' : 'text-danger' ?>">
          Rs. <?= number_format($lastMonthPL['profit_loss'], 2) ?></span></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-warning shadow-sm">
      <div class="card-body">
        <h5 class="card-title text-warning fw-bold"><i class="bi bi-calendar-range me-1"></i> This Year</h5>
        <p class="mb-1">Cash In: <strong class="text-success">Rs. <?= number_format($thisYearPL['cash_in'], 2) ?></strong></p>
        <p class="mb-1">Cash Out: <strong class="text-danger">Rs. <?= number_format($thisYearPL['cash_out'], 2) ?></strong></p>
        <p class="mt-2 fw-bold">Profit/Loss: <span class="<?= $thisYearPL['profit_loss'] >= 0 ? 'text-success' : 'text-danger' ?>">
          Rs. <?= number_format($thisYearPL['profit_loss'], 2) ?></span></p>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <canvas id="barChart"></canvas>
  </div>
  <div class="col-md-6">
    <canvas id="pieChart"></canvas>
  </div>
</div>

<h4 class="mt-5">Recent Transactions</h4>
<div class="table-responsive">
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Date</th>
        <th>Party</th>
        <th>Type</th>
        <th>Mode</th>
        <th>Amount</th>
        <th>Description</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recent as $txn): ?>
        <tr>
          <td><?= htmlspecialchars($txn['date']) ?></td>
          <td><?= htmlspecialchars($txn['party_name']) ?></td>
          <td><?= ucfirst($txn['type']) ?></td>
          <td><?= ucfirst($txn['payment_mode']) ?></td>
          <td><?= number_format($txn['amount'], 2) ?></td>
          <td><?= htmlspecialchars($txn['description']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<nav>
  <ul class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const barCtx = document.getElementById('barChart');
new Chart(barCtx, {
  type: 'bar',
  data: {
    labels: ['Last Month', 'This Month', 'This Year'],
    datasets: [
      {
        label: 'Cash In',
        backgroundColor: '#28a745',
        data: [<?= $lastMonthPL['cash_in'] ?>, <?= $thisMonthPL['cash_in'] ?>, <?= $thisYearPL['cash_in'] ?>]
      },
      {
        label: 'Cash Out',
        backgroundColor: '#dc3545',
        data: [<?= $lastMonthPL['cash_out'] ?>, <?= $thisMonthPL['cash_out'] ?>, <?= $thisYearPL['cash_out'] ?>]
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      title: {
        display: true,
        text: 'Cash In/Out Summary'
      }
    }
  }
});

const pieCtx = document.getElementById('pieChart');
new Chart(pieCtx, {
  type: 'line',
  data: {
    labels: ['Last Month', 'This Month', 'This Year'],
    datasets: [{
      label: 'Profit/Loss',
      backgroundColor: 'rgba(13, 202, 240, 0.5)',
      borderColor: '#0dcaf0',
      fill: true,
      tension: 0.4,
      data: [<?= $lastMonthPL['profit_loss'] ?>, <?= $thisMonthPL['profit_loss'] ?>, <?= $thisYearPL['profit_loss'] ?>]
    }]
  },
  options: {
    responsive: true,
    plugins: {
      title: {
        display: true,
        text: 'Profit/Loss Overview'
      }
    }
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
