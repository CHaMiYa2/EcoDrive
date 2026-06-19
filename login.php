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
  <title>Login — EcoDrive</title>
</head>
<body>
  <h2>EcoDrive — Login</h2>

  <?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if (isset($_GET['registered'])): ?>
    <p style="color:green;">Registration successful! Please log in.</p>
  <?php endif; ?>

  <?php if (isset($_GET['logout'])): ?>
    <p style="color:green;">You have been logged out.</p>
  <?php endif; ?>

  <form method="POST">
    <label>Role:<br>
      <select name="role">
        <option value="driver">Driver</option>
        <option value="admin">Admin</option>
      </select>
    </label><br><br>

    <label>Username:<br>
      <input type="text" name="username" required>
    </label><br><br>

    <label>Password:<br>
      <input type="password" name="password" required>
    </label><br><br>

    <button type="submit">Login</button>
  </form>

  <p>New driver? <a href="/register.php">Register here</a></p>
</body>
</html>
