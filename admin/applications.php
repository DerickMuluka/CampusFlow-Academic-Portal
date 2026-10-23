<?php
require_once '../config/db.php';
requireRole('admin');

$msg = ''; $err = '';

// Admit action: create user, assign admission number
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admit') {
    $appId = (int)$_POST['app_id'];
    $admissionNo = trim($_POST['admission_no'] ?? '');
    $tempPassword = trim($_POST['temp_password'] ?? '');

    if ($admissionNo === '' || $tempPassword === '') {
        $err = 'Admission number and temporary password are required.';
    } else {
        $app = $conn->query("SELECT * FROM applications WHERE id = $appId AND status = 'pending'")->fetch_assoc();
        if (!$app) {
            $err = 'Application not found or already processed.';
        } else {
            $course = $conn->query("SELECT c.id FROM courses c WHERE c.name = '" . $conn->real_escape_string($app['course_interest']) . "' LIMIT 1")->fetch_assoc();
            $courseId = $course ? (int)$course['id'] : null;
            $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $role = 'student';
            $name = $app['full_name'];
            $email = $app['email'];

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, admission_no, course_id) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('sssssi', $name, $email, $hash, $role, $admissionNo, $courseId);
            if ($stmt->execute()) {
                $newUserId = $conn->insert_id;
                $uid = (int)$_SESSION['user_id'];
                $upd = $conn->prepare("UPDATE applications SET status='admitted', admission_no=?, reviewed_by=?, reviewed_at=NOW(), student_user_id=? WHERE id=?");
                $upd->bind_param('siii', $admissionNo, $uid, $newUserId, $appId);
                $upd->execute();
                $upd->close();
                logAction($conn, 'admitted_student', 'application', $appId, "Admitted $name with $admissionNo");
                $msg = "Admitted $name as $admissionNo. Login: $email / $tempPassword";
            } else {
                $err = 'Could not create student account. Email may already exist.';
            }
            $stmt->close();
        }
    }
}

// Reject action
if (isset($_GET['reject'])) {
    $appId = (int)$_GET['reject'];
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE applications SET status='rejected', reviewed_by=?, reviewed_at=NOW() WHERE id=? AND status='pending'");
    $stmt->bind_param('ii', $uid, $appId);
    $stmt->execute();
    $stmt->close();
    logAction($conn, 'rejected_application', 'application', $appId);
    redirect('applications.php');
}

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending','reviewed','admitted','rejected','all'], true)) $status = 'pending';
$where = $status === 'all' ? '' : " WHERE status = '$status'";
$apps = $conn->query("SELECT * FROM applications $where ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$counts = [];
foreach (['pending','reviewed','admitted','rejected'] as $s) {
    $counts[$s] = (int)$conn->query("SELECT COUNT(*) c FROM applications WHERE status='$s'")->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Applications — Admin</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
<style>
    .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
    .filter-tabs a {
        padding: 8px 16px; background: var(--white); color: var(--primary);
        border-radius: 20px; text-decoration: none; font-weight: 700; font-size: .82rem;
        box-shadow: var(--shadow); transition: background .15s;
    }
    .filter-tabs a.active, .filter-tabs a:hover { background: var(--accent); color: var(--primary); }
    .filter-tabs a .count { background: rgba(0,0,0,.1); padding: 1px 7px; border-radius: 10px; margin-left: 4px; font-size: .72rem; }
    .app-detail { font-size: .82rem; color: var(--muted); }
    .app-detail strong { color: var(--text); }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Online Applications</h1>
    <p class="subtitle">Review applications submitted through the public website and admit new students.</p>

    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <div class="filter-tabs">
        <a href="?status=pending" class="<?= $status==='pending'?'active':'' ?>">Pending <span class="count"><?= $counts['pending'] ?></span></a>
        <a href="?status=reviewed" class="<?= $status==='reviewed'?'active':'' ?>">Reviewed <span class="count"><?= $counts['reviewed'] ?></span></a>
        <a href="?status=admitted" class="<?= $status==='admitted'?'active':'' ?>">Admitted <span class="count"><?= $counts['admitted'] ?></span></a>
        <a href="?status=rejected" class="<?= $status==='rejected'?'active':'' ?>">Rejected <span class="count"><?= $counts['rejected'] ?></span></a>
        <a href="?status=all" class="<?= $status==='all'?'active':'' ?>">All</a>
    </div>

    <?php if (empty($apps)): ?>
        <div class="empty-state"><p>No applications with status "<?= esc($status) ?>".</p></div>
    <?php else: ?>
        <?php foreach ($apps as $a): ?>
        <div class="question-card">
            <div class="q-header" style="margin-bottom:14px;">
                <div>
                    <strong style="color:var(--primary);font-size:1.05rem;"><?= esc($a['full_name']) ?></strong>
                    <span class="badge badge-<?= $a['status']==='pending'?'grey':($a['status']==='admitted'?'green':($a['status']==='rejected'?'red':'blue')) ?>" style="margin-left:10px;"><?= esc($a['status']) ?></span>
                </div>
                <span class="app-detail">Submitted <?= esc($a['created_at']) ?></span>
            </div>
            <div class="app-detail" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;margin-bottom:14px;">
                <div><strong>Email:</strong> <?= esc($a['email']) ?></div>
                <div><strong>Phone:</strong> <?= esc($a['phone']) ?></div>
                <div><strong>Course:</strong> <?= esc($a['course_interest']) ?></div>
                <div><strong>Campus:</strong> <?= esc($a['preferred_campus']) ?></div>
                <div><strong>KCSE:</strong> <?= esc($a['kcse_grade'] ?: '—') ?></div>
                <?php if ($a['admission_no']): ?><div><strong>Admission No:</strong> <?= esc($a['admission_no']) ?></div><?php endif; ?>
            </div>
            <?php if ($a['message']): ?>
                <p style="font-size:.85rem;padding:10px;background:var(--bg);border-radius:6px;margin-bottom:14px;"><em>"<?= esc($a['message']) ?>"</em></p>
            <?php endif; ?>

            <?php if ($a['status'] === 'pending'): ?>
            <form method="POST" style="padding-top:14px;border-top:1px solid var(--border);">
                <input type="hidden" name="action" value="admit">
                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Admission Number</label>
                        <input type="text" name="admission_no" required placeholder="RIF/2026/001">
                    </div>
                    <div class="form-group">
                        <label>Temporary Password</label>
                        <input type="text" name="temp_password" required value="Student@<?= date('Y') ?>">
                    </div>
                </div>
                <button type="submit" class="btn-primary">Admit Student</button>
                <a class="link-btn" href="?reject=<?= $a['id'] ?>" onclick="return confirm('Reject this application?')" style="margin-left:14px;color:#c0392b;">Reject</a>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
</body>
</html>