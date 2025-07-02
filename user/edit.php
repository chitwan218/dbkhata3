<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';

requireLogin();
if (!isAdmin()) {
    die('<div class="alert alert-danger m-4">Access denied. Admins only.</div>');
}

$db = new Database();
$error = '';
$success = '';
$id = $_GET['id'] ?? null;

if (!$id) {
    die('<div class="alert alert-danger m-4">Invalid user ID.</div>');
}

// Fetch user
$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $id);
$user = $db->single();

if (!$user) {
    die('<div class="alert alert-danger m-4">User not found.</div>');
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $role     = $_POST['role'];
    $password = $_POST['password'];

    if (empty($username) || empty($email)) {
        $error = "Username and email are required.";
    } else {
        // Check for duplicates
        $db->query("SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id");
        $db->bind(':email', $email);
        $db->bind(':username', $username);
        $db->bind(':id', $id);
        $existing = $db->single();

        if ($existing) {
            $error = "Username or email already exists.";
        } else {
            // Update
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $db->query("UPDATE users SET username = :username, email = :email, password = :password, role = :role WHERE id = :id");
                $db->bind(':password', $hashedPassword);
            } else {
                $db->query("UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id");
            }

            $db->bind(':username', $username);
            $db->bind(':email', $email);
            $db->bind(':role', $role);
            $db->bind(':id', $id);
            $db->execute();

            $success = "✅ User updated successfully.";
            // Refresh user info
            $user['username'] = $username;
            $user['email'] = $email;
            $user['role'] = $role;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5" style="max-width: 600px;">
    <h2>Edit User</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>New Password <small>(Leave blank to keep existing)</small></label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-select" required>
                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
