<?php
require_once '../config/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$attempt_id = (int)($_POST['attempt_id'] ?? 0);
$type = ($_POST['type'] ?? '') === 'tab_switch' ? 'tab_switch' : 'fullscreen_exit';
if ($attempt_id <= 0) { http_response_code(400); exit; }

$student_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM attempts WHERE id=? AND student_id=? AND status='in_progress'");
$stmt->bind_param('ii', $attempt_id, $student_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) { http_response_code(403); exit; }
$stmt->close();

$stmt = $conn->prepare("INSERT INTO violations (attempt_id, violation_type) VALUES (?,?)");
$stmt->bind_param('is', $attempt_id, $type);
$stmt->execute();
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['status' => 'logged']);