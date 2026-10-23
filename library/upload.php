<?php
require_once '../config/db.php';
requireRole('lecturer');
$uid = (int)$_SESSION['user_id'];

$msg = ''; $err = '';

$units = $conn->query("
    SELECT un.*, c.name AS course_name FROM lecturer_units lu
    JOIN units un ON un.id = lu.unit_id
    JOIN courses c ON c.id = un.course_id
    WHERE lu.lecturer_id = $uid ORDER BY un.name
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['resource_type'] ?? 'ebook';
    $unitId = (int)($_POST['unit_id'] ?? 0);
    $externalUrl = trim($_POST['external_url'] ?? '');

    if (!lecturerOwnsUnit($conn, $uid, $unitId)) { $err = 'You do not teach that unit.'; }
    elseif ($title === '') { $err = 'Title is required.'; }
    else {
        $courseId = (int)$conn->query("SELECT course_id FROM units WHERE id = $unitId")->fetch_assoc()['course_id'];
        $filePath = null;
        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            list($ok, $result) = handleUpload($_FILES['file'], ['pdf','doc','docx','ppt','pptx','txt','jpg','jpeg','png','zip'], 20971520);
            if ($ok) $filePath = $result;
            else $err = $result;
        }
        if ($err === '') {
            if ($filePath === null && $externalUrl === '') {
                $err = 'Provide either a file or a URL.';
            } else {
                $stmt = $conn->prepare("INSERT INTO library_resources (course_id, unit_id, title, description, resource_type, file_path, external_url, uploaded_by) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('iisssssi', $courseId, $unitId, $title, $description, $type, $filePath, $externalUrl, $uid);
                if ($stmt->execute()) {
                    logAction($conn, 'lecturer_added_resource', 'library', $conn->insert_id, $title);
                    $msg = 'Resource added to library.';
                } else {
                    $err = 'Save failed.';
                }
                $stmt->close();
            }
        }
    }
}

$myResources = $conn->query("
    SELECT r.*, un.code AS unit_code, un.name AS unit_name FROM library_resources r
    JOIN units un ON un.id = r.unit_id
    WHERE r.uploaded_by = $uid ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Resource — Lecturer</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Upload Library Resource</h1>
    <p class="subtitle">Add materials to the eLibrary for students in your units.</p>
    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card-form">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Unit</label>
                <select name="unit_id" required>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= esc($u['code'].' — '.$u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Type</label>
                <select name="resource_type">
                    <option value="ebook">eBook</option>
                    <option value="journal">Journal</option>
                    <option value="past_paper">Past Paper</option>
                    <option value="video">Video</option>
                    <option value="link">External Link</option>
                    <option value="note">Note</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>File (max 20MB)</label><input type="file" name="file"></div>
            <div class="form-group"><label>Or External URL</label><input type="url" name="external_url"></div>
        </div>
        <button type="submit" class="btn-primary">Add Resource</button>
    </form>

    <h2 style="margin-top:30px;">My Uploaded Resources (<?= count($myResources) ?>)</h2>
    <table class="data-table">
        <thead><tr><th>Title</th><th>Unit</th><th>Type</th><th>Link</th></tr></thead>
        <tbody>
        <?php foreach ($myResources as $r): ?>
        <tr>
            <td><?= esc($r['title']) ?></td>
            <td><?= esc($r['unit_code'].' — '.$r['unit_name']) ?></td>
            <td><?= esc($r['resource_type']) ?></td>
            <td>
                <?php if ($r['external_url']): ?><a class="link-btn" href="<?= esc($r['external_url']) ?>" target="_blank">Open</a><?php endif; ?>
                <?php if ($r['file_path']): ?><a class="link-btn" href="../uploads/<?= esc($r['file_path']) ?>" target="_blank">File</a><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>