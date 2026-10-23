<?php
require_once '../config/db.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course = (int)($_POST['course_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    if ($course && $name && $code) {
        $stmt = $conn->prepare("INSERT INTO units (course_id, name, code) VALUES (?,?,?)");
        $stmt->bind_param('iss', $course, $name, $code);
        $stmt->execute(); $stmt->close();
        redirect('units.php');
    }
}
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM units WHERE id=" . (int)$_GET['delete']);
    redirect('units.php');
}
$courses = $conn->query("SELECT c.*, d.name AS dept FROM courses c JOIN departments d ON d.id=c.department_id ORDER BY d.name, c.name")->fetch_all(MYSQLI_ASSOC);
$rows = $conn->query("SELECT u.*, c.name AS course_name FROM units u JOIN courses c ON c.id=u.course_id ORDER BY c.name, u.name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Units — Admin</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Units</h1>
    <p class="subtitle">Units are subjects within a course.</p>

    <form method="POST" class="card-form">
        <div class="form-group">
            <label>Course</label>
            <select name="course_id" required>
                <option value="">— Select —</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= esc($c['dept'] . ' — ' . $c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Unit Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Unit Code</label><input type="text" name="code" required></div>
        </div>
        <button type="submit" class="btn-primary">Add Unit</button>
    </form>

    <table class="data-table" style="margin-top:30px;">
        <thead><tr><th>Code</th><th>Name</th><th>Course</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><strong><?= esc($r['code']) ?></strong></td>
            <td><?= esc($r['name']) ?></td>
            <td><?= esc($r['course_name']) ?></td>
            <td><a class="link-btn" href="?delete=<?= $r['id'] ?>" onclick="return confirm('Delete unit?')">Delete</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>