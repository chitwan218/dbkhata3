<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/Database.php';

requireLogin();
if (!isAdmin()) {
    die('<div class="alert alert-danger m-4">Access denied. Admins only.</div>');
}

$db = new Database();
$db->query("SELECT * FROM users ORDER BY id DESC");
$users = $db->resultSet();

$deleted = isset($_GET['deleted']) ? true : false;
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">User Management</h2>
        <a href="add.php" class="btn btn-success">➕ Add New User</a>
    </div>

    <?php if ($deleted): ?>
        <div class="alert alert-success">✅ User deleted successfully.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th style="width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $i => $user): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                <?= ucfirst($user['role']) ?></span></td>
                            <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <?php if ($_SESSION['user']['id'] != $user['id']): ?>
                                    <a href="delete.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
