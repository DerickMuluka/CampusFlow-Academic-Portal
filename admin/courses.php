<?php
require_once '../config/db.php';
requireRole('admin');

$msg = ''; $err = '';

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $dept = (int)($_POST['department_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $level = $_POST['level'] ?? 'Certificate';
    $body = trim($_POST['exam_body'] ?? '');
    $dur = trim($_POST['duration'] ?? '');
    if ($dept && $name && $code) {
        $stmt = $conn->prepare("INSERT INTO courses (department_id, name, code, level, exam_body, duration) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('isssss', $dept, $name, $code, $level, $body, $dur);
        if ($stmt->execute()) {
            logAction($conn, 'created_course', 'course', $conn->insert_id, $name);
            $msg = 'Course added.';
        } else {
            $err = 'Could not add course. Code may already exist.';
        }
        $stmt->close();
    } else {
        $err = 'Department, name, and code are required.';
    }
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id = (int)$_POST['course_id'];
    $dept = (int)$_POST['department_id'];
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $level = $_POST['level'] ?? 'Certificate';
    $body = trim($_POST['exam_body'] ?? '');
    $dur = trim($_POST['duration'] ?? '');
    if ($id && $dept && $name && $code) {
        $stmt = $conn->prepare("UPDATE courses SET department_id=?, name=?, code=?, level=?, exam_body=?, duration=? WHERE id=?");
        $stmt->bind_param('isssssi', $dept, $name, $code, $level, $body, $dur, $id);
        if ($stmt->execute()) {
            logAction($conn, 'updated_course', 'course', $id, $name);
            $msg = 'Course updated.';
        } else {
            $err = 'Update failed. Code may be in use.';
        }
        $stmt->close();
    }
    redirect('courses.php');
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Prevent delete if units or students still reference it
    $usedByUnits = (int)$conn->query("SELECT COUNT(*) c FROM units WHERE course_id = $id")->fetch_assoc()['c'];
    $usedByStudents = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE course_id = $id")->fetch_assoc()['c'];
    if ($usedByUnits > 0 || $usedByStudents > 0) {
        $err = "Cannot delete: $usedByUnits unit(s) and $usedByStudents student(s) reference this course. Remove them first.";
    } else {
        $conn->query("DELETE FROM courses WHERE id = $id");
        logAction($conn, 'deleted_course', 'course', $id);
    }
    if ($err === '') redirect('courses.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $editing = $conn->query("SELECT * FROM courses WHERE id = " . (int)$_GET['edit'])->fetch_assoc();
}

$departments = $conn->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$rows = $conn->query("
    SELECT c.*, d.name AS dept_name,
           (SELECT COUNT(*) FROM units u WHERE u.course_id = c.id) AS unit_count,
           (SELECT COUNT(*) FROM users us WHERE us.course_id = c.id AND us.role = 'student') AS student_count
    FROM courses c
    JOIN departments d ON d.id = c.department_id
    ORDER BY d.name, c.name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Courses — Admin</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Courses</h1>
    <p class="subtitle">All courses by department. These are the same courses students see when applying online.</p>

    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <form method="POST" class="card-form">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?><input type="hidden" name="course_id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
        <h2><?= $editing ? 'Edit Course' : 'Add New Course' ?></h2>
        <div class="form-row">
            <div class="form-group">
                <label>Department *</label>
                <select name="department_id" required>
                    <option value="">— Select —</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= ($editing && (int)$editing['department_id'] === (int)$d['id']) ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Course Name *</label><input type="text" name="name" required value="<?= $editing ? esc($editing['name']) : '' ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Code *</label><input type="text" name="code" required maxlength="20" value="<?= $editing ? esc($editing['code']) : '' ?>"></div>
            <div class="form-group">
                <label>Level</label>
                <select name="level">
                    <option <?= ($editing && $editing['level'] === 'Certificate') ? 'selected' : '' ?>>Certificate</option>
                    <option <?= ($editing && $editing['level'] === 'Diploma') ? 'selected' : '' ?>>Diploma</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Exam Body</label><input type="text" name="exam_body" placeholder="ICM (UK), KNEC" value="<?= $editing ? esc($editing['exam_body']) : '' ?>"></div>
            <div class="form-group"><label>Duration</label><input type="text" name="duration" placeholder="6 months, 1 year" value="<?= $editing ? esc($editing['duration']) : '' ?>"></div>
        </div>
        <button type="submit" class="btn-primary"><?= $editing ? 'Save Changes' : 'Add Course' ?></button>
        <?php if ($editing): ?><a class="link-btn" href="courses.php" style="margin-left:12px;">Cancel</a><?php endif; ?>
    </form>

    <h2 style="margin-top:36px;">All Courses (<?= count($rows) ?>)</h2>
    <table class="data-table">
        <thead>
            <tr><th>Code</th><th>Name</th><th>Department</th><th>Level</th><th>Body</th><th>Units</th><th>Students</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><strong><?= esc($r['code']) ?></strong></td>
            <td><?= esc($r['name']) ?></td>
            <td><?= esc($r['dept_name']) ?></td>
            <td><?= esc($r['level']) ?></td>
            <td><?= esc($r['exam_body'] ?: '—') ?></td>
            <td><?= (int)$r['unit_count'] ?></td>
            <td><?= (int)$r['student_count'] ?></td>
            <td>
                <a class="link-btn" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
                <a class="link-btn" href="?delete=<?= (int)$r['id'] ?>" onclick="return confirm('Delete this course?')" style="color:#c0392b;">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>