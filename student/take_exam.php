<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

$exam_id = (int)($_GET['exam_id'] ?? 0);
$exam = $conn->query("
    SELECT e.*, un.name AS unit_name, un.course_id
    FROM exams e JOIN units un ON un.id=e.unit_id
    WHERE e.id=$exam_id AND e.is_active=1
")->fetch_assoc();
if (!$exam) die('Exam not found or inactive.');

// Check access
if (!canTakeExam($conn, $sid, $exam_id)) {
    die('You have already taken this exam. Retake requires lecturer authorization.');
}

// In-progress attempt or create
$stmt = $conn->prepare("SELECT id, started_at FROM attempts WHERE student_id=? AND exam_id=? AND status='in_progress' ORDER BY id DESC LIMIT 1");
$stmt->bind_param('ii', $sid, $exam_id);
$stmt->execute();
$att = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($att) {
    $attempt_id = (int)$att['id'];
    $started_at = $att['started_at'];
} else {
    $stmt = $conn->prepare("INSERT INTO attempts (student_id, exam_id, started_at, status) VALUES (?,?,NOW(),'in_progress')");
    $stmt->bind_param('ii', $sid, $exam_id);
    $stmt->execute();
    $attempt_id = $conn->insert_id;
    $stmt->close();
    $started_at = date('Y-m-d H:i:s');
}

$questions = $conn->query("SELECT * FROM questions WHERE exam_id=$exam_id ORDER BY id")->fetch_all(MYSQLI_ASSOC);
if (empty($questions)) die('This exam has no questions yet.');

$elapsed = time() - strtotime($started_at);
$remaining = max(0, ($exam['duration_minutes'] * 60) - $elapsed);
if ($remaining <= 0) {
    redirect("../api/submit_exam.php?auto=1&attempt_id=$attempt_id&exam_id=$exam_id");
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($exam['title']) ?></title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/exam.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body class="exam-body">

<div class="proctor-bar">
    <div class="proctor-info">
        <span class="rec-dot"></span> Proctoring Active
        <video id="camPreview" autoplay muted playsinline></video>
    </div>
    <div class="timer-box" id="timerBox"><span id="timerDisplay">--:--</span></div>
</div>

<div class="exam-container">
    <h1><?= esc($exam['title']) ?></h1>
    <p class="exam-meta"><?= esc($exam['unit_name']) ?> · <?= count($questions) ?> questions · <?= $exam['duration_minutes'] ?> minutes</p>

    <form id="examForm" method="POST" action="../api/submit_exam.php">
        <input type="hidden" name="attempt_id" value="<?= $attempt_id ?>">
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        <?php foreach ($questions as $i => $q): ?>
        <div class="question-card">
            <div class="q-header">
                <span class="q-num">Question <?= $i+1 ?> of <?= count($questions) ?></span>
                <span class="q-marks"><?= $q['marks'] ?> mark<?= $q['marks']>1?'s':'' ?></span>
            </div>
            <p class="q-text"><?= esc($q['question_text']) ?></p>
            <div class="options">
            <?php foreach (['A','B','C','D'] as $o): ?>
                <label class="option-label">
                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $o ?>">
                    <span class="option-badge"><?= $o ?></span>
                    <span class="option-text"><?= esc($q['option_'.strtolower($o)]) ?></span>
                </label>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="submit-area">
            <p class="warning-text" id="warningText">Do not switch tabs or exit fullscreen. Violations are logged.</p>
            <button type="submit" class="btn-submit" id="submitBtn">Submit Exam</button>
        </div>
    </form>
</div>

<script>
const ATTEMPT_ID = <?= $attempt_id ?>;
const REMAINING = <?= $remaining ?>;
</script>
<script src="../assets/js/proctor.js"></script>
</body>
</html>