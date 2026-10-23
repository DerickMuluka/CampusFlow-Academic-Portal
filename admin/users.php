<?php
require_once '../config/db.php';
requireRole('admin');

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $dept = (int)($_POST['department_id'] ?? 0) ?: null;
    $course = (int)($_POST['course_id'] ?? 0) ?: null;
    $adm = trim($_POST['admission_no'] ?? '');
    $staff = trim($_POST['staff_no'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '') ?: null;

    if (!in_array($role, ['admin','principal','lecturer','student'], true) || $name === '' || $email === '' || strlen($pass) < 6) {
        $err = 'All fields required. Password must be at least 6 characters.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $admVal = $adm !== '' ? $adm : null;
        $staffVal = $staff !== '' ? $staff : null;

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, department_id, course_id, admission_no, staff_no, academic_year) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssiisss', $name, $email, $hash, $role, $dept, $course, $admVal, $staffVal, $academic_year);
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            logAction($conn, 'created_user', 'user', $newId, "Created $role: $name");
            $msg = "Account created for $name ($role).";
        } else {
            $err = 'Failed: email, admission number or staff number already exists.';
        }
        $stmt->close();
    }
}

if (isset($_GET['deactivate'])) {
    $id = (int)$_GET['deactivate'];
    if ($id !== (int)$_SESSION['user_id']) {
        $conn->query("UPDATE users SET is_active = 0 WHERE id = $id");
        logAction($conn, 'deactivated_user', 'user', $id);
    }
    redirect('users.php');
}
if (isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    $conn->query("UPDATE users SET is_active = 1 WHERE id = $id");
    logAction($conn, 'activated_user', 'user', $id);
    redirect('users.php');
}

$departments = $conn->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$courses = $conn->query("SELECT * FROM courses ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("
    SELECT u.*, d.name AS dept_name, c.name AS course_name
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    LEFT JOIN courses c ON c.id = u.course_id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users — Admin</title>
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
    <h1>Manage Users</h1>
    <p class="subtitle">Create and manage all system accounts.</p>

    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <form method="POST" class="card-form">
        <h2>Create New Account</h2>
        <div class="form-row">
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="student">Student</option>
                    <option value="lecturer">Lecturer</option>
                    <option value="principal">Principal</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
        </div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
        <div class="form-row">
            <div class="form-group"><label>Password *</label><input type="text" name="password" required minlength="6"></div>
            <div class="form-group"><label>Staff No (lecturer/principal)</label><input type="text" name="staff_no"></div>
        </div>
        <div class="form-group">
            <label>Academic Year (students)</label>
            <input type="text" name="academic_year" placeholder="e.g. 2026-2027" pattern="\d{4}-\d{4}" title="Format: YYYY-YYYY">
        </div>
        <div class="form-row">
            <div class="form-group"><label>Admission No (student)</label><input type="text" name="admission_no" placeholder="RIF/2026/001"></div>
            <div class="form-group">
                <label>Department (lecturer)</label>
                <select name="department_id"><option value="">— None —</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Course (student)</label>
            <select name="course_id"><option value="">— None —</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
            <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary">Create Account</button>
    </form>

    <h2 style="margin-top:40px;">All Users (<?= count($users) ?>)</h2>
    <table class="data-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Dept / Course</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= esc($u['full_name']) ?></td>
            <td><?= esc($u['email']) ?></td>
            <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'red' : ($u['role'] === 'principal' ? 'purple' : ($u['role'] === 'lecturer' ? 'blue' : 'green')) ?>"><?= esc($u['role']) ?></span></td>
            <td><?= esc($u['dept_name'] ?? $u['course_name'] ?? '—') ?></td>
            <td><?= $u['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-grey">Disabled</span>' ?></td>
            <td>
                <a class="link-btn" href="edit-user.php?id=<?= (int)$u['id'] ?>">Edit</a>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                    <a class="link-btn" href="?<?= $u['is_active'] ? 'deactivate' : 'activate' ?>=<?= (int)$u['id'] ?>">
                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </a>
                <?php else: ?>
                    <span class="text-muted">You</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>