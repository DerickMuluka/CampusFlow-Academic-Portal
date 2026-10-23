<?php
require_once '../config/db.php';
requireRole('principal');

$stats = [
    'students'    => (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='student' AND is_active=1")->fetch_assoc()['c'],
    'lecturers'   => (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='lecturer' AND is_active=1")->fetch_assoc()['c'],
    'units'       => (int)$conn->query("SELECT COUNT(*) c FROM units")->fetch_assoc()['c'],
    'assignments' => (int)$conn->query("SELECT COUNT(*) c FROM lecturer_units")->fetch_assoc()['c'],
];
$unassigned = $conn->query("
    SELECT u.id, u.full_name, d.name AS dept
    FROM users u LEFT JOIN departments d ON d.id = u.department_id
    WHERE u.role = 'lecturer' AND u.is_active = 1
    AND u.id NOT IN (SELECT lecturer_id FROM lecturer_units)
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Principal Dashboard — Rifkins Exam</title>
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
    <h1>Principal Dashboard</h1>
    <p class="subtitle">Academic oversight and lecturer assignments.</p>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-num"><?= $stats['students'] ?></span><span>Active Students</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['lecturers'] ?></span><span>Active Lecturers</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['units'] ?></span><span>Units</span></div>
        <div class="stat-card"><span class="stat-num"><?= $stats['assignments'] ?></span><span>Lecturer Assignments</span></div>
    </div>

    <h2>Quick Actions</h2>
    <div class="action-cards">
        <a class="action-card" href="assign-lecturer.php">
            <h3>Assign Lecturers to Units</h3>
            <p>Control which lecturer teaches which unit.</p>
        </a>
        <a class="action-card" href="notes.php">
            <h3>Manage Unit Notes</h3>
            <p>Upload or edit notes across all units.</p>
        </a>
        <a class="action-card" href="reports.php">
            <h3>Institution Reports</h3>
            <p>Department summaries and top performers.</p>
        </a>
    </div>

    <h2 style="margin-top:32px;">Lecturers Without Unit Assignments</h2>
    <?php if (empty($unassigned)): ?>
        <div class="empty-state"><p>All active lecturers are assigned to units.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Department</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($unassigned as $u): ?>
            <tr>
                <td><?= esc($u['full_name']) ?></td>
                <td><?= esc($u['dept'] ?? '—') ?></td>
                <td><a class="link-btn" href="assign-lecturer.php?lecturer=<?= $u['id'] ?>">Assign Units</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</main>
</div>
</body>
</html>