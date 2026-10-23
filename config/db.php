<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ---- LOCAL XAMPP ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rifkins_exam');

// ---- INFINITYFREE (uncomment when deploying) ----
// define('DB_HOST', 'sqlXXX.infinityfree.com');
// define('DB_USER', 'if0_XXXXXXXX');
// define('DB_PASS', 'your_account_password');
// define('DB_NAME', 'if0_XXXXXXXX_rifkins_exam');

define('SNAPSHOT_DIR', __DIR__ . '/../snapshots/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("DB failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

// ---- SEED ADMIN (only if none exists) ----
$r = $conn->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc();
if ((int)$r['c'] === 0) {
    $hash = password_hash('Admin@2026', PASSWORD_DEFAULT);
    $name = 'System Administrator';
    $email = 'admin@rifkinscollege.com';
    $role = 'admin';
    $staff = 'ADM-001';
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, staff_no) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss', $name, $email, $hash, $role, $staff);
    $stmt->execute();
    $stmt->close();
}

// ---- SEED PRINCIPAL (only if none exists) ----
$r = $conn->query("SELECT COUNT(*) c FROM users WHERE role='principal'")->fetch_assoc();
if ((int)$r['c'] === 0) {
    $hash = password_hash('Principal@2026', PASSWORD_DEFAULT);
    $name = 'College Principal';
    $email = 'principal@rifkinscollege.com';
    $role = 'principal';
    $staff = 'PRN-001';
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, staff_no) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss', $name, $email, $hash, $role, $staff);
    $stmt->execute();
    $stmt->close();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
?>