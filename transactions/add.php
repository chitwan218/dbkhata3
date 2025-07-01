<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = new Database();
$message = '';

// Fetch parties
$db->query("SELECT id, name FROM parties ORDER BY name");
$parties = $db->resultSet();

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
        $db->query("INSERT INTO transactions (party_id, type, payment_mode, amount, description, date) VALUES (:party_id, :type, :payment_mode, :amount, :description, :date)");
        $db->bind(':party_id', $party_id);
        $db->bind(':type', $type);
        $db->bind(':payment_mode', $payment_mode);
        $db->bind(':amount', $amount);
        $db->bind(':description', $description);
        $db->bind(':date', $date);
        
        if ($db->execute()) {
            $transaction_id = $db->lastInsertId();

            // If sale or purchase, store items
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

            header("Location: " . BASE_URL . "/transactions/index.php?message=" . urlencode("Transaction added successfully.") . "&type=success");
            exit;
        } else {
            $message = "Error adding transaction.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">Add Transaction</h2>

<?php if ($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post" id="transactionForm">
    <div class="mb-3">
        <label>Party *</label>
        <select name="party_id" class="form-select" required>
            <option value="">-- Select Party --</option>
            <?php foreach ($parties as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Transaction Type *</label>
        <select name="type" id="type" class="form-select" required onchange="toggleItemBlock()">
            <option value="">-- Select Type --</option>
            <?php foreach ($transaction_types as $type): ?>
                <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Payment Mode *</label>
        <select name="payment_mode" id="payment_mode" class="form-select" required>
            <option value="">-- Select Mode --</option>
            <?php foreach ($payment_modes as $mode): ?>
                <option value="<?= $mode ?>"><?= ucfirst($mode) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="items-block" style="display: none;">
        <h5>Item Details</h5>
        <div id="item-list">
            <div class="row mb-2 item-row">
                <div class="col-md-4">
                    <input type="text" name="item_name[]" class="form-control" placeholder="Item Name" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="qty[]" class="form-control qty" placeholder="Qty" step="0.01" min="0" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="rate[]" class="form-control rate" placeholder="Rate" step="0.01" min="0" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="total[]" class="form-control total" placeholder="Total" readonly>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-item">X</button>
                </div>
            </div>
        </div>
        <button type="button" onclick="addItemRow()" class="btn btn-secondary btn-sm mb-3">Add More Item</button>
    </div>

    <div class="mb-3">
        <label>Amount (auto if items used) *</label>
        <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" required>
    </div>

    <div class="mb-3">
        <label>Date *</label>
        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control"></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Add Transaction</button>
    <a href="<?= BASE_URL ?>/transactions/index.php" class="btn btn-secondary">Cancel</a>
</form>

<script>
function toggleItemBlock() {
    const type = document.getElementById('type').value;
    const itemsBlock = document.getElementById('items-block');
    itemsBlock.style.display = (type === 'sale' || type === 'purchase') ? 'block' : 'none';
}

function addItemRow() {
    const row = document.querySelector('.item-row').cloneNode(true);
    row.querySelectorAll('input').forEach(input => input.value = '');
    document.getElementById('item-list').appendChild(row);
    bindQtyRateListeners();
}

function bindQtyRateListeners() {
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = row.querySelector('.qty');
        const rate = row.querySelector('.rate');
        const total = row.querySelector('.total');

        function calculateRowTotal() {
            const q = parseFloat(qty.value) || 0;
            const r = parseFloat(rate.value) || 0;
            total.value = (q * r).toFixed(2);
            calculateGrandTotal();
        }

        qty.addEventListener('input', calculateRowTotal);
        rate.addEventListener('input', calculateRowTotal);
    });

    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.onclick = function () {
            if (document.querySelectorAll('.item-row').length > 1) {
                btn.closest('.item-row').remove();
                calculateGrandTotal();
            }
        };
    });
}

function calculateGrandTotal() {
    let sum = 0;
    document.querySelectorAll('.total').forEach(input => {
        sum += parseFloat(input.value) || 0;
    });
    document.getElementById('amount').value = sum.toFixed(2);
}

document.addEventListener('DOMContentLoaded', () => {
    toggleItemBlock();
    bindQtyRateListeners();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
