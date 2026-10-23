<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

$student = $conn->query("SELECT course_id, c.name AS course_name FROM users u LEFT JOIN courses c ON c.id=u.course_id WHERE u.id=$sid")->fetch_assoc();
$courseId = (int)($student['course_id'] ?? 0);

$tuts = $conn->query("
    SELECT * FROM tutorials
    WHERE course_id = $courseId OR course_id IS NULL
    ORDER BY course_id IS NULL, created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutorials</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Tutorials for <?= esc($student['course_name'] ?? 'Your Course') ?></h1>
    <p class="subtitle">Curated videos to support your learning.</p>

    <?php if (empty($tuts)): ?>
        <div class="empty-state"><p>No tutorials available for your course yet.</p></div>
    <?php else: ?>
    <div class="exam-grid">
    <?php foreach ($tuts as $t): ?>
        <div class="exam-card">
            <h3><?= esc($t['title']) ?></h3>
            <p><?= esc($t['description']) ?></p>
            <a class="btn-primary btn-block" href="<?= esc($t['video_url']) ?>" target="_blank" rel="noopener">Watch Tutorial</a>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>
</div>
</body>
</html>