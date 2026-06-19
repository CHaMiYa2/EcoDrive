<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
requireDriver();

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM drivers WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$driver = $stmt->fetch();

$age = (new DateTime($driver['birthday']))->diff(new DateTime())->y;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Dashboard — EcoDrive</title>
</head>
<body>
  <h2>Driver Dashboard</h2>
  <p>Welcome, <?= htmlspecialchars($driver['full_name']) ?> | <a href="/logout.php">Logout</a></p>

  <hr>

  <h3>Personal Information</h3>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr><th>Full Name</th>     <td><?= htmlspecialchars($driver['full_name']) ?></td></tr>
    <tr><th>Birthday</th>      <td><?= htmlspecialchars($driver['birthday']) ?></td></tr>
    <tr><th>Age</th>           <td><?= $age ?></td></tr>
    <tr><th>Gender</th>        <td><?= htmlspecialchars($driver['gender']) ?></td></tr>
    <tr><th>Address</th>       <td><?= nl2br(htmlspecialchars($driver['address'])) ?></td></tr>
    <tr><th>License Class</th> <td><?= htmlspecialchars($driver['license_class']) ?></td></tr>
  </table>

  <h3>Location</h3>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr><th>Country</th> <td><?= htmlspecialchars($driver['country']) ?></td></tr>
    <tr><th>Region</th>  <td><?= htmlspecialchars($driver['region']) ?></td></tr>
    <tr><th>City</th>    <td><?= htmlspecialchars($driver['city']) ?></td></tr>
  </table>

  <h3>Vehicle Information</h3>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr><th>Vehicle Model</th> <td><?= htmlspecialchars($driver['vehicle_model']) ?></td></tr>
    <tr><th>Fuel Type</th>     <td><?= htmlspecialchars($driver['fuel_type']) ?></td></tr>
  </table>

  <br>
  <p><small>Registered on: <?= htmlspecialchars($driver['created_at']) ?></small></p>
</body>
</html>
