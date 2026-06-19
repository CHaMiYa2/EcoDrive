<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$user = getCurrentUser();
$db   = getDB();

$totalDrivers = $db->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard — EcoDrive</title>
</head>
<body>
  <h2>Fleet Admin Dashboard</h2>
  <p>Welcome, <?= htmlspecialchars($user['name']) ?> | <a href="/logout.php">Logout</a></p>

  <p>Total Drivers: <strong><?= $totalDrivers ?></strong></p>

  <hr>

  <h3>Registered Drivers</h3>

  <form onsubmit="return false;">
    <label>Search: <input type="text" id="search-input" oninput="loadDrivers(1)" placeholder="Name, city, vehicle..."></label>
  </form>

  <br>
  <div id="driver-count"></div>
  <br>

  <table border="1" cellpadding="6" id="driver-table">
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
    <tbody id="driver-tbody">
      <tr><td colspan="13">Loading...</td></tr>
    </tbody>
  </table>

  <div id="pagination"></div>

  <hr>

  <!-- Edit Form (hidden by default) -->
  <div id="edit-section" style="display:none;">
    <h3>Edit Driver</h3>
    <p id="edit-msg" style="color:red;"></p>
    <form id="edit-form">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">

      <label>Full Name *<br><input type="text" name="full_name" id="edit-full_name" required></label><br><br>
      <label>Birthday *<br><input type="date" name="birthday" id="edit-birthday" required></label><br><br>
      <label>Gender *<br>
        <select name="gender" id="edit-gender">
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>
      </label><br><br>
      <label>Address *<br><textarea name="address" id="edit-address" rows="3" cols="40"></textarea></label><br><br>
      <label>Country<br><input type="text" name="country" id="edit-country"></label><br><br>
      <label>Region<br><input type="text" name="region" id="edit-region"></label><br><br>
      <label>City<br><input type="text" name="city" id="edit-city"></label><br><br>
      <label>License Class *<br>
        <select name="license_class" id="edit-license_class">
          <option value="A">Class A</option>
          <option value="B">Class B</option>
          <option value="C">Class C</option>
        </select>
      </label><br><br>
      <label>Vehicle Model *<br><input type="text" name="vehicle_model" id="edit-vehicle_model" required></label><br><br>
      <label>Fuel Type *<br>
        <select name="fuel_type" id="edit-fuel_type">
          <option value="Petrol">Petrol</option>
          <option value="Diesel">Diesel</option>
          <option value="Hybrid">Hybrid</option>
          <option value="Electric">Electric (EV)</option>
          <option value="LPG">LPG</option>
          <option value="CNG">CNG</option>
        </select>
      </label><br><br>

      <button type="submit">Save Changes</button>
      <button type="button" onclick="document.getElementById('edit-section').style.display='none'">Cancel</button>
    </form>
  </div>

  <script>
    let currentPage = 1;

    async function loadDrivers(page = 1) {
      currentPage = page;
      const search = document.getElementById('search-input').value.trim();
      const params = new URLSearchParams({ action: 'list', page, search });
      const res    = await fetch('/api/admin_drivers.php?' + params);
      const json   = await res.json();

      const tbody = document.getElementById('driver-tbody');
      document.getElementById('driver-count').textContent = json.total + ' driver(s) found';

      if (!json.drivers.length) {
        tbody.innerHTML = '<tr><td colspan="13">No drivers found.</td></tr>';
        document.getElementById('pagination').innerHTML = '';
        return;
      }

      tbody.innerHTML = json.drivers.map((d, i) => `
        <tr>
          <td>${((page - 1) * 10) + i + 1}</td>
          <td>${esc(d.full_name)}</td>
          <td>${esc(d.username)}</td>
          <td>${esc(d.birthday)}</td>
          <td>${esc(d.gender)}</td>
          <td>${esc(d.country)}</td>
          <td>${esc(d.region)}</td>
          <td>${esc(d.city)}</td>
          <td>${esc(d.license_class)}</td>
          <td>${esc(d.vehicle_model)}</td>
          <td>${esc(d.fuel_type)}</td>
          <td>${esc(d.created_at)}</td>
          <td>
            <button onclick="openEdit(${d.id})">Edit</button>
            <button onclick="deleteDriver(${d.id}, '${esc(d.full_name)}')">Delete</button>
          </td>
        </tr>
      `).join('');

      // Pagination
      let pg = '';
      for (let i = 1; i <= json.total_pages; i++) {
        pg += `<button onclick="loadDrivers(${i})" ${i === page ? 'disabled' : ''}>${i}</button> `;
      }
      document.getElementById('pagination').innerHTML = pg;
    }

    function esc(str) {
      return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    async function openEdit(id) {
      const res  = await fetch(`/api/admin_drivers.php?action=get&id=${id}`);
      const json = await res.json();
      if (!json.success) { alert('Failed to load driver.'); return; }

      const d = json.driver;
      document.getElementById('edit-id').value            = d.id;
      document.getElementById('edit-full_name').value     = d.full_name;
      document.getElementById('edit-birthday').value      = d.birthday;
      document.getElementById('edit-gender').value        = d.gender;
      document.getElementById('edit-address').value       = d.address;
      document.getElementById('edit-country').value       = d.country;
      document.getElementById('edit-region').value        = d.region;
      document.getElementById('edit-city').value          = d.city;
      document.getElementById('edit-license_class').value = d.license_class;
      document.getElementById('edit-vehicle_model').value = d.vehicle_model;
      document.getElementById('edit-fuel_type').value     = d.fuel_type;

      document.getElementById('edit-msg').textContent = '';
      document.getElementById('edit-section').style.display = 'block';
      document.getElementById('edit-section').scrollIntoView({ behavior: 'smooth' });
    }

    document.getElementById('edit-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      const data = new FormData(this);
      const res  = await fetch('/api/admin_drivers.php', { method: 'POST', body: data });
      const json = await res.json();
      if (json.success) {
        document.getElementById('edit-section').style.display = 'none';
        loadDrivers(currentPage);
      } else {
        document.getElementById('edit-msg').textContent = json.message || 'Update failed.';
      }
    });

    async function deleteDriver(id, name) {
      if (!confirm('Delete driver "' + name + '"?')) return;
      const data = new FormData();
      data.set('action', 'delete');
      data.set('id', id);
      const res  = await fetch('/api/admin_drivers.php', { method: 'POST', body: data });
      const json = await res.json();
      if (json.success) {
        loadDrivers(currentPage);
      } else {
        alert(json.message || 'Delete failed.');
      }
    }

    loadDrivers();
  </script>
</body>
</html>
