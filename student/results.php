<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

$rows = $conn->query("
    SELECT a.id, a.score, a.status, a.submitted_at, a.retake_authorized,
           e.title, e.total_marks, e.pass_mark, un.name AS unit_name
    FROM attempts a
    JOIN exams e ON e.id=a.exam_id
    JOIN units un ON un.id=e.unit_id
    WHERE a.student_id=$sid AND a.status!='in_progress'
    ORDER BY a.submitted_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Results</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/results.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>My Exam Results</h1>
    <p class="subtitle">Click any result to see your answers and the correct answers.</p>

    <?php if (empty($rows)): ?>
        <div class="empty-state"><p>You have not completed any exams yet.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Unit</th><th>Exam</th><th>Score</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            $passed = (int)$r['score'] >= (int)$r['pass_mark'];
        ?>
        <tr>
            <td><?= esc($r['unit_name']) ?></td>
            <td><?= esc($r['title']) ?></td>
            <td>
                <span class="badge <?= $passed ? 'badge-green' : 'badge-red' ?>">
                    <?= (int)$r['score'] ?> / <?= (int)$r['total_marks'] ?>
                </span>
            </td>
            <td><?= $passed ? 'Pass' : 'Fail' ?></td>
            <td><?= esc($r['submitted_at']) ?></td>
            <td><a class="link-btn" href="result-detail.php?attempt_id=<?= $r['id'] ?>">View Answers</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</main>
</div>
</body>
</html>