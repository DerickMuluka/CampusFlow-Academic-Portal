<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

$units = $conn->query("SELECT un.* FROM lecturer_units lu JOIN units un ON un.id=lu.unit_id WHERE lu.lecturer_id=$lid ORDER BY un.name")->fetch_all(MYSQLI_ASSOC);
if (empty($units)) die('No units assigned.');

$unit_id = (int)($_GET['unit'] ?? $units[0]['id']);
if (!lecturerOwnsUnit($conn, $lid, $unit_id)) die('Access denied.');
$unit = $conn->query("SELECT * FROM units WHERE id=$unit_id")->fetch_assoc();

if (isset($_GET['delete'])) {
    $fid = (int)$_GET['delete'];
    $chk = $conn->query("SELECT file_path FROM uploaded_files WHERE id=$fid AND unit_id=$unit_id AND file_type='pastpaper'")->fetch_assoc();
    if ($chk) {
        @unlink(__DIR__ . '/../uploads/' . $chk['file_path']);
        $conn->query("DELETE FROM uploaded_files WHERE id=$fid");
    }
    redirect("pastpapers.php?unit=$unit_id");
}

$files = $conn->query("
    SELECT uf.*, u.full_name
    FROM uploaded_files uf
    JOIN users u ON u.id = uf.uploader_id
    WHERE uf.unit_id = $unit_id AND uf.file_type = 'pastpaper'
    ORDER BY uf.uploaded_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Past Papers — <?= esc($unit['name']) ?></title>
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
    <h1>Past Papers — <?= esc($unit['name']) ?></h1>
    <p class="subtitle">Unit code: <?= esc($unit['code']) ?></p>

    <form method="GET" class="card-form" style="max-width:420px;margin-bottom:24px;">
        <div class="form-group">
            <label>Select Unit</label>
            <select name="unit" onchange="this.form.submit()">
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $unit_id===$u['id']?'selected':'' ?>><?= esc($u['code'].' — '.$u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form method="POST" action="../api/upload-file.php" enctype="multipart/form-data" class="card-form">
        <input type="hidden" name="unit_id" value="<?= $unit_id ?>">
        <input type="hidden" name="file_type" value="pastpaper">
        <div class="form-group">
            <label>Paper Title</label>
            <input type="text" name="title" required placeholder="e.g. 2024 End of Semester Exam">
        </div>
        <div class="form-group">
            <label>Description / Year / Notes</label>
            <textarea name="description" rows="4" placeholder="Enter year, semester, exam body, or notes about this paper..."></textarea>
        </div>
        <div class="form-group">
            <label>Attach File (PDF, DOC, DOCX, TXT, JPG, PNG — max 10MB)</label>
            <input type="file" name="file" required>
        </div>
        <button type="submit" class="btn-primary">Upload Past Paper</button>
    </form>

    <h2 style="margin-top:30px;">Uploaded Past Papers (<?= count($files) ?>)</h2>
    <?php if (empty($files)): ?>
        <div class="empty-state"><p>No past papers uploaded yet.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Title</th><th>Notes</th><th>File</th><th>Uploaded By</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($files as $f): ?>
            <tr>
                <td><?= esc($f['title']) ?></td>
                <td><?= esc(substr($f['description'] ?? '', 0, 80)) ?><?= strlen($f['description'] ?? '') > 80 ? '…' : '' ?></td>
                <td><a class="link-btn" href="../uploads/<?= esc($f['file_path']) ?>" target="_blank"><?= esc($f['original_name']) ?></a></td>
                <td><?= esc($f['full_name']) ?></td>
                <td><?= esc($f['uploaded_at']) ?></td>
                <td>
                    <a class="link-btn" href="?unit=<?= $unit_id ?>&delete=<?= $f['id'] ?>" onclick="return confirm('Delete this past paper?')">Delete</a>
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