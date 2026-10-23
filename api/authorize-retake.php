<?php
require_once '../config/db.php';
requireRole('lecturer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$attempt_id = (int)($_POST['attempt_id'] ?? 0);
$lid = (int)$_SESSION['user_id'];

if ($attempt_id <= 0) { http_response_code(400); exit; }

$check = $conn->prepare("
    SELECT a.id FROM attempts a
    JOIN exams e ON e.id = a.exam_id
    JOIN lecturer_units lu ON lu.unit_id = e.unit_id
    WHERE a.id = ? AND lu.lecturer_id = ?
");
$check->bind_param('ii', $attempt_id, $lid);
$check->execute();
if (!$check->get_result()->fetch_assoc()) { http_response_code(403); exit; }
$check->close();

$upd = $conn->prepare("UPDATE attempts SET retake_authorized=1, authorized_by=?, authorized_at=NOW() WHERE id=?");
$upd->bind_param('ii', $lid, $attempt_id);
$upd->execute();
$upd->close();

header('Content-Type: application/json');
echo json_encode(['status' => 'authorized']);