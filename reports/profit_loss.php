<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = new Database();

function getProfitLoss($db, $start_date, $end_date) {
    $sql = "
        SELECT
            IFNULL(SUM(CASE
                WHEN type IN ('receipt', 'income') THEN amount
                WHEN type = 'sale' AND payment_mode IN ('cash', 'bank') THEN amount
                ELSE 0
            END), 0) AS cash_in,

            IFNULL(SUM(CASE
                WHEN type IN ('payment', 'expense') THEN amount
                WHEN type = 'purchase' AND payment_mode IN ('cash', 'bank') THEN amount
                ELSE 0
            END), 0) AS cash_out,

            (
                IFNULL(SUM(CASE
                    WHEN type IN ('receipt', 'income') THEN amount
                    WHEN type = 'sale' AND payment_mode IN ('cash', 'bank') THEN amount
                    ELSE 0
                END), 0)
                -
                IFNULL(SUM(CASE
                    WHEN type IN ('payment', 'expense') THEN amount
                    WHEN type = 'purchase' AND payment_mode IN ('cash', 'bank') THEN amount
                    ELSE 0
                END), 0)
            ) AS profit_loss
        FROM transactions
        WHERE date BETWEEN :start_date AND :end_date
    ";
    $db->query($sql);
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);
    return $db->single();
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

// Handle custom range form submission
$customPL = null;
$customStart = $_GET['custom_start'] ?? '';
$customEnd = $_GET['custom_end'] ?? '';

if ($customStart && $customEnd) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $customStart) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $customEnd)) {
        $customPL = getProfitLoss($db, $customStart, $customEnd);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4">Profit & Loss Summary</h2>

    <div class="row g-4 mb-5">
        <?php
        function plCardClass($amount) {
            return $amount >= 0 ? 'border-success text-success' : 'border-danger text-danger';
        }
        function plArrowIcon($amount) {
            return $amount >= 0 ? '▲' : '▼';
        }
        ?>

        <div class="col-md-4">
            <div class="card shadow-sm <?= plCardClass($thisMonthPL['profit_loss']) ?>">
                <div class="card-body">
                    <h5 class="card-title fw-bold">This Month</h5>
                    <small class="text-muted"><?= $startThisMonth ?> to <?= $endThisMonth ?></small>
                    <hr>
                    <p class="mb-1">Cash/Bank In: <span class="fw-semibold text-success">Rs. <?= number_format($thisMonthPL['cash_in'], 2) ?></span></p>
                    <p class="mb-3">Cash/Bank Out: <span class="fw-semibold text-danger">Rs. <?= number_format($thisMonthPL['cash_out'], 2) ?></span></p>
                    <h4 class="fw-bold <?= plCardClass($thisMonthPL['profit_loss']) ?>">
                        Profit / Loss: <?= plArrowIcon($thisMonthPL['profit_loss']) ?> Rs. <?= number_format($thisMonthPL['profit_loss'], 2) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm <?= plCardClass($lastMonthPL['profit_loss']) ?>">
                <div class="card-body">
                    <h5 class="card-title fw-bold">Last Month</h5>
                    <small class="text-muted"><?= $startLastMonth ?> to <?= $endLastMonth ?></small>
                    <hr>
                    <p class="mb-1">Cash/Bank In: <span class="fw-semibold text-success">Rs. <?= number_format($lastMonthPL['cash_in'], 2) ?></span></p>
                    <p class="mb-3">Cash/Bank Out: <span class="fw-semibold text-danger">Rs. <?= number_format($lastMonthPL['cash_out'], 2) ?></span></p>
                    <h4 class="fw-bold <?= plCardClass($lastMonthPL['profit_loss']) ?>">
                        Profit / Loss: <?= plArrowIcon($lastMonthPL['profit_loss']) ?> Rs. <?= number_format($lastMonthPL['profit_loss'], 2) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm <?= plCardClass($thisYearPL['profit_loss']) ?>">
                <div class="card-body">
                    <h5 class="card-title fw-bold">This Year</h5>
                    <small class="text-muted"><?= $startThisYear ?> to <?= $endThisYear ?></small>
                    <hr>
                    <p class="mb-1">Cash/Bank In: <span class="fw-semibold text-success">Rs. <?= number_format($thisYearPL['cash_in'], 2) ?></span></p>
                    <p class="mb-3">Cash/Bank Out: <span class="fw-semibold text-danger">Rs. <?= number_format($thisYearPL['cash_out'], 2) ?></span></p>
                    <h4 class="fw-bold <?= plCardClass($thisYearPL['profit_loss']) ?>">
                        Profit / Loss: <?= plArrowIcon($thisYearPL['profit_loss']) ?> Rs. <?= number_format($thisYearPL['profit_loss'], 2) ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Date Range -->
    <div class="card shadow-sm p-4">
        <h4 class="mb-3">Custom Date Range Profit & Loss</h4>
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="custom_start" class="form-label">Start Date</label>
                <input type="date" id="custom_start" name="custom_start" class="form-control" value="<?= htmlspecialchars($customStart) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="custom_end" class="form-label">End Date</label>
                <input type="date" id="custom_end" name="custom_end" class="form-control" value="<?= htmlspecialchars($customEnd) ?>" required>
            </div>
            <div class="col-md-4 d-grid">
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </form>

        <?php if ($customPL !== null): ?>
            <hr>
            <h5>Results for <?= htmlspecialchars($customStart) ?> to <?= htmlspecialchars($customEnd) ?></h5>
            <p>Cash/Bank In: <span class="text-success fw-semibold">Rs. <?= number_format($customPL['cash_in'], 2) ?></span></p>
            <p>Cash/Bank Out: <span class="text-danger fw-semibold">Rs. <?= number_format($customPL['cash_out'], 2) ?></span></p>
            <h4 class="<?= plCardClass($customPL['profit_loss']) ?>">
                Profit / Loss: <?= plArrowIcon($customPL['profit_loss']) ?> Rs. <?= number_format($customPL['profit_loss'], 2) ?>
            </h4>
        <?php elseif($customStart || $customEnd): ?>
            <div class="alert alert-warning mt-3">Please enter valid start and end dates.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
