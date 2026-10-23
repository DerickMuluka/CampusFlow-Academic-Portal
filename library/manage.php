<?php
require_once '../config/db.php';
requireRole(['admin','principal']);
$uid = (int)$_SESSION['user_id'];

$msg = ''; $err = '';

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['resource_type'] ?? 'ebook';
    $courseId = (int)($_POST['course_id'] ?? 0) ?: null;
    $unitId = (int)($_POST['unit_id'] ?? 0) ?: null;
    $externalUrl = trim($_POST['external_url'] ?? '');

    if ($title === '') {
        $err = 'Title is required.';
    } else {
        $filePath = null;
        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            list($ok, $result) = handleUpload($_FILES['file'], ['pdf','doc','docx','ppt','pptx','txt','jpg','jpeg','png','zip'], 20971520);
            if ($ok) $filePath = $result;
            else { $err = $result; }
        }
        if ($err === '') {
            if ($filePath === null && $externalUrl === '') {
                $err = 'Provide either a file upload or an external URL.';
            } else {
                $stmt = $conn->prepare("INSERT INTO library_resources (course_id, unit_id, title, description, resource_type, file_path, external_url, uploaded_by) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('iisssss i' === 'iisssss i' ? 'iisssssi' : 'iisssssi', $courseId, $unitId, $title, $description, $type, $filePath, $externalUrl, $uid);
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO library_resources (course_id, unit_id, title, description, resource_type, file_path, external_url, uploaded_by) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('iisssssi', $courseId, $unitId, $title, $description, $type, $filePath, $externalUrl, $uid);
                if ($stmt->execute()) {
                    logAction($conn, 'created_library_resource', 'library', $conn->insert_id, "Added: $title");
                    $msg = 'Resource added.';
                } else {
                    $err = 'Could not save resource.';
                }
                $stmt->close();
            }
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $rid = (int)$_GET['delete'];
    $chk = $conn->query("SELECT file_path FROM library_resources WHERE id = $rid")->fetch_assoc();
    if ($chk) {
        if ($chk['file_path']) @unlink(__DIR__ . '/../uploads/' . $chk['file_path']);
        $conn->query("DELETE FROM library_resources WHERE id = $rid");
        logAction($conn, 'deleted_library_resource', 'library', $rid);
    }
    redirect('manage.php');
}

$courses = $conn->query("SELECT c.*, d.name AS dept FROM courses c JOIN departments d ON d.id = c.department_id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
$units = $conn->query("SELECT un.*, c.name AS course_name FROM units un JOIN courses c ON c.id = un.course_id ORDER BY c.name, un.code")->fetch_all(MYSQLI_ASSOC);
$resources = $conn->query("
    SELECT r.*, c.name AS course_name, un.code AS unit_code, un.name AS unit_name
    FROM library_resources r
    LEFT JOIN courses c ON c.id = r.course_id
    LEFT JOIN units un ON un.id = r.unit_id
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Library — Rifkins College</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Manage eLibrary</h1>
    <p class="subtitle">Upload books, journals, videos, or links for any course or unit.</p>
    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card-form">
        <input type="hidden" name="action" value="create">
        <h2>Add New Resource</h2>
        <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
        <div class="form-row">
            <div class="form-group">
                <label>Resource Type</label>
                <select name="resource_type">
                    <option value="ebook">eBook</option>
                    <option value="journal">Journal</option>
                    <option value="past_paper">Past Paper</option>
                    <option value="video">Video</option>
                    <option value="link">External Link</option>
                    <option value="note">Note</option>
                </select>
            </div>
            <div class="form-group">
                <label>Course (optional)</label>
                <select name="course_id">
                    <option value="">— General —</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Unit (optional)</label>
            <select name="unit_id">
                <option value="">— None —</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['course_name'].' · '.$u['code'].' '.$u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Upload File (max 20MB)</label><input type="file" name="file"></div>
            <div class="form-group"><label>Or External URL</label><input type="url" name="external_url" placeholder="https://..."></div>
        </div>
        <button type="submit" class="btn-primary">Add Resource</button>
    </form>

    <h2 style="margin-top:30px;">All Resources (<?= count($resources) ?>)</h2>
    <table class="data-table">
        <thead><tr><th>Title</th><th>Type</th><th>Course</th><th>Unit</th><th>Link</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($resources as $r): ?>
        <tr>
            <td><?= esc($r['title']) ?></td>
            <td><span class="badge badge-blue"><?= esc($r['resource_type']) ?></span></td>
            <td><?= esc($r['course_name'] ?? '—') ?></td>
            <td><?= esc($r['unit_code'] ?? '—') ?></td>
            <td>
                <?php if ($r['external_url']): ?><a class="link-btn" href="<?= esc($r['external_url']) ?>" target="_blank">Open</a><?php endif; ?>
                <?php if ($r['file_path']): ?><a class="link-btn" href="../uploads/<?= esc($r['file_path']) ?>" target="_blank">File</a><?php endif; ?>
            </td>
            <td><a class="link-btn" href="?delete=<?= $r['id'] ?>" onclick="return confirm('Delete?')">Delete</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>