<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = new Database();
$message = '';

$party_id = $_GET['party_id'] ?? null;
$selectedParty = null;
$autoAmount = '';

// Fetch all parties
$db->query("SELECT id, name FROM parties ORDER BY name");
$parties = $db->resultSet();

// If party is pre-selected, fetch net balance to determine payable
if ($party_id && is_numeric($party_id)) {
    $db->query("SELECT id, name FROM parties WHERE id = :id");
    $db->bind(':id', $party_id);
    $selectedParty = $db->single();

    if ($selectedParty) {
        // Net balance calculation (excluding income/expense)
        $db->query("
            SELECT 
                SUM(CASE 
                    WHEN type = 'sale' AND payment_mode = 'credit' THEN amount
                    WHEN type = 'purchase' AND payment_mode = 'credit' THEN -amount
                    WHEN type = 'receipt' AND payment_mode IN ('cash', 'bank') THEN -amount
                    WHEN type = 'payment' AND payment_mode IN ('cash', 'bank') THEN amount
                    ELSE 0
                END) AS net_balance
            FROM transactions
            WHERE party_id = :id AND type NOT IN ('income', 'expense')
        ");
        $db->bind(':id', $party_id);
        $res = $db->single();
        $net = $res['net_balance'] ?? 0;

        // If party has payable (negative balance), suggest amount
        $autoAmount = $net < 0 ? number_format(abs($net), 2, '.', '') : '';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $party_id = $_POST['party_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $description = trim($_POST['description'] ?? '');

    if (!$party_id || !$amount || $amount <= 0) {
        $message = "Please select a party and enter a valid amount.";
    } else {
        $db->query("INSERT INTO transactions (party_id, type, payment_mode, amount, description, date) 
                    VALUES (:party_id, 'payment', 'cash', :amount, :description, :date)");
        $db->bind(':party_id', $party_id);
        $db->bind(':amount', $amount);
        $db->bind(':description', $description);
        $db->bind(':date', $date);

        if ($db->execute()) {
            header("Location: profile.php?id=$party_id&message=" . urlencode("Payment recorded successfully.") . "&type=success");
            exit;
        } else {
            $message = "Failed to record payment.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2>Record Payment to Party</h2>

<?php if ($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <label for="party_id">Select Party</label>
        <select name="party_id" id="party_id" class="form-select" required <?= $selectedParty ? 'readonly disabled' : '' ?>>
            <option value="">-- Select Party --</option>
            <?php foreach ($parties as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $party_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($selectedParty): ?>
            <input type="hidden" name="party_id" value="<?= $selectedParty['id'] ?>">
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="amount">Amount</label>
        <input type="number" name="amount" id="amount" step="0.01" class="form-control" required value="<?= htmlspecialchars($autoAmount) ?>">
        <?php if ($autoAmount): ?>
            <small class="text-muted">Suggested amount based on current payable balance.</small>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="date">Date</label>
        <input type="date" name="date" id="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="mb-3">
        <label for="description">Description (optional)</label>
        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
    </div>

    <button type="submit" class="btn btn-success">Record Payment</button>
    <a href="profile.php?id=<?= $party_id ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
