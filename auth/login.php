<?php
require_once '../config/config.php';
require_once '../includes/database.php';
session_start();

$db = new Database();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

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

        if ($remember) {
            setcookie("remember_email", $email, time() + (30 * 24 * 60 * 60)); // 30 days
        } else {
            setcookie("remember_email", "", time() - 3600); // Clear cookie
        }

        header("Location: " . BASE_URL . "/dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}

$rememberedEmail = $_COOKIE['remember_email'] ?? '';
?>

<?php include '../includes/header.php'; ?>

<style>
  body, html {
    height: 100vh;
    margin: 0;
  }
  .login-container {
    display: flex;
    height: 100vh;
  }
  .hero-left {
    flex: 1;
    background: url("<?= BASE_URL ?>/assets/hero.jpg") center center / cover no-repeat;
    min-width: 300px;
  }
  .form-right {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    background-color: #f8f9fa;
  }
  .login-card {
    background-color: #fff;
    padding: 2.5rem 2rem;
    border-radius: 10px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    width: 100%;
    max-width: 420px;
  }
  .login-card h2 {
    font-weight: 700;
    color: #0d2d52;
  }
</style>

<div class="login-container">
  <div class="hero-left d-none d-md-block"></div>

  <div class="form-right">
    <div class="login-card">
      <h2 class="mb-3 text-center">
        <i class="bi bi-person-circle me-1"></i> Welcome Back
      </h2>
      <p class="text-muted text-center mb-4">Login to access your dashboard</p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($rememberedEmail) ?>" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" <?= $rememberedEmail ? 'checked' : '' ?>>
            <label class="form-check-label" for="remember">Remember Me</label>
          </div>
          <a href="#" class="text-decoration-none small text-primary">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-box-arrow-in-right me-1"></i> Login
        </button>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
