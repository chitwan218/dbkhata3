<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/Database.php';

requireLogin();
if (!isAdmin()) {
    die('<div class="alert alert-danger m-4">Access denied. Admins only.</div>');
}

$db = new Database();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if email or username exists
        $db->query("SELECT id FROM users WHERE email = :email OR username = :username");
        $db->bind(':email', $email);
        $db->bind(':username', $username);
        $existing = $db->single();

        if ($existing) {
            $error = "Username or email already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $db->query("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
            $db->bind(':username', $username);
            $db->bind(':email', $email);
            $db->bind(':password', $hashedPassword);
            $db->bind(':role', $role);
            $db->execute();

            $success = "✅ User added successfully!";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5" style="max-width: 600px;">
    <h2 class="mb-4">Add New User</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-select" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Add User</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
