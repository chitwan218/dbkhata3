<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = new Database();

// Fetch all items
$db->query("SELECT * FROM items ORDER BY name ASC");
$items = $db->resultSet();

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2>Items & Stock</h2>
    <a href="add.php" class="btn btn-success mb-3">+ Add New Item</a>
    
    <?php if (count($items) === 0): ?>
        <p>No items found. Add some!</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Stock Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                            <td>Rs. <?= number_format($item['purchase_price'], 2) ?></td>
                            <td>Rs. <?= number_format($item['selling_price'], 2) ?></td>
                            <td><?= number_format($item['stock_quantity'], 2) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="stock_adjust.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">Adjust Stock</a>
                                <a href="delete.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
