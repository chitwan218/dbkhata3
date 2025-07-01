<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = new Database();

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("Invalid item ID.");
}

// Fetch item
$db->query("SELECT * FROM items WHERE id = :id");
$db->bind(':id', $id);
$item = $db->single();

if (!$item) {
    die("Item not found.");
}

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adjustment = $_POST['adjustment'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if (!is_numeric($adjustment)) {
        $errors[] = "Adjustment must be a numeric value.";
    } else {
        $adjustment = floatval($adjustment);
        $newStock = $item['stock_quantity'] + $adjustment;
        if ($newStock < 0) {
            $errors[] = "Stock quantity cannot be negative.";
        }
    }

    if (empty($errors)) {
        // Update stock quantity
        $db->query("UPDATE items SET stock_quantity = :stock_quantity WHERE id = :id");
        $db->bind(':stock_quantity', $newStock);
        $db->bind(':id', $id);
        if ($db->execute()) {
            // Optionally, you may want to log this adjustment in a stock_adjustments table (not provided here)
            $message = "Stock adjusted successfully. New stock: $newStock";
            $item['stock_quantity'] = $newStock;
        } else {
            $errors[] = "Failed to adjust stock.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2>Adjust Stock for: <?= htmlspecialchars($item['name']) ?></h2>

    <p><strong>Current Stock:</strong> <?= number_format($item['stock_quantity'], 2) ?></p>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="adjustment" class="form-label">Adjustment (use negative to reduce stock)</label>
            <input type="number" step="0.01" id="adjustment" name="adjustment" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="note" class="form-label">Note (optional)</label>
            <textarea id="note" name="note" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Apply Adjustment</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
