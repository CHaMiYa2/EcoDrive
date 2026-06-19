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
    $full_name     = trim($_POST['full_name']     ?? '');
    $birthday      = trim($_POST['birthday']      ?? '');
    $gender        = trim($_POST['gender']        ?? '');
    $address       = trim($_POST['address']       ?? '');
    $country       = trim($_POST['country']       ?? '');
    $region        = trim($_POST['region']        ?? '');
    $city          = trim($_POST['city']          ?? '');
    $license_class = trim($_POST['license_class'] ?? '');
    $vehicle_model = trim($_POST['vehicle_model'] ?? '');
    $fuel_type     = trim($_POST['fuel_type']     ?? '');
    $username      = trim($_POST['username']      ?? '');
    $password      = $_POST['password']           ?? '';
    $confirm_pass  = $_POST['confirm_password']   ?? '';

    if (empty($full_name) || empty($birthday) || empty($gender) || empty($address) ||
        empty($country) || empty($region) || empty($city) || empty($license_class) ||
        empty($vehicle_model) || empty($fuel_type) || empty($username) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 4) {
        $error = 'Username must be at least 4 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Passwords do not match.';
    } else {
        $birth = new DateTime($birthday);
        $today = new DateTime();
        $age   = $today->diff($birth)->y;

        if ($age < 24) {
            $error = 'You must be at least 24 years old to register. Your age: ' . $age;
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM drivers WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username is already taken.';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("
                    INSERT INTO drivers
                        (full_name, birthday, gender, address, country, region, city, license_class, vehicle_model, fuel_type, username, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $full_name, $birthday, $gender, $address,
                    $country, $region, $city,
                    $license_class, $vehicle_model, $fuel_type,
                    $username, $hashed
                ]);
                header('Location: /login.php?registered=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — EcoDrive</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
  <div class="auth-wrap wide">
    <div class="auth-logo">
      <div class="icon">🚛</div>
      <div class="name">EcoDrive</div>
    </div>
    <h2>Driver Registration</h2>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

      <div class="section-title">Personal Information</div>

      <label>Full Name *</label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>

      <div class="form-row">
        <div>
          <label>Date of Birth * (must be 24+)</label>
          <input type="date" name="birthday" value="<?= htmlspecialchars($_POST['birthday'] ?? '') ?>"
                 max="<?= date('Y-m-d', strtotime('-24 years')) ?>" required>
        </div>
        <div>
          <label>Gender *</label>
          <select name="gender" required>
            <option value="">Select</option>
            <option value="male"   <?= (($_POST['gender'] ?? '') === 'male')   ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
            <option value="other"  <?= (($_POST['gender'] ?? '') === 'other')  ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
      </div>

      <label>Address *</label>
      <textarea name="address" rows="3" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

      <label>License Class *</label>
      <select name="license_class" required>
        <option value="">Select</option>
        <option value="A" <?= (($_POST['license_class'] ?? '') === 'A') ? 'selected' : '' ?>>Class A — Heavy Vehicles</option>
        <option value="B" <?= (($_POST['license_class'] ?? '') === 'B') ? 'selected' : '' ?>>Class B — Light Vehicles</option>
        <option value="C" <?= (($_POST['license_class'] ?? '') === 'C') ? 'selected' : '' ?>>Class C — Motorcycles</option>
      </select>

      <div class="section-title">Location</div>

      <div class="form-row">
        <div>
          <label>Country *</label>
          <input type="text" name="country" value="<?= htmlspecialchars($_POST['country'] ?? '') ?>" required>
        </div>
        <div>
          <label>Region *</label>
          <input type="text" name="region" value="<?= htmlspecialchars($_POST['region'] ?? '') ?>" required>
        </div>
      </div>

      <label>City *</label>
      <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required>

      <div class="section-title">Vehicle Information</div>

      <div class="form-row">
        <div>
          <label>Vehicle Model *</label>
          <input type="text" name="vehicle_model" value="<?= htmlspecialchars($_POST['vehicle_model'] ?? '') ?>" required>
        </div>
        <div>
          <label>Fuel Type *</label>
          <select name="fuel_type" required>
            <option value="">Select</option>
            <option value="Petrol"   <?= (($_POST['fuel_type'] ?? '') === 'Petrol')   ? 'selected' : '' ?>>Petrol</option>
            <option value="Diesel"   <?= (($_POST['fuel_type'] ?? '') === 'Diesel')   ? 'selected' : '' ?>>Diesel</option>
            <option value="Hybrid"   <?= (($_POST['fuel_type'] ?? '') === 'Hybrid')   ? 'selected' : '' ?>>Hybrid</option>
            <option value="Electric" <?= (($_POST['fuel_type'] ?? '') === 'Electric') ? 'selected' : '' ?>>Electric (EV)</option>
            <option value="LPG"      <?= (($_POST['fuel_type'] ?? '') === 'LPG')      ? 'selected' : '' ?>>LPG</option>
            <option value="CNG"      <?= (($_POST['fuel_type'] ?? '') === 'CNG')      ? 'selected' : '' ?>>CNG</option>
          </select>
        </div>
      </div>

      <div class="section-title">Account</div>

      <label>Username * (min 4 chars)</label>
      <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

      <div class="form-row">
        <div>
          <label>Password * (min 8 chars)</label>
          <input type="password" name="password" required>
        </div>
        <div>
          <label>Confirm Password *</label>
          <input type="password" name="confirm_password" required>
        </div>
      </div>

      <button type="submit" class="btn-block">Create Driver Account</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="/login.php">Login here</a>
    </div>
  </div>
</body>
</html>
