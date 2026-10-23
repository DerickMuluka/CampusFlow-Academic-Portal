<?php
require_once '../config/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$attempt_id = (int)($_POST['attempt_id'] ?? 0);
$dataUrl = $_POST['image'] ?? '';

if ($attempt_id <= 0 || $dataUrl === '') { http_response_code(400); exit; }

$student_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM attempts WHERE id=? AND student_id=? AND status='in_progress'");
$stmt->bind_param('ii', $attempt_id, $student_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) { http_response_code(403); exit; }
$stmt->close();

$imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl));
if ($imageData === false) { http_response_code(400); exit; }

$filename = 'snap_' . $attempt_id . '_' . bin2hex(random_bytes(6)) . '.jpg';
if (!is_dir(SNAPSHOT_DIR)) { @mkdir(SNAPSHOT_DIR, 0755, true); }
file_put_contents(SNAPSHOT_DIR . $filename, $imageData);

$stmt = $conn->prepare("INSERT INTO snapshots (attempt_id, image_path) VALUES (?,?)");
$stmt->bind_param('is', $attempt_id, $filename);
$stmt->execute();
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['status' => 'saved']);