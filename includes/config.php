<?php
// includes/config.php
// Database configuration - update these values for your environment

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecodrive_db');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'EcoDrive');
define('SITE_TAGLINE', 'Driver & Fleet Administration Portal');
// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Minimum driver age
define('MIN_DRIVER_AGE', 24);
