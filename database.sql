CREATE DATABASE IF NOT EXISTS ecodrive_db;
USE ecodrive_db;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    birthday DATE NOT NULL,
    gender ENUM('male','female','other') NOT NULL,
    address TEXT NOT NULL,
    country VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    license_class ENUM('A','B','C') NOT NULL,
    vehicle_model VARCHAR(150) NOT NULL,
    fuel_type VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin (password: admin123)
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
