<?php
// api/admin_drivers.php
// Admin-only CRUD operations on driver records

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

startSecureSession();

// Must be logged in as admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');
$db     = getDB();

switch ($action) {

    // ── LIST / SEARCH ──────────────────────────────────────────────
    case 'list':
        $search    = sanitize($_GET['search'] ?? '');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $perPage   = 10;
        $offset    = ($page - 1) * $perPage;

        if ($search) {
            $like  = '%' . $search . '%';
            $total = $db->prepare("SELECT COUNT(*) FROM drivers WHERE full_name LIKE ? OR username LIKE ? OR city LIKE ? OR country LIKE ? OR vehicle_model LIKE ?");
            $total->execute([$like, $like, $like, $like, $like]);

            $stmt = $db->prepare("SELECT id, full_name, birthday, gender, country, region, city, license_class, vehicle_model, fuel_type, username, ip_address, created_at FROM drivers WHERE full_name LIKE ? OR username LIKE ? OR city LIKE ? OR country LIKE ? OR vehicle_model LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$like, $like, $like, $like, $like, $perPage, $offset]);
        } else {
            $total = $db->query("SELECT COUNT(*) FROM drivers");
            $stmt  = $db->prepare("SELECT id, full_name, birthday, gender, country, region, city, license_class, vehicle_model, fuel_type, username, ip_address, created_at FROM drivers ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$perPage, $offset]);
        }

        $totalRows  = (int)$total->fetchColumn();
        $drivers    = $stmt->fetchAll();
        $totalPages = (int)ceil($totalRows / $perPage);

        echo json_encode([
            'success'     => true,
            'drivers'     => $drivers,
            'total'       => $totalRows,
            'page'        => $page,
            'total_pages' => $totalPages,
        ]);
        break;

    // ── GET SINGLE DRIVER ──────────────────────────────────────────
    case 'get':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT id, full_name, birthday, gender, address, country, region, city, license_class, vehicle_model, fuel_type, username, ip_address, created_at FROM drivers WHERE id = ?");
        $stmt->execute([$id]);
        $driver = $stmt->fetch();

        if (!$driver) {
            echo json_encode(['success' => false, 'message' => 'Driver not found.']);
            break;
        }

        echo json_encode(['success' => true, 'driver' => $driver]);
        break;

    // ── UPDATE ─────────────────────────────────────────────────────
    case 'update':
        $id           = (int)($_POST['id'] ?? 0);
        $fullName     = sanitize($_POST['full_name']     ?? '');
        $birthday     = sanitize($_POST['birthday']      ?? '');
        $gender       = sanitize($_POST['gender']        ?? '');
        $address      = sanitize($_POST['address']       ?? '');
        $country      = sanitize($_POST['country']       ?? '');
        $region       = sanitize($_POST['region']        ?? '');
        $city         = sanitize($_POST['city']          ?? '');
        $licenseClass = sanitize($_POST['license_class'] ?? '');
        $vehicleModel = sanitize($_POST['vehicle_model'] ?? '');
        $fuelType     = sanitize($_POST['fuel_type']     ?? '');

        if (!$id || empty($fullName) || empty($birthday) || empty($gender) || empty($address)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            break;
        }

        if (!validateAge($birthday)) {
            echo json_encode(['success' => false, 'message' => 'Driver must be at least ' . MIN_DRIVER_AGE . ' years old.']);
            break;
        }

        if (!in_array($gender, ['male', 'female', 'other']) || !in_array($licenseClass, ['A', 'B', 'C'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid gender or license class.']);
            break;
        }

        $stmt = $db->prepare("
            UPDATE drivers SET
                full_name = ?, birthday = ?, gender = ?, address = ?,
                country = ?, region = ?, city = ?,
                license_class = ?, vehicle_model = ?, fuel_type = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullName, $birthday, $gender, $address, $country, $region, $city, $licenseClass, $vehicleModel, $fuelType, $id]);

        echo json_encode(['success' => true, 'message' => 'Driver updated successfully.']);
        break;

    // ── DELETE ─────────────────────────────────────────────────────
    case 'delete':
        $id   = (int)($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid driver ID.']);
            break;
        }

        $stmt = $db->prepare("DELETE FROM drivers WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Driver deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Driver not found.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

exit;
