<?php
require_once '../config/db.php';
requireRole('principal');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lecturer = (int)($_POST['lecturer_id'] ?? 0);
    $units = $_POST['units'] ?? [];
    if ($lecturer > 0) {
        $conn->query("DELETE FROM lecturer_units WHERE lecturer_id = $lecturer");
        if (!empty($units)) {
            $stmt = $conn->prepare("INSERT INTO lecturer_units (lecturer_id, unit_id, assigned_by) VALUES (?,?,?)");
            $by = (int)$_SESSION['user_id'];
            foreach ($units as $uid) {
                $uid = (int)$uid;
                $stmt->bind_param('iii', $lecturer, $uid, $by);
                $stmt->execute();
            }
            $stmt->close();
        }
        $msg = 'Unit assignments saved.';
    }
}

$lecturers = $conn->query("SELECT u.*, d.name AS dept FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.role='lecturer' AND u.is_active=1 ORDER BY u.full_name")->fetch_all(MYSQLI_ASSOC);
$selected = (int)($_GET['lecturer'] ?? 0);

// Group units by course
$grouped = [];
$res = $conn->query("
    SELECT un.*, c.id AS course_id, c.name AS course_name
    FROM units un
    JOIN courses c ON c.id = un.course_id
    ORDER BY c.name, un.name
");
while ($r = $res->fetch_assoc()) $grouped[$r['course_name']][] = $r;

$assigned = [];
if ($selected > 0) {
    $r = $conn->query("SELECT unit_id FROM lecturer_units WHERE lecturer_id=$selected");
    while ($row = $r->fetch_assoc()) $assigned[] = (int)$row['unit_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Lecturers — Principal</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
<style>
    .unit-group { background:var(--white); border-radius:var(--radius); padding:18px; box-shadow:var(--shadow); margin-bottom:16px; border-left:4px solid var(--accent); }
    .unit-group h3 { color:var(--primary); font-size:.95rem; margin-bottom:10px; text-transform:uppercase; letter-spacing:.5px; }
    .unit-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:10px; }
    .unit-tile { display:flex; gap:10px; align-items:flex-start; padding:12px; border:2px solid var(--border); border-radius:8px; cursor:pointer; transition:border-color .15s, background .15s; background:var(--white); }
    .unit-tile:hover { border-color:var(--accent); background:#fffdf5; }
    .unit-tile:has(input:checked) { border-color:var(--accent); background:#fff8e6; }
    .unit-tile input { margin-top:3px; flex-shrink:0; }
    .unit-tile .unit-meta { font-size:.82rem; }
    .unit-tile .unit-code { font-weight:700; color:var(--primary); }
    .unit-tile .unit-name { color:var(--text); }
    @media (max-width:600px) { .unit-grid { grid-template-columns:1fr; } }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Assign Units to Lecturer</h1>
    <p class="subtitle">Select a lecturer, then tick the units they will teach.</p>
    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>

    <form method="GET" class="card-form" style="max-width:500px;margin-bottom:24px;">
        <div class="form-group">
            <label>Select Lecturer</label>
            <select name="lecturer" onchange="this.form.submit()">
                <option value="">— Choose a lecturer —</option>
                <?php foreach ($lecturers as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $selected===$l['id']?'selected':'' ?>>
                        <?= esc($l['full_name']) ?> <?= $l['dept'] ? '· '.esc($l['dept']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($selected): ?>
    <form method="POST">
        <input type="hidden" name="lecturer_id" value="<?= $selected ?>">
        <?php foreach ($grouped as $courseName => $units): ?>
        <div class="unit-group">
            <h3><?= esc($courseName) ?></h3>
            <div class="unit-grid">
                <?php foreach ($units as $u): ?>
                <label class="unit-tile">
                    <input type="checkbox" name="units[]" value="<?= $u['id'] ?>" <?= in_array((int)$u['id'], $assigned, true) ? 'checked' : '' ?>>
                    <span class="unit-meta">
                        <span class="unit-code"><?= esc($u['code']) ?></span><br>
                        <span class="unit-name"><?= esc($u['name']) ?></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-primary">Save Assignments</button>
    </form>
    <?php endif; ?>
</main>
</div>
</body>
</html>