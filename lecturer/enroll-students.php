<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

// Load lecturer's units
$units = $conn->query("
    SELECT un.*, c.name AS course_name
    FROM lecturer_units lu
    JOIN units un ON un.id = lu.unit_id
    JOIN courses c ON c.id = un.course_id
    WHERE lu.lecturer_id = $lid
    ORDER BY un.name
")->fetch_all(MYSQLI_ASSOC);

if (empty($units)) {
    ?>
    <!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Students</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/responsive.css"></head>
    <body>
    <?php include '../includes/header.php'; ?>
    <div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h1>Enroll Students</h1>
        <div class="empty-state"><p>You have no units assigned. Contact the Principal.</p></div>
    </main>
    </div>
    </body></html>
    <?php
    exit;
}

// Build list of allowed unit IDs (int, for safe validation)
$allowedIds = array_map('intval', array_column($units, 'id'));

// Determine which unit is currently selected — validate BEFORE any query
$requested = (int)($_GET['unit'] ?? $_POST['unit_id'] ?? 0);
if (!in_array($requested, $allowedIds, true)) {
    $requested = $allowedIds[0];
}
$unit_id = $requested;

// Fresh fetch of the selected unit — always matching the URL/POST value
$unit = $conn->query("
    SELECT un.*, c.id AS course_id, c.name AS course_name
    FROM units un
    JOIN courses c ON c.id = un.course_id
    WHERE un.id = $unit_id
")->fetch_assoc();
if (!$unit) die('Unit not found.');

$msg = ''; $err = '';

// Enroll action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
    $studentIds = $_POST['students'] ?? [];
    if (!empty($studentIds)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO unit_enrollments (student_id, unit_id, enrolled_by) VALUES (?,?,?)");
        $count = 0;
        foreach ($studentIds as $sid) {
            $sid = (int)$sid;
            if ($sid <= 0) continue;
            $stmt->bind_param('iii', $sid, $unit_id, $lid);
            if ($stmt->execute()) $count++;
        }
        $stmt->close();
        logAction($conn, 'enrolled_students', 'unit', $unit_id, "Enrolled $count student(s) into {$unit['code']}");
        redirect("enroll-students.php?unit=$unit_id");
    }
}

// Remove enrollment
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM unit_enrollments WHERE unit_id = ? AND student_id = ?");
    $stmt->bind_param('ii', $unit_id, $removeId);
    $stmt->execute();
    $stmt->close();
    logAction($conn, 'removed_enrollment', 'unit', $unit_id, "Removed student ID $removeId");
    redirect("enroll-students.php?unit=$unit_id");
}

// Only students in the SAME COURSE as this unit, active, and not yet enrolled in this unit
$courseId = (int)$unit['course_id'];
$students = $conn->query("
    SELECT u.id, u.full_name, u.admission_no, u.academic_year
    FROM users u
    WHERE u.role = 'student'
      AND u.is_active = 1
      AND u.course_id = $courseId
      AND u.id NOT IN (
          SELECT student_id FROM unit_enrollments WHERE unit_id = $unit_id
      )
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);

// Already enrolled in this unit
$enrolled = $conn->query("
    SELECT u.id, u.full_name, u.admission_no, ue.enrolled_at
    FROM unit_enrollments ue
    JOIN users u ON u.id = ue.student_id
    WHERE ue.unit_id = $unit_id
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enroll Students — <?= esc($unit['code']) ?></title>
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
    <h1>Enroll Students</h1>
    <p class="subtitle">
        Unit: <strong><?= esc($unit['code']) ?> — <?= esc($unit['name']) ?></strong>
        · Course: <strong><?= esc($unit['course_name']) ?></strong>
    </p>

    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <form method="GET" class="card-form" style="max-width:500px;margin-bottom:24px;">
        <div class="form-group">
            <label>Select Unit</label>
            <select name="unit" onchange="this.form.submit()">
                <?php foreach ($units as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= $unit_id === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= esc($u['code'].' — '.$u['name'].' ('.$u['course_name'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <h2>Available Students (<?= count($students) ?>)</h2>
    <p class="subtitle" style="margin-bottom:16px;">
        Active students in <strong><?= esc($unit['course_name']) ?></strong> who are not yet enrolled in <strong><?= esc($unit['code']) ?></strong>.
    </p>

    <?php if (empty($students)): ?>
        <div class="empty-state"><p>No unenrolled students in this course. All active students in this course are already enrolled.</p></div>
    <?php else: ?>
    <form method="POST" class="card-form">
        <input type="hidden" name="action" value="enroll">
        <input type="hidden" name="unit_id" value="<?= $unit_id ?>">
        <div class="checkbox-grid">
        <?php foreach ($students as $s): ?>
            <label class="check-label">
                <input type="checkbox" name="students[]" value="<?= (int)$s['id'] ?>">
                <span>
                    <strong><?= esc($s['full_name']) ?></strong><br>
                    <small><?= esc($s['admission_no']) ?><?php if (!empty($s['academic_year'])): ?> · <?= esc($s['academic_year']) ?><?php endif; ?></small>
                </span>
            </label>
        <?php endforeach; ?>
        </div>
        <button type="submit" class="btn-primary" style="margin-top:16px;">Enroll Selected into <?= esc($unit['code']) ?></button>
    </form>
    <?php endif; ?>

    <h2 style="margin-top:36px;">Currently Enrolled (<?= count($enrolled) ?>)</h2>
    <?php if (empty($enrolled)): ?>
        <div class="empty-state"><p>No students enrolled in this unit yet.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Student</th><th>Admission No</th><th>Enrolled At</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($enrolled as $e): ?>
        <tr>
            <td><?= esc($e['full_name']) ?></td>
            <td><?= esc($e['admission_no']) ?></td>
            <td><?= esc($e['enrolled_at']) ?></td>
            <td>
                <a class="link-btn" href="?unit=<?= $unit_id ?>&remove=<?= (int)$e['id'] ?>"
                   onclick="return confirm('Remove <?= esc($e['full_name']) ?> from this unit?')"
                   style="color:#c0392b;">Remove</a>
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