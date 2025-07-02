<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
$db = new Database();
$message = '';
$transaction_id = $_GET['id'] ?? null;

if (!$transaction_id || !is_numeric($transaction_id)) {
    header("Location: index.php?message=" . urlencode("Invalid transaction ID.") . "&type=danger");
    exit;
}

$db->query("SELECT * FROM transactions WHERE id = :id LIMIT 1");
$db->bind(':id', $transaction_id);
$transaction = $db->single();

if (!$transaction) {
    header("Location: index.php?message=" . urlencode("Transaction not found.") . "&type=danger");
    exit;
}

$db->query("SELECT id, name FROM parties ORDER BY name");
$parties = $db->resultSet();

$db->query("SELECT * FROM transaction_items WHERE transaction_id = :id");
$db->bind(':id', $transaction_id);
$items = $db->resultSet();

$transaction_types = ['sale', 'purchase', 'income', 'expense'];
$payment_modes = ['cash', 'bank', 'credit'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $party_id = $_POST['party_id'] ?? '';
    $type = $_POST['type'] ?? '';
    $payment_mode = $_POST['payment_mode'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['date'] ?? '';

    if (!$party_id || !in_array($type, $transaction_types) || !in_array($payment_mode, $payment_modes) || !$amount || !is_numeric($amount) || $amount <= 0 || !$date) {
        $message = "Please fill all required fields correctly.";
    } else {
        $db->query("UPDATE transactions SET party_id=:party_id, type=:type, payment_mode=:payment_mode, amount=:amount, description=:description, date=:date WHERE id=:id");
        $db->bind(':party_id', $party_id);
        $db->bind(':type', $type);
        $db->bind(':payment_mode', $payment_mode);
        $db->bind(':amount', $amount);
        $db->bind(':description', $description);
        $db->bind(':date', $date);
        $db->bind(':id', $transaction_id);

        if ($db->execute()) {
            // Remove old items
            $db->query("DELETE FROM transaction_items WHERE transaction_id = :id");
            $db->bind(':id', $transaction_id);
            $db->execute();

            // Insert new items if sale/purchase
            if (in_array($type, ['sale', 'purchase']) && !empty($_POST['item_name'])) {
                foreach ($_POST['item_name'] as $i => $itemName) {
                    $qty = $_POST['qty'][$i] ?? 0;
                    $rate = $_POST['rate'][$i] ?? 0;
                    $total = $_POST['total'][$i] ?? 0;

                    if ($itemName && $qty > 0 && $rate > 0) {
                        $db->query("INSERT INTO transaction_items (transaction_id, item_name, qty, rate, total) VALUES (:transaction_id, :item_name, :qty, :rate, :total)");
                        $db->bind(':transaction_id', $transaction_id);
                        $db->bind(':item_name', $itemName);
                        $db->bind(':qty', $qty);
                        $db->bind(':rate', $rate);
                        $db->bind(':total', $total);
                        $db->execute();
                    }
                }
            }

            header("Location: index.php?message=" . urlencode("Transaction updated successfully.") . "&type=success");
            exit;
        } else {
            $message = "Error updating transaction.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2>Edit Transaction</h2>
<?php if ($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post" id="transactionForm">
    <div class="mb-3">
        <label>Party *</label>
        <select name="party_id" class="form-select" required>
            <?php foreach ($parties as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $transaction['party_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Transaction Type *</label>
        <select name="type" id="type" class="form-select" required onchange="toggleItemBlock()">
            <?php foreach ($transaction_types as $tt): ?>
                <option value="<?= $tt ?>" <?= $tt == $transaction['type'] ? 'selected' : '' ?>>
                    <?= ucfirst($tt) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Payment Mode *</label>
        <select name="payment_mode" id="payment_mode" class="form-select" required>
            <?php foreach ($payment_modes as $mode): ?>
                <option value="<?= $mode ?>" <?= $mode == $transaction['payment_mode'] ? 'selected' : '' ?>>
                    <?= ucfirst($mode) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="items-block" style="display: none;">
        <h5>Item Details</h5>
        <div id="item-list">
            <?php if (count($items) > 0): foreach ($items as $item): ?>
                <div class="row mb-2 item-row">
                    <div class="col-md-4">
                        <input type="text" name="item_name[]" class="form-control" placeholder="Item Name" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="qty[]" class="form-control qty" step="0.01" min="0" value="<?= $item['qty'] ?>" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="rate[]" class="form-control rate" step="0.01" min="0" value="<?= $item['rate'] ?>" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="total[]" class="form-control total" value="<?= $item['total'] ?>" readonly>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-item">X</button>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <!-- Default one row -->
                <div class="row mb-2 item-row">
                    <div class="col-md-4">
                        <input type="text" name="item_name[]" class="form-control" placeholder="Item Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="qty[]" class="form-control qty" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="rate[]" class="form-control rate" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="total[]" class="form-control total" readonly>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-item">X</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" onclick="addItemRow()" class="btn btn-sm btn-secondary mb-3">Add More</button>
    </div>

    <div class="mb-3">
        <label>Amount (auto if items used) *</label>
        <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" value="<?= $transaction['amount'] ?>" required>
    </div>

    <div class="mb-3">
        <label>Date *</label>
        <input type="date" name="date" class="form-control" value="<?= $transaction['date'] ?>" required>
    </div>

    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($transaction['description']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Update Transaction</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<script>
function toggleItemBlock() {
    const type = document.getElementById('type').value;
    const block = document.getElementById('items-block');
    block.style.display = (type === 'sale' || type === 'purchase') ? 'block' : 'none';
}

function addItemRow() {
    const row = document.querySelector('.item-row').cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('item-list').appendChild(row);
    bindItemEvents();
}

function bindItemEvents() {
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = row.querySelector('.qty');
        const rate = row.querySelector('.rate');
        const total = row.querySelector('.total');

        const calc = () => {
            const q = parseFloat(qty.value) || 0;
            const r = parseFloat(rate.value) || 0;
            total.value = (q * r).toFixed(2);
            calcTotal();
        };

        qty.addEventListener('input', calc);
        rate.addEventListener('input', calc);
    });

    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.onclick = () => {
            if (document.querySelectorAll('.item-row').length > 1) {
                btn.closest('.item-row').remove();
                calcTotal();
            }
        };
    });
}

function calcTotal() {
    let sum = 0;
    document.querySelectorAll('.total').forEach(input => {
        sum += parseFloat(input.value) || 0;
    });
    document.getElementById('amount').value = sum.toFixed(2);
}

document.addEventListener('DOMContentLoaded', () => {
    toggleItemBlock();
    bindItemEvents();
    calcTotal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
