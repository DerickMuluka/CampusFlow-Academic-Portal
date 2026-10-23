<?php
require_once '../config/db.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid user.');

$user = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
if (!$user) die('User not found.');

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dept = (int)($_POST['department_id'] ?? 0) ?: null;
    $course = (int)($_POST['course_id'] ?? 0) ?: null;
    $adm = trim($_POST['admission_no'] ?? '');
    $staff = trim($_POST['staff_no'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    $academic_year = trim($_POST['academic_year'] ?? '') ?: null;

    if ($name && $email) {
        $admVal = $adm ?: null;
        $staffVal = $staff ?: null;
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, department_id=?, course_id=?, admission_no=?, staff_no=?, is_active=?, academic_year=? WHERE id=?");
        $stmt->bind_param('ssiissiis', $name, $email, $dept, $course, $admVal, $staffVal, $active, $academic_year, $id);
        if ($stmt->execute()) $msg = 'User updated.';
        else $err = 'Update failed. Email may be in use.';
        $stmt->close();
    } else { $err = 'Name and email required.'; }
}

$departments = $conn->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$courses = $conn->query("SELECT * FROM courses ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$user = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User</title>
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
    <h1>Edit User</h1>
    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>
    <form method="POST" class="card-form">
        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= esc($user['full_name']) ?>" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= esc($user['email']) ?>" required></div>

        <?php if ($user['role'] === 'student'): ?>
        <div class="form-group">
            <label>Academic Year</label>
            <input type="text" name="academic_year" value="<?= esc($user['academic_year'] ?? '') ?>" placeholder="e.g. 2026-2027" pattern="\d{4}-\d{4}" title="Format: YYYY-YYYY">
        </div>
        <?php endif; ?>

        <div class="form-group"><label>Role</label><input type="text" value="<?= esc($user['role']) ?>" disabled></div>

        <?php if ($user['role'] === 'lecturer'): ?>
        <div class="form-group"><label>Department</label>
            <select name="department_id">
                <option value="">— None —</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $user['department_id']==$d['id']?'selected':'' ?>><?= esc($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if ($user['role'] === 'student'): ?>
        <div class="form-group"><label>Course</label>
            <select name="course_id">
                <option value="">— None —</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $user['course_id']==$c['id']?'selected':'' ?>><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Admission No</label><input type="text" name="admission_no" value="<?= esc($user['admission_no'] ?? '') ?>"></div>
        <?php endif; ?>

        <?php if (in_array($user['role'], ['lecturer','principal'])): ?>
        <div class="form-group"><label>Staff No</label><input type="text" name="staff_no" value="<?= esc($user['staff_no'] ?? '') ?>"></div>
        <?php endif; ?>

        <div class="form-group">
            <label><input type="checkbox" name="is_active" <?= $user['is_active']?'checked':'' ?>> Active</label>
        </div>

        <button type="submit" class="btn-primary">Save Changes</button>
        <a class="link-btn" href="users.php" style="margin-left:12px;">Back to Users</a>
    </form>
</main>
</div>
</body>
</html>