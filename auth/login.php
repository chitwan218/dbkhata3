<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
session_start();

$db = new Database();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $db->query("SELECT * FROM users WHERE email = :email LIMIT 1");
    $db->bind(':email', $email);
    $user = $db->single();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role']
        ];
        header("Location: " . BASE_URL . "/dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4">Login</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Login</button>
        <a href="register.php" class="btn btn-link">Create Account</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
