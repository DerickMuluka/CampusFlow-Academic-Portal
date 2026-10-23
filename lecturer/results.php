<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

// Handle retake authorization
if (isset($_POST['authorize_attempt'])) {
    $aid = (int)$_POST['authorize_attempt'];
    $check = $conn->prepare("
        SELECT a.id FROM attempts a
        JOIN exams e ON e.id = a.exam_id
        JOIN lecturer_units lu ON lu.unit_id = e.unit_id
        WHERE a.id = ? AND lu.lecturer_id = ?
    ");
    $check->bind_param('ii', $aid, $lid);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
        $upd = $conn->prepare("UPDATE attempts SET retake_authorized=1, authorized_by=?, authorized_at=NOW() WHERE id=?");
        $upd->bind_param('ii', $lid, $aid);
        $upd->execute();
        $upd->close();
    }
    $check->close();
    redirect('results.php');
}

// Fetch results for this lecturer's units
$stmt = $conn->prepare("
    SELECT a.id, a.score, a.status, a.retake_authorized, a.submitted_at,
           u.full_name, u.admission_no, ex.title AS exam_title, ex.total_marks,
           un.name AS unit_name,
           (SELECT COUNT(*) FROM violations v WHERE v.attempt_id=a.id) AS viol_count
    FROM attempts a
    JOIN users u ON u.id = a.student_id
    JOIN exams ex ON ex.id = a.exam_id
    JOIN units un ON un.id = ex.unit_id
    JOIN lecturer_units lu ON lu.unit_id = ex.unit_id
    WHERE lu.lecturer_id = ? AND a.status != 'in_progress'
    ORDER BY a.submitted_at DESC
");
$stmt->bind_param('i', $lid);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Results — Lecturer</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Student Results</h1>
    <p class="subtitle">Review results and authorize retakes when appropriate.</p>

    <?php if (empty($results)): ?>
        <div class="empty-state"><p>No results yet for your units.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Student</th><th>Adm No</th><th>Unit</th><th>Exam</th><th>Score</th><th>Status</th><th>Violations</th><th>Retake</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr>
                <td><?= esc($r['full_name']) ?></td>
                <td><?= esc($r['admission_no']) ?></td>
                <td><?= esc($r['unit_name']) ?></td>
                <td><?= esc($r['exam_title']) ?></td>
                <td><?= (int)$r['score'] ?> / <?= (int)$r['total_marks'] ?></td>
                <td><?= esc($r['status']) ?></td>
                <td><?= (int)$r['viol_count'] ?></td>
                <td><?= $r['retake_authorized'] ? '<span class="badge badge-green">Authorized</span>' : '—' ?></td>
                <td>
                    <?php if (!$r['retake_authorized']): ?>
                        <form method="POST" style="display:inline;">
                            <button name="authorize_attempt" value="<?= $r['id'] ?>" class="link-btn" onclick="return confirm('Authorize retake for this student?')">Authorize</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">Done</span>
                    <?php endif; ?>
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