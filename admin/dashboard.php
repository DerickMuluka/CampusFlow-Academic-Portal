<?php
require_once '../config/db.php';
requireRole('admin');

$stats = [
    'students'    => (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='student' AND is_active=1")->fetch_assoc()['c'],
    'lecturers'   => (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='lecturer' AND is_active=1")->fetch_assoc()['c'],
    'exams'       => (int)$conn->query("SELECT COUNT(*) c FROM exams WHERE is_active=1")->fetch_assoc()['c'],
    'attempts'    => (int)$conn->query("SELECT COUNT(*) c FROM attempts WHERE status!='in_progress'")->fetch_assoc()['c'],
    'departments' => (int)$conn->query("SELECT COUNT(*) c FROM departments")->fetch_assoc()['c'],
    'courses'     => (int)$conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c'],
    'units'       => (int)$conn->query("SELECT COUNT(*) c FROM units")->fetch_assoc()['c'],
];
$recent = $conn->query("
    SELECT a.score, a.submitted_at, u.full_name, e.title
    FROM attempts a
    JOIN users u ON u.id=a.student_id
    JOIN exams e ON e.id=a.exam_id
    WHERE a.status!='in_progress'
    ORDER BY a.submitted_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Rifkins Exam</title>
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
    <h1>Institution Overview</h1>
    <p class="subtitle">Rifkins College · TVETA/PRIVATE/TVC/0005/2022</p>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-num"><?= $stats['students'] ?></span><span>Active Students</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['lecturers'] ?></span><span>Lecturers</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['departments'] ?></span><span>Departments</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['courses'] ?></span><span>Courses</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['units'] ?></span><span>Units</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['exams'] ?></span><span>Active Exams</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['attempts'] ?></span><span>Submissions</span></div>
    </div>

    <h2>Quick Actions</h2>
    <div class="action-cards">
        <a class="action-card" href="users.php">
            <h3>Create User Account</h3>
            <p>Add students, lecturers, principals, or administrators.</p>
        </a>
        <a class="action-card" href="departments.php">
            <h3>Manage Departments</h3>
            <p>Create and maintain academic departments.</p>
        </a>
        <a class="action-card" href="courses.php">
            <h3>Manage Courses</h3>
            <p>Add or remove courses under each department.</p>
        </a>
        <a class="action-card" href="units.php">
            <h3>Manage Units</h3>
            <p>Units are the subjects within courses.</p>
        </a>
        <a class="action-card" href="audit.php">
            <h3>Audit Log</h3>
            <p>Review all exam submissions and proctoring flags.</p>
        </a>
    </div>

    <h2 style="margin-top:32px;">Recent Submissions</h2>
    <?php if (empty($recent)): ?>
        <div class="empty-state"><p>No exam submissions yet.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Student</th><th>Exam</th><th>Score</th><th>Submitted</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?= esc($r['full_name']) ?></td>
                <td><?= esc($r['title']) ?></td>
                <td><?= (int)$r['score'] ?></td>
                <td><?= esc($r['submitted_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</main>
</div>
</body>
</html>