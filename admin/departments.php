<?php
require_once '../config/db.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    if ($name && $code) {
        $stmt = $conn->prepare("INSERT INTO departments (name, code) VALUES (?,?)");
        $stmt->bind_param('ss', $name, $code);
        $stmt->execute(); $stmt->close();
        redirect('departments.php');
    }
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM departments WHERE id=$id");
    redirect('departments.php');
}
$rows = $conn->query("
    SELECT d.*, (SELECT COUNT(*) FROM courses c WHERE c.department_id=d.id) AS course_count,
           (SELECT COUNT(*) FROM users u WHERE u.department_id=d.id AND u.role='lecturer') AS lecturer_count
    FROM departments d ORDER BY d.name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Departments — Admin</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Departments</h1>
    <p class="subtitle">Academic departments at Rifkins College.</p>

    <form method="POST" class="card-form" style="max-width:500px;margin-bottom:30px;">
        <div class="form-row">
            <div class="form-group"><label>Department Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Code</label><input type="text" name="code" maxlength="10" required></div>
        </div>
        <button type="submit" class="btn-primary">Add Department</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Code</th><th>Name</th><th>Courses</th><th>Lecturers</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><strong><?= esc($r['code']) ?></strong></td>
            <td><?= esc($r['name']) ?></td>
            <td><?= (int)$r['course_count'] ?></td>
            <td><?= (int)$r['lecturer_count'] ?></td>
            <td><a class="link-btn" href="?delete=<?= $r['id'] ?>" onclick="return confirm('Delete this department?')">Delete</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>