<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$db = new Database();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // Validation
    if ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $db->query("SELECT id FROM users WHERE email = :email");
        $db->bind(':email', $email);
        if ($db->single()) {
            $message = "Email already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $db->query("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'user')");
            $db->bind(':username', $username);
            $db->bind(':email', $email);
            $db->bind(':password', $hashedPassword);

            if ($db->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $message = "Error creating account.";
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4">Create New Account</h2>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
        </div>

        <div class="mb-3">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
        <a href="login.php" class="btn btn-link">Back to Login</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
