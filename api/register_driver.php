<?php
// api/register_driver.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Collect and sanitize inputs
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
$username     = sanitize($_POST['username']      ?? '');
$password     =           $_POST['password']     ?? '';
$confirmPass  =           $_POST['confirm_password'] ?? '';

// Validation
$errors = [];

if (empty($fullName))     $errors[] = 'Full name is required.';
if (empty($birthday))     $errors[] = 'Birthday is required.';
if (empty($gender))       $errors[] = 'Gender is required.';
if (empty($address))      $errors[] = 'Address is required.';
if (empty($country))      $errors[] = 'Country is required.';
if (empty($region))       $errors[] = 'Region is required.';
if (empty($city))         $errors[] = 'City is required.';
if (empty($licenseClass)) $errors[] = 'License class is required.';
if (empty($vehicleModel)) $errors[] = 'Vehicle model is required.';
if (empty($fuelType))     $errors[] = 'Fuel type is required.';
if (empty($username))     $errors[] = 'Username is required.';
if (strlen($username) < 4) $errors[] = 'Username must be at least 4 characters.';
if (empty($password))     $errors[] = 'Password is required.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirmPass) $errors[] = 'Passwords do not match.';

if (!in_array($gender, ['male', 'female', 'other']))
    $errors[] = 'Invalid gender selection.';

if (!in_array($licenseClass, ['A', 'B', 'C']))
    $errors[] = 'Invalid license class.';

// Validate birthday and age
if (!empty($birthday)) {
    $dateCheck = DateTime::createFromFormat('Y-m-d', $birthday);
    if (!$dateCheck || $dateCheck->format('Y-m-d') !== $birthday) {
        $errors[] = 'Invalid birthday date format.';
    } elseif (!validateAge($birthday)) {
        $errors[] = 'You must be at least ' . MIN_DRIVER_AGE . ' years old to register.';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$db = getDB();

// Check username uniqueness
$stmt = $db->prepare("SELECT id FROM drivers WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'errors' => ['That username is already taken. Please choose another.']]);
    exit;
}

// Hash password and insert
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $db->prepare("
        INSERT INTO drivers
            (full_name, birthday, gender, address, country, region, city, license_class, vehicle_model, fuel_type, username, password)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $fullName, $birthday, $gender, $address,
        $country, $region, $city,
        $licenseClass, $vehicleModel, $fuelType,
        $username, $hashedPassword
    ]);

    echo json_encode(['success' => true, 'message' => 'Registration successful!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Registration failed. Please try again.']]);
}

exit;
