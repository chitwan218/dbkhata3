<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = new Database();

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $purchase_price = $_POST['purchase_price'] ?? '0';
    $selling_price = $_POST['selling_price'] ?? '0';
    $stock_quantity = $_POST['stock_quantity'] ?? '0';

    // Validation
    if ($name === '') {
        $errors[] = 'Item name is required.';
    }
    if (!is_numeric($purchase_price) || $purchase_price < 0) {
        $errors[] = 'Purchase price must be a non-negative number.';
    }
    if (!is_numeric($selling_price) || $selling_price < 0) {
        $errors[] = 'Selling price must be a non-negative number.';
    }
    if (!is_numeric($stock_quantity) || $stock_quantity < 0) {
        $errors[] = 'Stock quantity must be a non-negative number.';
    }

    if (empty($errors)) {
        $db->query("INSERT INTO items (name, description, purchase_price, selling_price, stock_quantity) VALUES (:name, :description, :purchase_price, :selling_price, :stock_quantity)");
        $db->bind(':name', $name);
        $db->bind(':description', $description);
        $db->bind(':purchase_price', $purchase_price);
        $db->bind(':selling_price', $selling_price);
        $db->bind(':stock_quantity', $stock_quantity);

        if ($db->execute()) {
            $message = "Item added successfully.";
            // Clear form inputs after success
            $name = $description = '';
            $purchase_price = $selling_price = $stock_quantity = 0;
        } else {
            $errors[] = "Failed to add item. Please try again.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2>Add New Item</h2>

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

    <form method="post" class="mt-3">
        <div class="mb-3">
            <label for="name" class="form-label">Item Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($name ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description (optional)</label>
            <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($description ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="purchase_price" class="form-label">Purchase Price</label>
            <input type="number" step="0.01" min="0" id="purchase_price" name="purchase_price" class="form-control" value="<?= htmlspecialchars($purchase_price ?? '0') ?>">
        </div>
        <div class="mb-3">
            <label for="selling_price" class="form-label">Selling Price</label>
            <input type="number" step="0.01" min="0" id="selling_price" name="selling_price" class="form-control" value="<?= htmlspecialchars($selling_price ?? '0') ?>">
        </div>
        <div class="mb-3">
            <label for="stock_quantity" class="form-label">Stock Quantity</label>
            <input type="number" step="0.01" min="0" id="stock_quantity" name="stock_quantity" class="form-control" value="<?= htmlspecialchars($stock_quantity ?? '0') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Add Item</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
