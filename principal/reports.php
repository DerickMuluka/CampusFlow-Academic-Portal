<?php
require_once '../config/db.php';
requireRole('principal');

$byDept = $conn->query("
    SELECT d.name, COUNT(DISTINCT u.id) AS students,
           (SELECT COUNT(*) FROM attempts a JOIN users us ON us.id=a.student_id WHERE us.department_id=d.id AND a.status!='in_progress') AS attempts
    FROM departments d
    LEFT JOIN users u ON u.department_id=d.id AND u.role='student'
    GROUP BY d.id ORDER BY d.name
")->fetch_all(MYSQLI_ASSOC);

$topPerformers = $conn->query("
    SELECT u.full_name, AVG(a.score) AS avg_score, COUNT(a.id) AS attempts
    FROM attempts a JOIN users u ON u.id=a.student_id
    WHERE a.status!='in_progress'
    GROUP BY u.id HAVING attempts >= 1
    ORDER BY avg_score DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports — Principal</title>
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
    <h1>Institutional Reports</h1>
    <p class="subtitle">Enrollment and performance overview by department.</p>

    <h2>Department Summary</h2>
    <table class="data-table">
        <thead><tr><th>Department</th><th>Students</th><th>Completed Attempts</th></tr></thead>
        <tbody>
        <?php foreach ($byDept as $d): ?>
        <tr><td><?= esc($d['name']) ?></td><td><?= (int)$d['students'] ?></td><td><?= (int)$d['attempts'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 style="margin-top:30px;">Top Performers</h2>
    <table class="data-table">
        <thead><tr><th>Student</th><th>Average Score</th><th>Attempts</th></tr></thead>
        <tbody>
        <?php foreach ($topPerformers as $t): ?>
        <tr><td><?= esc($t['full_name']) ?></td><td><?= number_format((float)$t['avg_score'], 1) ?></td><td><?= (int)$t['attempts'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>