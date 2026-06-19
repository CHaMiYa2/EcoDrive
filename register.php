<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
startSession();

if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error  = '';
$success = '';

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

    // Validation
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
        // Age check (must be 24+)
        $birth = new DateTime($birthday);
        $today = new DateTime();
        $age   = $today->diff($birth)->y;

        if ($age < 24) {
            $error = 'You must be at least 24 years old to register. Your age: ' . $age;
        } else {
            $db = getDB();

            // Check username taken
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
  <title>Register — EcoDrive</title>
</head>
<body>
  <h2>Driver Registration</h2>

  <?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST">

    <h3>Personal Information</h3>

    <label>Full Name *<br>
      <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
    </label><br><br>

    <label>Birthday * (must be 24+)<br>
      <input type="date" name="birthday" value="<?= htmlspecialchars($_POST['birthday'] ?? '') ?>"
             max="<?= date('Y-m-d', strtotime('-24 years')) ?>" required>
    </label><br><br>

    <label>Gender *<br>
      <select name="gender" required>
        <option value="">Select</option>
        <option value="male"   <?= (($_POST['gender'] ?? '') === 'male')   ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
        <option value="other"  <?= (($_POST['gender'] ?? '') === 'other')  ? 'selected' : '' ?>>Other</option>
      </select>
    </label><br><br>

    <label>Address *<br>
      <textarea name="address" rows="3" cols="40" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
    </label><br><br>

    <label>License Class *<br>
      <select name="license_class" required>
        <option value="">Select</option>
        <option value="A" <?= (($_POST['license_class'] ?? '') === 'A') ? 'selected' : '' ?>>Class A — Heavy Vehicles</option>
        <option value="B" <?= (($_POST['license_class'] ?? '') === 'B') ? 'selected' : '' ?>>Class B — Light Vehicles</option>
        <option value="C" <?= (($_POST['license_class'] ?? '') === 'C') ? 'selected' : '' ?>>Class C — Motorcycles</option>
      </select>
    </label><br><br>

    <h3>Location</h3>

    <label>Country *<br>
      <input type="text" name="country" value="<?= htmlspecialchars($_POST['country'] ?? '') ?>" required>
    </label><br><br>

    <label>Region *<br>
      <input type="text" name="region" value="<?= htmlspecialchars($_POST['region'] ?? '') ?>" required>
    </label><br><br>

    <label>City *<br>
      <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required>
    </label><br><br>

    <h3>Vehicle Information</h3>

    <label>Vehicle Model *<br>
      <input type="text" name="vehicle_model" value="<?= htmlspecialchars($_POST['vehicle_model'] ?? '') ?>" required>
    </label><br><br>

    <label>Fuel Type *<br>
      <select name="fuel_type" required>
        <option value="">Select</option>
        <option value="Petrol"   <?= (($_POST['fuel_type'] ?? '') === 'Petrol')   ? 'selected' : '' ?>>Petrol</option>
        <option value="Diesel"   <?= (($_POST['fuel_type'] ?? '') === 'Diesel')   ? 'selected' : '' ?>>Diesel</option>
        <option value="Hybrid"   <?= (($_POST['fuel_type'] ?? '') === 'Hybrid')   ? 'selected' : '' ?>>Hybrid</option>
        <option value="Electric" <?= (($_POST['fuel_type'] ?? '') === 'Electric') ? 'selected' : '' ?>>Electric (EV)</option>
        <option value="LPG"      <?= (($_POST['fuel_type'] ?? '') === 'LPG')      ? 'selected' : '' ?>>LPG</option>
        <option value="CNG"      <?= (($_POST['fuel_type'] ?? '') === 'CNG')      ? 'selected' : '' ?>>CNG</option>
      </select>
    </label><br><br>

    <h3>Account</h3>

    <label>Username * (min 4 chars)<br>
      <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    </label><br><br>

    <label>Password * (min 8 chars)<br>
      <input type="password" name="password" required>
    </label><br><br>

    <label>Confirm Password *<br>
      <input type="password" name="confirm_password" required>
    </label><br><br>

    <button type="submit">Register</button>
  </form>

  <p>Already have an account? <a href="/login.php">Login here</a></p>
</body>
</html>
