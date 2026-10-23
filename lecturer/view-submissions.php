<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

$aid = (int)($_GET['assignment'] ?? 0);
$assignment = $conn->query("
    SELECT uf.*, un.name AS unit_name, un.code AS unit_code, un.id AS unit_id
    FROM uploaded_files uf
    JOIN units un ON un.id = uf.unit_id
    JOIN lecturer_units lu ON lu.unit_id = uf.unit_id
    WHERE uf.id = $aid AND uf.file_type = 'assignment' AND lu.lecturer_id = $lid
")->fetch_assoc();
if (!$assignment) die('Assignment not found or access denied.');

$msg = '';

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_id'])) {
    $subId = (int)$_POST['sub_id'];
    $grade = (int)$_POST['grade'];
    $feedback = trim($_POST['feedback'] ?? '');
    if ($grade >= 0 && $grade <= 100) {
        $stmt = $conn->prepare("UPDATE assignment_submissions SET grade=?, feedback=?, graded_by=?, graded_at=NOW() WHERE id=? AND assignment_file_id=?");
        $stmt->bind_param('isiii', $grade, $feedback, $lid, $subId, $aid);
        $stmt->execute();
        $stmt->close();
        $msg = 'Grade saved.';
    }
}

// Handle grade removal (allow regrading)
if (isset($_GET['reset'])) {
    $subId = (int)$_GET['reset'];
    $stmt = $conn->prepare("UPDATE assignment_submissions SET grade=NULL, feedback=NULL, graded_by=NULL, graded_at=NULL WHERE id=? AND assignment_file_id=?");
    $stmt->bind_param('ii', $subId, $aid);
    $stmt->execute();
    $stmt->close();
    redirect("view-submissions.php?assignment=$aid");
}

$subs = $conn->query("
    SELECT s.*, u.full_name, u.admission_no
    FROM assignment_submissions s
    JOIN users u ON u.id = s.student_id
    WHERE s.assignment_file_id = $aid
    ORDER BY s.submitted_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submissions — <?= esc($assignment['title']) ?></title>
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
    <h1>Submissions — <?= esc($assignment['title']) ?></h1>
    <p class="subtitle"><?= esc($assignment['unit_code'].' · '.$assignment['unit_name']) ?> · <?= count($subs) ?> submission(s)</p>
    <p style="margin-bottom:20px;">
        <a class="link-btn" href="assignments.php?unit=<?= $assignment['unit_id'] ?>">← Back to Assignments</a>
        <a class="link-btn" href="../uploads/<?= esc($assignment['file_path']) ?>" target="_blank">📎 View Original Brief</a>
    </p>

    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>

    <?php if (empty($subs)): ?>
        <div class="empty-state"><p>No submissions yet. Students will appear here once they submit.</p></div>
    <?php else: ?>
    <?php foreach ($subs as $s): ?>
    <div class="question-card">
        <div class="q-header">
            <div>
                <strong><?= esc($s['full_name']) ?></strong>
                <span class="text-muted"> · <?= esc($s['admission_no']) ?></span>
            </div>
            <span class="text-muted"><?= esc($s['submitted_at']) ?></span>
        </div>

        <p style="margin:10px 0;">
            <a class="link-btn" href="../uploads/<?= esc($s['file_path']) ?>" target="_blank">
                📎 Download: <?= esc($s['original_name']) ?>
            </a>
        </p>

        <?php if ($s['notes']): ?>
            <p><em>Student notes:</em> <?= esc($s['notes']) ?></p>
        <?php endif; ?>

        <?php if ($s['grade'] !== null): ?>
            <div class="alert-success" style="margin-top:12px;">
                <strong>Graded: <?= (int)$s['grade'] ?>/100</strong>
                <?php if ($s['feedback']): ?><br>Feedback: <?= esc($s['feedback']) ?><?php endif; ?>
                <br><small>Graded on <?= esc($s['graded_at']) ?></small>
            </div>
        <?php endif; ?>

        <form method="POST" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
            <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Grade (0-100)</label>
                    <input type="number" name="grade" min="0" max="100" required value="<?= $s['grade'] !== null ? (int)$s['grade'] : '' ?>">
                </div>
                <div class="form-group">
                    <label>Feedback</label>
                    <input type="text" name="feedback" value="<?= esc($s['feedback'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" class="btn-primary"><?= $s['grade'] !== null ? 'Update Grade' : 'Save Grade' ?></button>
            <?php if ($s['grade'] !== null): ?>
                <a class="link-btn" href="?assignment=<?= $aid ?>&reset=<?= $s['id'] ?>" style="margin-left:12px;" onclick="return confirm('Clear this grade so the student can resubmit?')">Clear Grade (allow resubmit)</a>
            <?php endif; ?>
        </form>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
</body>
</html>