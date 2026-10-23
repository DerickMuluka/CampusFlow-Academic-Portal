<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

// Fetch units assigned to this lecturer
$myUnits = $conn->query("
    SELECT un.*, c.name AS course_name
    FROM lecturer_units lu
    JOIN units un ON un.id = lu.unit_id
    JOIN courses c ON c.id = un.course_id
    WHERE lu.lecturer_id = $lid
    ORDER BY un.name
")->fetch_all(MYSQLI_ASSOC);

if (empty($myUnits)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exams — No Units</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/responsive.css"></head>
    <body>
    <?php include '../includes/header.php'; ?>
    <div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h1>Exams</h1>
        <div class="empty-state">
            <p><strong>You have no units assigned.</strong></p>
            <p>The Principal must assign you to units before you can create exams.</p>
            <p>Contact: principal@rifkinscollege.com</p>
        </div>
    </main>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Determine selected unit
$unit_id = (int)($_GET['unit'] ?? $_POST['unit_id'] ?? $myUnits[0]['id']);

// Verify ownership — if not owned, fall back to first owned unit with a message
$owned = false;
foreach ($myUnits as $u) if ((int)$u['id'] === $unit_id) { $owned = true; break; }
if (!$owned) {
    $unit_id = (int)$myUnits[0]['id'];
    $owned = true;
}

$unit = $conn->query("SELECT un.*, c.name AS course_name FROM units un JOIN courses c ON c.id=un.course_id WHERE un.id=$unit_id")->fetch_assoc();

$msg = '';

// Create exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $dur = (int)($_POST['duration_minutes'] ?? 60);
    $tm = (int)($_POST['total_marks'] ?? 10);
    $pm = (int)($_POST['pass_mark'] ?? 4);
    $max = (int)($_POST['max_attempts'] ?? 1);
    if ($title) {
        $stmt = $conn->prepare("INSERT INTO exams (unit_id, title, duration_minutes, total_marks, pass_mark, max_attempts, created_by) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('isiiiii', $unit_id, $title, $dur, $tm, $pm, $max, $lid);
        $stmt->execute();
        $stmt->close();
        redirect("exams.php?unit=$unit_id");
    }
}

// Update exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $eid = (int)$_POST['exam_id'];
    $title = trim($_POST['title'] ?? '');
    $dur = (int)($_POST['duration_minutes'] ?? 60);
    $tm = (int)($_POST['total_marks'] ?? 10);
    $pm = (int)($_POST['pass_mark'] ?? 4);
    $max = (int)($_POST['max_attempts'] ?? 1);
    $chk = $conn->query("SELECT unit_id FROM exams WHERE id=$eid")->fetch_assoc();
    if ($chk && (int)$chk['unit_id'] === $unit_id && $title) {
        $stmt = $conn->prepare("UPDATE exams SET title=?, duration_minutes=?, total_marks=?, pass_mark=?, max_attempts=? WHERE id=?");
        $stmt->bind_param('siiiii', $title, $dur, $tm, $pm, $max, $eid);
        $stmt->execute();
        $stmt->close();
    }
    redirect("exams.php?unit=$unit_id");
}

// Delete exam
if (isset($_GET['delete'])) {
    $eid = (int)$_GET['delete'];
    $chk = $conn->query("SELECT unit_id FROM exams WHERE id=$eid")->fetch_assoc();
    if ($chk && (int)$chk['unit_id'] === $unit_id) {
        $conn->query("DELETE FROM exams WHERE id=$eid");
    }
    redirect("exams.php?unit=$unit_id");
}

// Toggle active
if (isset($_GET['toggle'])) {
    $eid = (int)$_GET['toggle'];
    $chk = $conn->query("SELECT unit_id FROM exams WHERE id=$eid")->fetch_assoc();
    if ($chk && (int)$chk['unit_id'] === $unit_id) {
        $conn->query("UPDATE exams SET is_active = 1 - is_active WHERE id=$eid");
    }
    redirect("exams.php?unit=$unit_id");
}

// Editing mode
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editing = $conn->query("SELECT * FROM exams WHERE id=$eid AND unit_id=$unit_id")->fetch_assoc();
}

$exams = $conn->query("
    SELECT e.*, (SELECT COUNT(*) FROM questions q WHERE q.exam_id=e.id) AS qcount
    FROM exams e WHERE e.unit_id=$unit_id ORDER BY e.id DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exams — <?= esc($unit['name']) ?></title>
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
    <h1>Exams — <?= esc($unit['name']) ?></h1>
    <p class="subtitle"><?= esc($unit['code'].' · '.$unit['course_name']) ?></p>

    <form method="GET" class="card-form" style="max-width:420px;margin-bottom:24px;">
        <div class="form-group">
            <label>Select Unit</label>
            <select name="unit" onchange="this.form.submit()">
                <?php foreach ($myUnits as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $unit_id===$u['id']?'selected':'' ?>>
                        <?= esc($u['code'].' — '.$u['name'].' ('.$u['course_name'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form method="POST" class="card-form">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <input type="hidden" name="unit_id" value="<?= $unit_id ?>">
        <?php if ($editing): ?><input type="hidden" name="exam_id" value="<?= $editing['id'] ?>"><?php endif; ?>
        <h2><?= $editing ? 'Edit Exam' : 'Create New Exam' ?></h2>
        <div class="form-group"><label>Exam Title</label><input type="text" name="title" required value="<?= $editing ? esc($editing['title']) : '' ?>"></div>
        <div class="form-row">
            <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration_minutes" value="<?= $editing ? (int)$editing['duration_minutes'] : 60 ?>" required></div>
            <div class="form-group"><label>Max Attempts</label><input type="number" name="max_attempts" value="<?= $editing ? (int)$editing['max_attempts'] : 1 ?>" min="1" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Total Marks</label><input type="number" name="total_marks" value="<?= $editing ? (int)$editing['total_marks'] : 10 ?>" required></div>
            <div class="form-group"><label>Pass Mark</label><input type="number" name="pass_mark" value="<?= $editing ? (int)$editing['pass_mark'] : 4 ?>" required></div>
        </div>
        <button type="submit" class="btn-primary"><?= $editing ? 'Save Changes' : 'Create Exam' ?></button>
        <?php if ($editing): ?><a class="link-btn" href="exams.php?unit=<?= $unit_id ?>" style="margin-left:12px;">Cancel</a><?php endif; ?>
    </form>

    <h2 style="margin-top:30px;">Existing Exams (<?= count($exams) ?>)</h2>
    <?php if (empty($exams)): ?>
        <div class="empty-state"><p>No exams created for this unit yet.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Title</th><th>Duration</th><th>Questions</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($exams as $e): ?>
        <tr>
            <td><?= esc($e['title']) ?></td>
            <td><?= (int)$e['duration_minutes'] ?> min</td>
            <td><?= (int)$e['qcount'] ?></td>
            <td><span class="badge <?= $e['is_active'] ? 'badge-green' : 'badge-grey' ?>"><?= $e['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td>
                <a class="link-btn" href="questions.php?exam=<?= $e['id'] ?>">Questions</a>
                <a class="link-btn" href="?unit=<?= $unit_id ?>&edit=<?= $e['id'] ?>">Edit</a>
                <a class="link-btn" href="?unit=<?= $unit_id ?>&toggle=<?= $e['id'] ?>"><?= $e['is_active'] ? 'Disable' : 'Enable' ?></a>
                <a class="link-btn" href="?unit=<?= $unit_id ?>&delete=<?= $e['id'] ?>" onclick="return confirm('Delete exam and all questions?')">Delete</a>
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