<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

$units = $conn->query("
    SELECT un.*, c.name AS course_name,
           (SELECT COUNT(*) FROM unit_enrollments ue WHERE ue.unit_id=un.id) AS enrolled_count,
           (SELECT COUNT(*) FROM exams e WHERE e.unit_id=un.id) AS exam_count,
           (SELECT COUNT(*) FROM uploaded_files uf WHERE uf.unit_id=un.id) AS file_count
    FROM lecturer_units lu
    JOIN units un ON un.id=lu.unit_id
    JOIN courses c ON c.id=un.course_id
    WHERE lu.lecturer_id=$lid
    ORDER BY un.name
")->fetch_all(MYSQLI_ASSOC);

$stats = [
    'units' => count($units),
    'students' => (int)$conn->query("SELECT COUNT(DISTINCT student_id) c FROM unit_enrollments ue JOIN lecturer_units lu ON lu.unit_id=ue.unit_id WHERE lu.lecturer_id=$lid")->fetch_assoc()['c'],
    'exams' => (int)$conn->query("SELECT COUNT(*) c FROM exams e JOIN lecturer_units lu ON lu.unit_id=e.unit_id WHERE lu.lecturer_id=$lid")->fetch_assoc()['c'],
    'files' => (int)$conn->query("SELECT COUNT(*) c FROM uploaded_files uf JOIN lecturer_units lu ON lu.unit_id=uf.unit_id WHERE lu.lecturer_id=$lid")->fetch_assoc()['c'],
];  
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lecturer Dashboard — Rifkins Exam</title>
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
    <h1>Lecturer Dashboard</h1>
    <p class="subtitle">Your teaching units and student activity.</p>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-num"><?= $stats['units'] ?></span><span>Units Assigned</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['students'] ?></span><span>Enrolled Students</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['exams'] ?></span><span>Exams Created</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['files'] ?></span><span>Files Uploaded</span></div>
    </div>

    <h2>Quick Actions</h2>
    <div class="action-cards">
        <a class="action-card" href="enroll-students.php"><h3>Enroll Students</h3><p>Add students to your units so they can access materials.</p></a>
        <a class="action-card" href="exams.php"><h3>Create Exams</h3><p>Build timed, proctored assessments with auto-grading.</p></a>
        <a class="action-card" href="notes.php"><h3>Upload Notes</h3><p>Share lecture notes and study materials.</p></a>
        <a class="action-card" href="assignments.php"><h3>Assignments</h3><p>Post assignment briefs and files.</p></a>
        <a class="action-card" href="pastpapers.php"><h3>Past Papers</h3><p>Upload revision papers for your units.</p></a>
    </div>

    <h2 style="margin-top:32px;">My Units</h2>
    <?php if (empty($units)): ?>
        <div class="empty-state"><p>No units assigned yet. Contact the Principal.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Code</th><th>Unit</th><th>Course</th><th>Enrolled</th><th>Exams</th><th>Files</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($units as $u): ?>
        <tr>
            <td><strong><?= esc($u['code']) ?></strong></td>
            <td><?= esc($u['name']) ?></td>
            <td><?= esc($u['course_name']) ?></td>
            <td><?= (int)$u['enrolled_count'] ?></td>
            <td><?= (int)$u['exam_count'] ?></td>
            <td><?= (int)$u['file_count'] ?></td>
            <td>
                <a class="link-btn" href="enroll-students.php?unit=<?= $u['id'] ?>">Enroll</a>
                <a class="link-btn" href="exams.php?unit=<?= $u['id'] ?>">Exams</a>
                <a class="link-btn" href="notes.php?unit=<?= $u['id'] ?>">Notes</a>
                <a class="link-btn" href="assignments.php?unit=<?= $u['id'] ?>">Assignments</a>
                <a class="link-btn" href="pastpapers.php?unit=<?= $u['id'] ?>">Past Papers</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</main>
</div>
</body>
</html>