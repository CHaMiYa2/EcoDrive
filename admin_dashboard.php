<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
requireAdmin();

$db    = getDB();
$error = '';
$success = '';

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM drivers WHERE id = ?")->execute([$id]);
    $success = 'Driver deleted successfully.';
}

// UPDATE
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

// FETCH EDIT
$editDriver = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM drivers WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editDriver = $stmt->fetch();
}

// SEARCH + LIST
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

$fuelBadgeMap = [
    'Electric' => 'badge-green', 'Hybrid' => 'badge-green',
    'Petrol' => 'badge-slate', 'Diesel' => 'badge-slate',
    'LPG' => 'badge-blue', 'CNG' => 'badge-blue'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — EcoDrive</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="dash-layout">

  <aside class="sidebar">
    <div class="brand">
      <div class="icon">🛡</div>
      <div>
        <div class="name">EcoDrive</div>
        <div class="role">Fleet Admin</div>
      </div>
    </div>
    <nav>
      <a href="/admin_dashboard.php" class="active">📊 Drivers</a>
    </nav>
    <div class="user-box">
      <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
      <div class="user-role">Administrator</div>
      <a href="/logout.php" class="btn btn-outline btn-sm btn-block">Logout</a>
    </div>
  </aside>

  <div class="main">
    <div class="page-header">
      <h2>Fleet Dashboard</h2>
    </div>

    <?php if ($error):   ?><div class="alert alert-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="label">Total Drivers</div>
        <div class="value"><?= $totalRows ?></div>
      </div>
    </div>

    <?php if ($editDriver): ?>
      <div class="card">
        <div class="card-header">Edit Driver</div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="update_driver" value="1">
            <input type="hidden" name="id" value="<?= $editDriver['id'] ?>">

            <label>Full Name *</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($editDriver['full_name']) ?>" required>

            <div class="form-row">
              <div>
                <label>Birthday *</label>
                <input type="date" name="birthday" value="<?= htmlspecialchars($editDriver['birthday']) ?>" required>
              </div>
              <div>
                <label>Gender *</label>
                <select name="gender" required>
                  <option value="male"   <?= $editDriver['gender'] === 'male'   ? 'selected' : '' ?>>Male</option>
                  <option value="female" <?= $editDriver['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                  <option value="other"  <?= $editDriver['gender'] === 'other'  ? 'selected' : '' ?>>Other</option>
                </select>
              </div>
            </div>

            <label>Address *</label>
            <textarea name="address" rows="2" required><?= htmlspecialchars($editDriver['address']) ?></textarea>

            <div class="form-row">
              <div>
                <label>Country *</label>
                <input type="text" name="country" value="<?= htmlspecialchars($editDriver['country']) ?>" required>
              </div>
              <div>
                <label>Region *</label>
                <input type="text" name="region" value="<?= htmlspecialchars($editDriver['region']) ?>" required>
              </div>
            </div>

            <label>City *</label>
            <input type="text" name="city" value="<?= htmlspecialchars($editDriver['city']) ?>" required>

            <div class="form-row">
              <div>
                <label>License Class *</label>
                <select name="license_class" required>
                  <option value="A" <?= $editDriver['license_class'] === 'A' ? 'selected' : '' ?>>Class A</option>
                  <option value="B" <?= $editDriver['license_class'] === 'B' ? 'selected' : '' ?>>Class B</option>
                  <option value="C" <?= $editDriver['license_class'] === 'C' ? 'selected' : '' ?>>Class C</option>
                </select>
              </div>
              <div>
                <label>Fuel Type *</label>
                <select name="fuel_type" required>
                  <option value="Petrol"   <?= $editDriver['fuel_type'] === 'Petrol'   ? 'selected' : '' ?>>Petrol</option>
                  <option value="Diesel"   <?= $editDriver['fuel_type'] === 'Diesel'   ? 'selected' : '' ?>>Diesel</option>
                  <option value="Hybrid"   <?= $editDriver['fuel_type'] === 'Hybrid'   ? 'selected' : '' ?>>Hybrid</option>
                  <option value="Electric" <?= $editDriver['fuel_type'] === 'Electric' ? 'selected' : '' ?>>Electric</option>
                  <option value="LPG"      <?= $editDriver['fuel_type'] === 'LPG'      ? 'selected' : '' ?>>LPG</option>
                  <option value="CNG"      <?= $editDriver['fuel_type'] === 'CNG'      ? 'selected' : '' ?>>CNG</option>
                </select>
              </div>
            </div>

            <label>Vehicle Model *</label>
            <input type="text" name="vehicle_model" value="<?= htmlspecialchars($editDriver['vehicle_model']) ?>" required>

            <button type="submit">Save Changes</button>
            <a href="/admin_dashboard.php" class="btn btn-outline">Cancel</a>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">Registered Drivers (<?= $totalRows ?>)</div>
      <div class="card-body">

        <form method="GET" class="search-bar">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, city, vehicle...">
          <button type="submit" class="btn-sm">Search</button>
          <?php if ($search): ?>
            <a href="/admin_dashboard.php" class="btn btn-outline btn-sm">Clear</a>
          <?php endif; ?>
        </form>

        <?php if (empty($drivers)): ?>
          <p>No drivers found.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>#</th><th>Name</th><th>Username</th><th>Location</th>
                <th>License</th><th>Vehicle</th><th>Fuel</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($drivers as $i => $d): ?>
                <tr>
                  <td><?= $offset + $i + 1 ?></td>
                  <td><strong><?= htmlspecialchars($d['full_name']) ?></strong></td>
                  <td><?= htmlspecialchars($d['username']) ?></td>
                  <td><?= htmlspecialchars($d['city'] . ', ' . $d['country']) ?></td>
                  <td><span class="badge badge-blue">Class <?= htmlspecialchars($d['license_class']) ?></span></td>
                  <td><?= htmlspecialchars($d['vehicle_model']) ?></td>
                  <td><span class="badge <?= $fuelBadgeMap[$d['fuel_type']] ?? 'badge-slate' ?>"><?= htmlspecialchars($d['fuel_type']) ?></span></td>
                  <td>
                    <a href="/admin_dashboard.php?edit=<?= $d['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-outline btn-sm">Edit</a>
                    <a href="/admin_dashboard.php?delete=<?= $d['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Delete <?= htmlspecialchars($d['full_name']) ?>?')">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <?php if ($totalPages > 1): ?>
            <div class="pagination">
              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p == $page): ?>
                  <strong><?= $p ?></strong>
                <?php else: ?>
                  <a href="/admin_dashboard.php?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $p ?></a>
                <?php endif; ?>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>
</body>
</html>
