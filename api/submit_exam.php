<?php
require_once '../config/db.php';
requireRole('student');

$attempt_id = (int)($_POST['attempt_id'] ?? $_GET['attempt_id'] ?? 0);
$exam_id    = (int)($_POST['exam_id'] ?? $_GET['exam_id'] ?? 0);
$answers    = $_POST['answers'] ?? [];
$student_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM attempts WHERE id=? AND student_id=? AND status='in_progress'");
$stmt->bind_param('ii', $attempt_id, $student_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) { redirect("../student/result-detail.php?attempt_id=$attempt_id"); }
$stmt->close();

$qs = $conn->query("SELECT id, correct_answer, marks FROM questions WHERE exam_id=$exam_id")->fetch_all(MYSQLI_ASSOC);
$score = 0;

foreach ($qs as $q) {
    $sel = isset($answers[$q['id']]) ? strtoupper($answers[$q['id']]) : null;
    $is_correct = ($sel !== null && $sel === $q['correct_answer']) ? 1 : 0;
    if ($is_correct) $score += (int)$q['marks'];

    $stmt = $conn->prepare("INSERT INTO answers (attempt_id, question_id, selected_option, is_correct) VALUES (?,?,?,?)");
    $stmt->bind_param('iisi', $attempt_id, $q['id'], $sel, $is_correct);
    $stmt->execute();
    $stmt->close();
}

$status = isset($_GET['auto']) ? 'timed_out' : 'submitted';
$stmt = $conn->prepare("UPDATE attempts SET submitted_at=NOW(), score=?, status=? WHERE id=?");
$stmt->bind_param('isi', $score, $status, $attempt_id);
$stmt->execute();
$stmt->close();

redirect("../student/result-detail.php?attempt_id=$attempt_id");
?>