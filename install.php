<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host']);
    $dbname = trim($_POST['db_name']);
    $user = trim($_POST['db_user']);
    $pass = $_POST['db_pass'];
    $adminUser = trim($_POST['admin_user']);
    $adminEmail = trim($_POST['admin_email']);
    $adminPass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);

    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create DB if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");

        // Create tables
        $sql = file_get_contents(__DIR__ . '/db/schema.sql');
        $pdo->exec($sql);

        // Insert admin
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:u, :e, :p, 'admin')");
        $stmt->execute([
            ':u' => $adminUser,
            ':e' => $adminEmail,
            ':p' => $adminPass
        ]);

        // Save config
        $config = "<?php
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('BASE_URL', 'http://localhost/dbkhata3'); // Change if hosted elsewhere
";

        if (!file_put_contents(__DIR__ . '/config/config.php', $config)) {
            throw new Exception("Failed to write config.php. Check folder permissions.");
        }

        $success = '✅ Installation complete! <a href="auth/login.php" class="alert-link">Login here</a>.';
    } catch (Exception $e) {
        $error = '❌ Installation failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DBKhata v3 Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="col-md-8 col-lg-6 bg-white p-4 p-md-5 shadow rounded">
            <h3 class="mb-4 text-center">🚀 DBKhata v3 Installer</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Database Host</label>
                    <input type="text" name="db_host" class="form-control" required value="localhost">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Database Name</label>
                    <input type="text" name="db_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Database User</label>
                    <input type="text" name="db_user" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Database Password</label>
                    <input type="password" name="db_pass" class="form-control">
                </div>
                <hr class="mt-4 mb-3">
                <h5 class="text-center">🔐 Admin Setup</h5>
                <div class="col-md-6">
                    <label class="form-label">Admin Username</label>
                    <input type="text" name="admin_user" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admin Email</label>
                    <input type="email" name="admin_email" class="form-control" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Admin Password</label>
                    <input type="password" name="admin_pass" class="form-control" required>
                </div>
                <div class="col-12 d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Install DBKhata</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
