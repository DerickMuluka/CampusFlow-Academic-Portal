<?php
require_once '../config/db.php';
requireRole(['lecturer','principal','admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$unit_id = (int)($_POST['unit_id'] ?? 0);
$file_type = $_POST['file_type'] ?? '';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!in_array($file_type, ['note','assignment','exam','pastpaper'], true)) { die('Invalid file type.'); }
if ($unit_id <= 0 || $title === '') { die('Unit and title required.'); }

// Permission check
$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
if ($role === 'lecturer' && !lecturerOwnsUnit($conn, $uid, $unit_id)) die('Access denied.');

if (empty($_FILES['file'])) die('No file uploaded.');

list($ok, $result) = handleUpload($_FILES['file']);
if (!$ok) die($result);

$stmt = $conn->prepare("INSERT INTO uploaded_files (unit_id, uploader_id, file_type, title, description, file_path, original_name, file_size) VALUES (?,?,?,?,?,?,?,?)");
$orig = $_FILES['file']['name'];
$size = (int)$_FILES['file']['size'];
$stmt->bind_param('iisssssi', $unit_id, $uid, $file_type, $title, $description, $result, $orig, $size);
$stmt->execute();
$stmt->close();

$back = $_SERVER['HTTP_REFERER'] ?? '../lecturer/dashboard.php';
header("Location: $back");
exit;
?>