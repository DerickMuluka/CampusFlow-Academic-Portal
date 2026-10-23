<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

$student = $conn->query("
    SELECT u.*, c.name AS course_name
    FROM users u LEFT JOIN courses c ON c.id = u.course_id
    WHERE u.id = $sid
")->fetch_assoc();

$units = getStudentUnits($conn, $sid);
$course_id = (int)($student['course_id'] ?? 0);

$exams = [];
$files = [];
$tutorials = [];
$assignmentCount = 0;

if (!empty($units)) {
    $unitIds = implode(',', array_map('intval', array_column($units, 'id')));

    $exams = $conn->query("
        SELECT e.*, un.name AS unit_name, un.code AS unit_code,
               (SELECT COUNT(*) FROM attempts a WHERE a.exam_id=e.id AND a.student_id=$sid AND a.status!='in_progress') AS attempt_count,
               (SELECT MAX(score) FROM attempts a WHERE a.exam_id=e.id AND a.student_id=$sid) AS best_score,
               (SELECT retake_authorized FROM attempts a WHERE a.exam_id=e.id AND a.student_id=$sid ORDER BY id DESC LIMIT 1) AS last_retake
        FROM exams e
        JOIN units un ON un.id = e.unit_id
        WHERE e.unit_id IN ($unitIds) AND e.is_active = 1
        ORDER BY un.name
    ")->fetch_all(MYSQLI_ASSOC);

    $files = $conn->query("
        SELECT uf.*, un.name AS unit_name
        FROM uploaded_files uf
        JOIN units un ON un.id = uf.unit_id
        WHERE uf.unit_id IN ($unitIds) AND uf.file_type IN ('note','assignment')
        ORDER BY uf.uploaded_at DESC LIMIT 8
    ")->fetch_all(MYSQLI_ASSOC);

    $assignmentCount = (int)$conn->query("
        SELECT COUNT(*) c FROM uploaded_files
        WHERE unit_id IN ($unitIds) AND file_type = 'assignment'
    ")->fetch_assoc()['c'];
}

if ($course_id > 0) {
    $tutorials = $conn->query("
        SELECT * FROM tutorials
        WHERE course_id = $course_id OR course_id IS NULL
        ORDER BY course_id IS NULL, created_at DESC
        LIMIT 4
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard — Rifkins Exam</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>My Learning Dashboard</h1>
    <p class="subtitle">
        <?= esc($student['course_name'] ?? 'No course assigned') ?>
        <?php if (!empty($student['admission_no'])): ?> · <?= esc($student['admission_no']) ?><?php endif; ?>
    </p>

    <?php if (empty($units)): ?>
        <div class="empty-state">
            <p><strong>You are not enrolled in any units yet.</strong></p>
            <p>Your lecturer will enroll you into the units you are taking. Once enrolled, you will see exams, notes, and assignments here.</p>
        </div>
    <?php else: ?>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-num"><?= count($units) ?></span><span>Enrolled Units</span></div>
        <div class="stat-card"><span class="stat-num"><?= count($exams) ?></span><span>Available Exams</span></div>
        <div class="stat-card"><span class="stat-num"><?= $assignmentCount ?></span><span>Assignments</span></div>
        <div class="stat-card"><span class="stat-num"><?= count($tutorials) ?></span><span>Tutorials</span></div>
    </div>

    <h2>My Units</h2>
    <table class="data-table">
        <thead><tr><th>Code</th><th>Unit Name</th><th>Course</th></tr></thead>
        <tbody>
        <?php foreach ($units as $u): ?>
            <tr>
                <td><strong><?= esc($u['code']) ?></strong></td>
                <td><?= esc($u['name']) ?></td>
                <td><?= esc($u['course_name']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 style="margin-top:32px;">Available Exams</h2>
    <?php if (empty($exams)): ?>
        <div class="empty-state"><p>No exams available for your units yet.</p></div>
    <?php else: ?>
    <div class="exam-grid">
    <?php foreach ($exams as $e):
        $attempts = (int)$e['attempt_count'];
        $canRetake = $attempts > 0 && (int)$e['last_retake'] === 1;
        $canStart = ($attempts === 0) || $canRetake;
    ?>
        <div class="exam-card">
            <h3><?= esc($e['title']) ?></h3>
            <p class="exam-code"><?= esc($e['unit_code']) ?> — <?= esc($e['unit_name']) ?></p>
            <ul class="exam-meta-list">
                <li><strong><?= (int)$e['duration_minutes'] ?></strong> minutes · Pass: <?= (int)$e['pass_mark'] ?>/<?= (int)$e['total_marks'] ?></li>
                <?php if ($attempts): ?><li>Best score: <strong><?= (int)$e['best_score'] ?></strong></li><?php endif; ?>
            </ul>
            <?php if ($canStart): ?>
                <a class="btn-primary btn-block" href="take_exam.php?exam_id=<?= $e['id'] ?>"><?= $attempts ? 'Retake Exam' : 'Start Exam' ?></a>
            <?php else: ?>
                <p class="alert-warn">Retake requires lecturer authorization.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <h2 style="margin-top:32px;">Recent Notes & Assignments</h2>
    <?php if (empty($files)): ?>
        <div class="empty-state"><p>No files uploaded for your units yet.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Type</th><th>Title</th><th>Unit</th><th>Download</th></tr></thead>
        <tbody>
        <?php foreach ($files as $f): ?>
        <tr>
            <td><span class="badge badge-blue"><?= esc($f['file_type']) ?></span></td>
            <td><?= esc($f['title']) ?></td>
            <td><?= esc($f['unit_name']) ?></td>
            <td><a class="link-btn" href="../uploads/<?= esc($f['file_path']) ?>" target="_blank">Open</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h2 style="margin-top:32px;">Tutorials for You</h2>
    <?php if (empty($tutorials)): ?>
        <div class="empty-state"><p>No tutorials available yet.</p></div>
    <?php else: ?>
    <div class="exam-grid">
    <?php foreach ($tutorials as $t): ?>
        <div class="exam-card">
            <h3><?= esc($t['title']) ?></h3>
            <p><?= esc($t['description']) ?></p>
            <a class="btn-primary btn-block" href="<?= esc($t['video_url']) ?>" target="_blank" rel="noopener">Watch Tutorial</a>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</main>
</div>
</body>
</html>