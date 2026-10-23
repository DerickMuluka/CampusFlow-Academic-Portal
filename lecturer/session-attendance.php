<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

$sessionId = (int)($_GET['session'] ?? 0);
$session = $conn->query("
    SELECT s.*, un.name AS unit_name, un.code AS unit_code, un.course_id
    FROM online_sessions s
    JOIN units un ON un.id = s.unit_id
    WHERE s.id = $sessionId AND s.lecturer_id = $lid
")->fetch_assoc();
if (!$session) die('Session not found or access denied.');

// Enrolled students + attendance status
$enrolled = $conn->query("
    SELECT u.id, u.full_name, u.admission_no,
           sa.joined_at, sa.status
    FROM unit_enrollments ue
    JOIN users u ON u.id = ue.student_id
    LEFT JOIN session_attendance sa ON sa.session_id = $sessionId AND sa.student_id = u.id
    WHERE ue.unit_id = {$session['unit_id']}
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);

$attended = 0; $absent = 0;
foreach ($enrolled as $e) { if ($e['joined_at']) $attended++; else $absent++; }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance — <?= esc($session['title']) ?></title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Attendance</h1>
    <p class="subtitle"><?= esc($session['title']) ?> · <?= esc($session['unit_code'].' '.$session['unit_name']) ?> · <?= esc($session['scheduled_at']) ?></p>

    <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card"><span class="stat-num"><?= count($enrolled) ?></span><span>Enrolled</span></div>
        <div class="stat-card"><span class="stat-num" style="color:#1e7e34;"><?= $attended ?></span><span>Attended</span></div>
        <div class="stat-card"><span class="stat-num" style="color:#c0392b;"><?= $absent ?></span><span>Absent</span></div>
        <div class="stat-card"><span class="stat-num"><?= count($enrolled) ? round($attended / count($enrolled) * 100, 1) : 0 ?>%</span><span>Rate</span></div>
    </div>

    <table class="data-table">
        <thead><tr><th>Student</th><th>Admission No</th><th>Status</th><th>Joined At</th></tr></thead>
        <tbody>
        <?php foreach ($enrolled as $e): ?>
        <tr>
            <td><?= esc($e['full_name']) ?></td>
            <td><?= esc($e['admission_no']) ?></td>
            <td><?= $e['joined_at'] ? '<span class="badge badge-green">Attended</span>' : '<span class="badge badge-grey">Absent</span>' ?></td>
            <td><?= esc($e['joined_at'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:24px;"><a class="link-btn" href="online-sessions.php">← Back to Sessions</a></p>
</main>
</div>
</body>
</html>