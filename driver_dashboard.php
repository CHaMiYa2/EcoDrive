<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
requireDriver();

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM drivers WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$driver = $stmt->fetch();

$age = (new DateTime($driver['birthday']))->diff(new DateTime())->y;

$fuelBadge = [
    'Electric' => 'badge-green', 'Hybrid' => 'badge-green',
    'Petrol' => 'badge-slate', 'Diesel' => 'badge-slate',
    'LPG' => 'badge-blue', 'CNG' => 'badge-blue'
][$driver['fuel_type']] ?? 'badge-slate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Dashboard — EcoDrive</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="dash-layout">

  <aside class="sidebar">
    <div class="brand">
      <div class="icon">🚛</div>
      <div>
        <div class="name">EcoDrive</div>
        <div class="role">Driver Portal</div>
      </div>
    </div>
    <nav>
      <a href="/driver_dashboard.php" class="active">🏠 Dashboard</a>
    </nav>
    <div class="user-box">
      <div class="user-name"><?= htmlspecialchars($driver['full_name']) ?></div>
      <div class="user-role">Driver</div>
      <a href="/logout.php" class="btn btn-outline btn-sm btn-block">Logout</a>
    </div>
  </aside>

  <div class="main">
    <div class="page-header">
      <h2>My Dashboard</h2>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="label">License Class</div>
        <div class="value">Class <?= htmlspecialchars($driver['license_class']) ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Age</div>
        <div class="value"><?= $age ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Vehicle</div>
        <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($driver['vehicle_model']) ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Fuel Type</div>
        <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($driver['fuel_type']) ?></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Personal Information</div>
      <div class="card-body">
        <div class="info-grid">
          <div><div class="info-label">Full Name</div><div class="info-value"><?= htmlspecialchars($driver['full_name']) ?></div></div>
          <div><div class="info-label">Username</div><div class="info-value">@<?= htmlspecialchars($driver['username']) ?></div></div>
          <div><div class="info-label">Birthday</div><div class="info-value"><?= htmlspecialchars($driver['birthday']) ?></div></div>
          <div><div class="info-label">Gender</div><div class="info-value"><?= htmlspecialchars(ucfirst($driver['gender'])) ?></div></div>
          <div class="full"><div class="info-label">Address</div><div class="info-value"><?= nl2br(htmlspecialchars($driver['address'])) ?></div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Location</div>
      <div class="card-body">
        <div class="info-grid">
          <div><div class="info-label">Country</div><div class="info-value"><?= htmlspecialchars($driver['country']) ?></div></div>
          <div><div class="info-label">Region</div><div class="info-value"><?= htmlspecialchars($driver['region']) ?></div></div>
          <div><div class="info-label">City</div><div class="info-value"><?= htmlspecialchars($driver['city']) ?></div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Vehicle Information</div>
      <div class="card-body">
        <div class="info-grid">
          <div><div class="info-label">Vehicle Model</div><div class="info-value"><?= htmlspecialchars($driver['vehicle_model']) ?></div></div>
          <div><div class="info-label">Fuel Type</div><div class="info-value"><span class="badge <?= $fuelBadge ?>"><?= htmlspecialchars($driver['fuel_type']) ?></span></div></div>
        </div>
      </div>
    </div>

  </div>
</div>
</body>
</html>
