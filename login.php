<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
startSession();

if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'driver';

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $db = getDB();

        if ($role === 'admin') {
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        } else {
            $stmt = $db->prepare("SELECT * FROM drivers WHERE username = ? LIMIT 1");
        }

        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $role;
            if ($role === 'driver') {
                $_SESSION['full_name'] = $user['full_name'];
            }

            if ($role === 'admin') {
                header('Location: /admin_dashboard.php');
            } else {
                header('Location: /driver_dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — EcoDrive</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-logo">
      <div class="icon">🚛</div>
      <div class="name">EcoDrive</div>
    </div>
    <h2>Sign In</h2>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">Registration successful! Please log in.</div>
    <?php endif; ?>

    <?php if (isset($_GET['logout'])): ?>
      <div class="alert alert-success">You have been logged out.</div>
    <?php endif; ?>

    <form method="POST">
      <label>Role</label>
      <select name="role">
        <option value="driver">Driver</option>
        <option value="admin">Fleet Admin</option>
      </select>

      <label>Username</label>
      <input type="text" name="username" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <button type="submit" class="btn-block">Login</button>
    </form>

    <div class="auth-footer">
      New driver? <a href="/register.php">Register here</a>
    </div>
  </div>
</body>
</html>
