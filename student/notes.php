<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

$units = getStudentUnits($conn, $sid);
if (empty($units)) {
    $noUnits = true;
} else {
    $noUnits = false;
    $unitIds = implode(',', array_map('intval', array_column($units, 'id')));
    $files = $conn->query("
        SELECT uf.*, un.name AS unit_name, un.code AS unit_code, u.full_name AS uploader
        FROM uploaded_files uf
        JOIN units un ON un.id = uf.unit_id
        JOIN users u ON u.id = uf.uploader_id
        WHERE uf.unit_id IN ($unitIds) AND uf.file_type = 'note'
        ORDER BY uf.uploaded_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Notes — Rifkins Exam</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>My Unit Notes</h1>
    <p class="subtitle">Lecture notes shared by your lecturers for the units you are enrolled in.</p>

    <?php if ($noUnits): ?>
        <div class="empty-state"><p>You are not enrolled in any units yet. Ask your lecturer to enroll you.</p></div>
    <?php elseif (empty($files)): ?>
        <div class="empty-state"><p>No notes have been uploaded for your units yet.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Unit</th><th>Title</th><th>Description</th><th>File</th><th>Uploaded By</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php foreach ($files as $f): ?>
            <tr>
                <td><?= esc($f['unit_code'] . ' — ' . $f['unit_name']) ?></td>
                <td><?= esc($f['title']) ?></td>
                <td><?= esc(substr($f['description'] ?? '', 0, 100)) ?><?= strlen($f['description'] ?? '') > 100 ? '…' : '' ?></td>
                <td><a class="link-btn" href="../uploads/<?= esc($f['file_path']) ?>" target="_blank">Download (<?= esc($f['original_name']) ?>)</a></td>
                <td><?= esc($f['uploader']) ?></td>
                <td><?= esc($f['uploaded_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</main>
</div>
</body>
</html>