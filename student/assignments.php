<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

$units = getStudentUnits($conn, $sid);
$noUnits = empty($units);

$msg = ''; $err = '';

// Handle submission upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $aid = (int)$_POST['assignment_id'];
    $notes = trim($_POST['notes'] ?? '');
    
    // Verify this assignment belongs to an enrolled unit
    $chk = $conn->prepare("
        SELECT uf.id FROM uploaded_files uf
        JOIN unit_enrollments ue ON ue.unit_id = uf.unit_id
        WHERE uf.id = ? AND ue.student_id = ? AND uf.file_type = 'assignment'
    ");
    $chk->bind_param('ii', $aid, $sid);
    $chk->execute();
    $allowed = $chk->get_result()->fetch_assoc() !== null;
    $chk->close();

    if (!$allowed) { $err = 'Invalid assignment.'; }
    elseif (empty($_FILES['submission'])) { $err = 'Please attach a file.'; }
    else {
        list($ok, $result) = handleUpload($_FILES['submission'], ['pdf','doc','docx','txt','zip','jpg','jpeg','png'], 15728640);
        if (!$ok) { $err = $result; }
        else {
            $orig = $_FILES['submission']['name'];
            // Check if a submission already exists — only allow ONE update if not yet graded
            $existing = $conn->query("SELECT id, grade FROM assignment_submissions WHERE assignment_file_id=$aid AND student_id=$sid")->fetch_assoc();
            if ($existing) {
                if ($existing['grade'] !== null) {
                    $err = 'This assignment has already been graded. You can no longer update it.';
                    @unlink(__DIR__ . '/../uploads/' . $result);
                } else {
                    // update existing
                    @unlink(__DIR__ . '/../uploads/' . $existing['file_path']);
                    $upd = $conn->prepare("UPDATE assignment_submissions SET file_path=?, original_name=?, notes=? WHERE id=?");
                    $upd->bind_param('sssi', $result, $orig, $notes, $existing['id']);
                    $upd->execute();
                    $upd->close();
                    $msg = 'Submission updated.';
                }
            } else {
                $ins = $conn->prepare("INSERT INTO assignment_submissions (assignment_file_id, student_id, file_path, original_name, notes) VALUES (?,?,?,?,?)");
                $ins->bind_param('iisss', $aid, $sid, $result, $orig, $notes);
                $ins->execute();
                $ins->close();
                $msg = 'Assignment submitted.';
            }
        }
    }
}

// Fetch assignments for enrolled units with submission status
$assignments = [];
if (!$noUnits) {
    $unitIds = implode(',', array_map('intval', array_column($units, 'id')));
    $assignments = $conn->query("
        SELECT uf.*, un.name AS unit_name, un.code AS unit_code, u.full_name AS uploader,
               s.id AS sub_id, s.original_name AS sub_name, s.file_path AS sub_path,
               s.submitted_at, s.grade, s.feedback, s.graded_at
        FROM uploaded_files uf
        JOIN units un ON un.id = uf.unit_id
        JOIN users u ON u.id = uf.uploader_id
        LEFT JOIN assignment_submissions s ON s.assignment_file_id = uf.id AND s.student_id = $sid
        WHERE uf.unit_id IN ($unitIds) AND uf.file_type = 'assignment'
        ORDER BY uf.uploaded_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Assignments</title>
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
    <h1>My Assignments</h1>
    <p class="subtitle">Download briefs, submit your work, view grades and feedback.</p>

    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <?php if ($noUnits): ?>
        <div class="empty-state"><p>You are not enrolled in any units yet.</p></div>
    <?php elseif (empty($assignments)): ?>
        <div class="empty-state"><p>No assignments have been posted for your units yet.</p></div>
    <?php else: ?>
    <?php foreach ($assignments as $a): ?>
        <div class="question-card">
            <div class="q-header">
                <strong><?= esc($a['title']) ?></strong>
                <span class="badge badge-blue"><?= esc($a['unit_code']) ?></span>
            </div>
            <p><?= nl2br(esc($a['description'] ?? '')) ?></p>
            <p class="text-muted">Posted by <?= esc($a['uploader']) ?> · <?= esc($a['uploaded_at']) ?></p>
            <p><a class="link-btn" href="../uploads/<?= esc($a['file_path']) ?>" target="_blank">📎 Download brief: <?= esc($a['original_name']) ?></a></p>

            <?php if ($a['sub_id']): ?>
                <div class="alert-success" style="margin-top:12px;">
                    <strong>Your submission:</strong> <?= esc($a['sub_name']) ?> · <?= esc($a['submitted_at']) ?>
                    <?php if ($a['grade'] !== null): ?>
                        <br><strong>Grade: <?= (int)$a['grade'] ?>/100</strong>
                        <?php if ($a['feedback']): ?><br>Feedback: <?= esc($a['feedback']) ?><?php endif; ?>
                        <br><em>Graded — cannot be updated.</em>
                    <?php else: ?>
                        <br><em>Not yet graded. You may update once before grading.</em>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($a['grade'] === null): ?>
                <form method="POST" enctype="multipart/form-data" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
                    <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                    <div class="form-group">
                        <label><?= $a['sub_id'] ? 'Update submission' : 'Submit your work' ?> (PDF, DOC, ZIP, JPG, PNG — max 15MB)</label>
                        <input type="file" name="submission" required>
                    </div>
                    <div class="form-group">
                        <label>Notes for lecturer (optional)</label>
                        <textarea name="notes" rows="2"><?= $a['sub_id'] ? '' : '' ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary"><?= $a['sub_id'] ? 'Update Submission' : 'Submit Assignment' ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
</body>
</html>