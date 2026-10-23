<?php
require_once '../config/db.php';
requireRole('student');

$attempt_id = (int)($_GET['attempt_id'] ?? 0);
$student_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT a.*, e.title, e.total_marks, e.pass_mark, un.name AS unit_name
    FROM attempts a
    JOIN exams e ON e.id = a.exam_id
    JOIN units un ON un.id = e.unit_id
    WHERE a.id = ? AND a.student_id = ?
");
$stmt->bind_param('ii', $attempt_id, $student_id);
$stmt->execute();
$att = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$att) die('Result not found.');

$qStmt = $conn->prepare("
    SELECT q.*, ans.selected_option, ans.is_correct
    FROM questions q
    LEFT JOIN answers ans ON ans.question_id = q.id AND ans.attempt_id = ?
    WHERE q.exam_id = ?
    ORDER BY q.id
");
$qStmt->bind_param('ii', $attempt_id, $att['exam_id']);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$qStmt->close();

$percent = $att['total_marks'] > 0 ? round(((int)$att['score'] / (int)$att['total_marks']) * 100, 1) : 0;
$passed = (int)$att['score'] >= (int)$att['pass_mark'];
$letter = $percent >= 80 ? 'A' : ($percent >= 70 ? 'B' : ($percent >= 60 ? 'C' : ($percent >= 50 ? 'D' : ($percent >= 40 ? 'E' : 'F'))));

// Count correct/incorrect/skipped
$correctCount = 0; $wrongCount = 0; $skippedCount = 0;
foreach ($questions as $q) {
    if ($q['selected_option'] === null) $skippedCount++;
    elseif ((int)$q['is_correct'] === 1) $correctCount++;
    else $wrongCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Result — <?= esc($att['title']) ?></title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/results.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="result-hero <?= $passed ? 'pass' : 'fail' ?>">
        <h1><?= $passed ? 'PASSED' : 'FAILED' ?></h1>
        <p class="score-big"><?= (int)$att['score'] ?> / <?= (int)$att['total_marks'] ?></p>
        <p style="font-size:1.4rem;font-weight:700;"><?= $percent ?>% — Grade <?= $letter ?></p>
        <p><?= esc($att['title']) ?> · <?= esc($att['unit_name']) ?></p>
    </div>

    <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card"><span class="stat-num" style="color:#1e7e34;"><?= $correctCount ?></span><span>Correct</span></div>
        <div class="stat-card"><span class="stat-num" style="color:#c0392b;"><?= $wrongCount ?></span><span>Wrong</span></div>
        <div class="stat-card"><span class="stat-num" style="color:#6c757d;"><?= $skippedCount ?></span><span>Skipped</span></div>
        <div class="stat-card"><span class="stat-num"><?= count($questions) ?></span><span>Total</span></div>
    </div>

    <h2>Answer Review</h2>
    <p class="subtitle">Review each question below. Correct answers are shown in green.</p>

    <?php foreach ($questions as $i => $q):
        $isCorrect = (int)$q['is_correct'] === 1;
        $isSkipped = $q['selected_option'] === null;
        $cardClass = $isSkipped ? 'review-skipped' : ($isCorrect ? 'review-correct' : 'review-wrong');
    ?>
    <div class="review-card <?= $cardClass ?>">
        <div class="review-header">
            <span>Q<?= $i+1 ?> · <?= (int)$q['marks'] ?> mark<?= $q['marks']>1?'s':'' ?></span>
            <span class="review-status">
                <?php if ($isSkipped): ?>
                    — Not answered
                <?php elseif ($isCorrect): ?>
                    ✓ Correct
                <?php else: ?>
                    ✗ Incorrect
                <?php endif; ?>
            </span>
        </div>
        <p class="review-text"><?= esc($q['question_text']) ?></p>
        <div class="review-options">
            <?php foreach (['A','B','C','D'] as $opt):
                $optText = $q['option_'.strtolower($opt)];
                $isSelected = ($q['selected_option'] === $opt);
                $isAnswerKey = ($q['correct_answer'] === $opt);
                $cls = '';
                if ($isAnswerKey) $cls = 'opt-correct';
                elseif ($isSelected && !$isAnswerKey) $cls = 'opt-wrong';
            ?>
            <div class="review-option <?= $cls ?>">
                <span class="option-badge"><?= $opt ?></span>
                <span><?= esc($optText) ?></span>
                <?php if ($isAnswerKey): ?>
                    <span class="opt-tag">✓ Correct answer<?= $isSelected ? ' — your answer' : '' ?></span>
                <?php elseif ($isSelected): ?>
                    <span class="opt-tag opt-tag-wrong">Your answer</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($isSkipped): ?>
            <p class="missed-note">You did not answer this question. Correct answer was <strong><?= esc($q['correct_answer']) ?></strong>.</p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <p style="margin-top:30px;">
        <a class="btn-primary" href="results.php">Back to My Results</a>
        <a class="link-btn" href="dashboard.php" style="margin-left:16px;">Dashboard</a>
    </p>
</main>
</div>
</body>
</html>