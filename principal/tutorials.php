<?php
require_once '../config/db.php';
requireRole('principal');
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'create') {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $url = trim($_POST['video_url'] ?? '');
        $courseId = (int)($_POST['course_id'] ?? 0) ?: null;
        if ($title && $url) {
            $stmt = $conn->prepare("INSERT INTO tutorials (title, description, video_url, course_id, created_by) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssii', $title, $desc, $url, $courseId, $uid);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (($_POST['action'] ?? '') === 'update') {
        $tid = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $desc = trim($_POST['description']);
        $url = trim($_POST['video_url']);
        $courseId = (int)$_POST['course_id'] ?: null;
        $stmt = $conn->prepare("UPDATE tutorials SET title=?, description=?, video_url=?, course_id=? WHERE id=?");
        $stmt->bind_param('sssii', $title, $desc, $url, $courseId, $tid);
        $stmt->execute();
        $stmt->close();
    }
    redirect('tutorials.php');
}
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM tutorials WHERE id=" . (int)$_GET['delete']);
    redirect('tutorials.php');
}
$courses = $conn->query("SELECT c.*, d.name AS dept FROM courses c JOIN departments d ON d.id=c.department_id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
$editing = null;
if (isset($_GET['edit'])) {
    $editing = $conn->query("SELECT * FROM tutorials WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}
$tuts = $conn->query("SELECT t.*, c.name AS course_name FROM tutorials t LEFT JOIN courses c ON c.id=t.course_id ORDER BY t.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutorials — Principal</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Manage Tutorials</h1>
    <p class="subtitle">Add YouTube tutorials relevant to each course.</p>

    <form method="POST" class="card-form">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
        <h2><?= $editing ? 'Edit Tutorial' : 'Add New Tutorial' ?></h2>
        <div class="form-group"><label>Title</label><input type="text" name="title" required value="<?= $editing ? esc($editing['title']) : '' ?>"></div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= $editing ? esc($editing['description']) : '' ?></textarea></div>
        <div class="form-group"><label>YouTube URL</label><input type="url" name="video_url" required value="<?= $editing ? esc($editing['video_url']) : '' ?>"></div>
        <div class="form-group"><label>Course (leave blank for general)</label>
            <select name="course_id"><option value="">— General —</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($editing && $editing['course_id']==$c['id'])?'selected':'' ?>><?= esc($c['name']) ?></option>
            <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary"><?= $editing ? 'Save' : 'Add' ?></button>
        <?php if ($editing): ?><a class="link-btn" href="tutorials.php" style="margin-left:12px;">Cancel</a><?php endif; ?>
    </form>

    <h2 style="margin-top:30px;">All Tutorials (<?= count($tuts) ?>)</h2>
    <table class="data-table">
        <thead><tr><th>Title</th><th>Course</th><th>Link</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($tuts as $t): ?>
        <tr>
            <td><?= esc($t['title']) ?></td>
            <td><?= esc($t['course_name'] ?? 'General') ?></td>
            <td><a class="link-btn" href="<?= esc($t['video_url']) ?>" target="_blank">Open</a></td>
            <td>
                <a class="link-btn" href="?edit=<?= $t['id'] ?>">Edit</a>
                <a class="link-btn" href="?delete=<?= $t['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>