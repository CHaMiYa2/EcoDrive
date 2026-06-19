<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
requireAdmin();

$db    = getDB();
$error = '';
$success = '';

// ── DELETE ────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM drivers WHERE id = ?")->execute([$id]);
    $success = 'Driver deleted successfully.';
}

// ── UPDATE ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_driver'])) {
    $id            = (int)$_POST['id'];
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

    if (empty($full_name) || empty($birthday) || empty($gender) || empty($address) ||
        empty($country) || empty($region) || empty($city) || empty($license_class) ||
        empty($vehicle_model) || empty($fuel_type)) {
        $error = 'All fields are required.';
    } else {
        $birth = new DateTime($birthday);
        $age   = (new DateTime())->diff($birth)->y;
        if ($age < 24) {
            $error = 'Driver must be at least 24 years old.';
        } else {
            $stmt = $db->prepare("
                UPDATE drivers SET
                    full_name = ?, birthday = ?, gender = ?, address = ?,
                    country = ?, region = ?, city = ?,
                    license_class = ?, vehicle_model = ?, fuel_type = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $full_name, $birthday, $gender, $address,
                $country, $region, $city,
                $license_class, $vehicle_model, $fuel_type,
                $id
            ]);
            $success = 'Driver updated successfully.';
        }
    }
}

// ── FETCH EDIT DRIVER ─────────────────────────────────────────────
$editDriver = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM drivers WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editDriver = $stmt->fetch();
}

// ── SEARCH + LIST ─────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

if ($search) {
    $like  = '%' . $search . '%';
    $total = $db->prepare("SELECT COUNT(*) FROM drivers WHERE full_name LIKE ? OR city LIKE ? OR vehicle_model LIKE ?");
    $total->execute([$like, $like, $like]);
    $stmt  = $db->prepare("SELECT * FROM drivers WHERE full_name LIKE ? OR city LIKE ? OR vehicle_model LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$like, $like, $like, $limit, $offset]);
} else {
    $total = $db->query("SELECT COUNT(*) FROM drivers");
    $stmt  = $db->prepare("SELECT * FROM drivers ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
}

$totalRows  = (int)$total->fetchColumn();
$drivers    = $stmt->fetchAll();
$totalPages = (int)ceil($totalRows / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard — EcoDrive</title>
</head>
<body>
  <h2>Admin Dashboard</h2>
  <p>Logged in as: <?= htmlspecialchars($_SESSION['username']) ?> | <a href="/logout.php">Logout</a></p>

  <?php if ($error):   ?><p style="color:red;"><?=   htmlspecialchars($error)   ?></p><?php endif; ?>
  <?php if ($success): ?><p style="color:green;"><?= htmlspecialchars($success) ?></p><?php endif; ?>

  <hr>

  <!-- EDIT FORM -->
  <?php if ($editDriver): ?>
    <h3>Edit Driver</h3>
    <form method="POST">
      <input type="hidden" name="update_driver" value="1">
      <input type="hidden" name="id" value="<?= $editDriver['id'] ?>">

      <label>Full Name *<br>
        <input type="text" name="full_name" value="<?= htmlspecialchars($editDriver['full_name']) ?>" required>
      </label><br><br>

      <label>Birthday *<br>
        <input type="date" name="birthday" value="<?= htmlspecialchars($editDriver['birthday']) ?>" required>
      </label><br><br>

      <label>Gender *<br>
        <select name="gender" required>
          <option value="male"   <?= $editDriver['gender'] === 'male'   ? 'selected' : '' ?>>Male</option>
          <option value="female" <?= $editDriver['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
          <option value="other"  <?= $editDriver['gender'] === 'other'  ? 'selected' : '' ?>>Other</option>
        </select>
      </label><br><br>

      <label>Address *<br>
        <textarea name="address" rows="3" cols="40" required><?= htmlspecialchars($editDriver['address']) ?></textarea>
      </label><br><br>

      <label>Country *<br>
        <input type="text" name="country" value="<?= htmlspecialchars($editDriver['country']) ?>" required>
      </label><br><br>

      <label>Region *<br>
        <input type="text" name="region" value="<?= htmlspecialchars($editDriver['region']) ?>" required>
      </label><br><br>

      <label>City *<br>
        <input type="text" name="city" value="<?= htmlspecialchars($editDriver['city']) ?>" required>
      </label><br><br>

      <label>License Class *<br>
        <select name="license_class" required>
          <option value="A" <?= $editDriver['license_class'] === 'A' ? 'selected' : '' ?>>Class A</option>
          <option value="B" <?= $editDriver['license_class'] === 'B' ? 'selected' : '' ?>>Class B</option>
          <option value="C" <?= $editDriver['license_class'] === 'C' ? 'selected' : '' ?>>Class C</option>
        </select>
      </label><br><br>

      <label>Vehicle Model *<br>
        <input type="text" name="vehicle_model" value="<?= htmlspecialchars($editDriver['vehicle_model']) ?>" required>
      </label><br><br>

      <label>Fuel Type *<br>
        <select name="fuel_type" required>
          <option value="Petrol"   <?= $editDriver['fuel_type'] === 'Petrol'   ? 'selected' : '' ?>>Petrol</option>
          <option value="Diesel"   <?= $editDriver['fuel_type'] === 'Diesel'   ? 'selected' : '' ?>>Diesel</option>
          <option value="Hybrid"   <?= $editDriver['fuel_type'] === 'Hybrid'   ? 'selected' : '' ?>>Hybrid</option>
          <option value="Electric" <?= $editDriver['fuel_type'] === 'Electric' ? 'selected' : '' ?>>Electric</option>
          <option value="LPG"      <?= $editDriver['fuel_type'] === 'LPG'      ? 'selected' : '' ?>>LPG</option>
          <option value="CNG"      <?= $editDriver['fuel_type'] === 'CNG'      ? 'selected' : '' ?>>CNG</option>
        </select>
      </label><br><br>

      <button type="submit">Save Changes</button>
      <a href="/admin_dashboard.php">Cancel</a>
    </form>
    <hr>
  <?php endif; ?>

  <!-- SEARCH -->
  <h3>All Drivers (<?= $totalRows ?>)</h3>
  <form method="GET">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, city, vehicle...">
    <button type="submit">Search</button>
    <?php if ($search): ?>
      <a href="/admin_dashboard.php">Clear</a>
    <?php endif; ?>
  </form>

  <br>

  <!-- DRIVERS TABLE -->
  <?php if (empty($drivers)): ?>
    <p>No drivers found.</p>
  <?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
      <thead>
        <tr>
          <th>#</th>
          <th>Full Name</th>
          <th>Username</th>
          <th>Birthday</th>
          <th>Gender</th>
          <th>Country</th>
          <th>Region</th>
          <th>City</th>
          <th>License</th>
          <th>Vehicle</th>
          <th>Fuel</th>
          <th>Registered</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($drivers as $i => $d): ?>
          <tr>
            <td><?= $offset + $i + 1 ?></td>
            <td><?= htmlspecialchars($d['full_name']) ?></td>
            <td><?= htmlspecialchars($d['username']) ?></td>
            <td><?= htmlspecialchars($d['birthday']) ?></td>
            <td><?= htmlspecialchars($d['gender']) ?></td>
            <td><?= htmlspecialchars($d['country']) ?></td>
            <td><?= htmlspecialchars($d['region']) ?></td>
            <td><?= htmlspecialchars($d['city']) ?></td>
            <td><?= htmlspecialchars($d['license_class']) ?></td>
            <td><?= htmlspecialchars($d['vehicle_model']) ?></td>
            <td><?= htmlspecialchars($d['fuel_type']) ?></td>
            <td><?= htmlspecialchars($d['created_at']) ?></td>
            <td>
              <a href="/admin_dashboard.php?edit=<?= $d['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Edit</a> |
              <a href="/admin_dashboard.php?delete=<?= $d['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                 onclick="return confirm('Delete <?= htmlspecialchars($d['full_name']) ?>?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
      <br>
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <?php if ($p == $page): ?>
          <strong><?= $p ?></strong>
        <?php else: ?>
          <a href="/admin_dashboard.php?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    <?php endif; ?>
  <?php endif; ?>

</body>
</html>
