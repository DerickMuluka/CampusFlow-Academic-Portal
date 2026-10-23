<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

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
    <title>CAT & Final Scores — No Units</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/responsive.css"></head>
    <body>
    <?php include '../includes/header.php'; ?>
    <div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h1>CAT & Final Scores</h1>
        <div class="empty-state">
            <p>You have no units assigned. Ask the Principal to assign you to units first.</p>
        </div>
    </main>
    </div>
    </body></html>
    <?php
    exit;
}

$unit_id = (int)($_GET['unit'] ?? $_POST['unit_id'] ?? $units[0]['id']);
$owned = false;
foreach ($units as $u) if ((int)$u['id'] === $unit_id) { $owned = true; break; }
if (!$owned) $unit_id = (int)$units[0]['id'];

$unit = $conn->query("
    SELECT un.*, c.name AS course_name
    FROM units un JOIN courses c ON c.id = un.course_id
    WHERE un.id = $unit_id
")->fetch_assoc();

$msg = ''; $err = '';

$CAT_MAX = 30;
$FINAL_MAX = 70;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['scores'])) {
    $saved = 0; $skipped = 0;
    $conn->begin_transaction();
    try {
        foreach ($_POST['scores'] as $studentId => $vals) {
            $studentId = (int)$studentId;
            $cat1 = ($vals['cat1'] ?? '') !== '' ? (int)$vals['cat1'] : null;
            $cat2 = ($vals['cat2'] ?? '') !== '' ? (int)$vals['cat2'] : null;
            $final = ($vals['final_manual'] ?? '') !== '' ? (int)$vals['final_manual'] : null;

            if ($cat1 !== null && ($cat1 < 0 || $cat1 > $CAT_MAX)) { $skipped++; continue; }
            if ($cat2 !== null && ($cat2 < 0 || $cat2 > $CAT_MAX)) { $skipped++; continue; }
            if ($final !== null && ($final < 0 || $final > $FINAL_MAX)) { $skipped++; continue; }

            $stmt = $conn->prepare("
                INSERT INTO cat_scores (student_id, unit_id, cat1, cat2, final_manual, recorded_by)
                VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                    cat1 = VALUES(cat1),
                    cat2 = VALUES(cat2),
                    final_manual = VALUES(final_manual),
                    recorded_by = VALUES(recorded_by)
            ");
            $stmt->bind_param('iiiiii', $studentId, $unit_id, $cat1, $cat2, $final, $lid);
            $stmt->execute();
            $stmt->close();
            $saved++;
        }
        $conn->commit();
        $msg = "Saved $saved row(s)." . ($skipped ? " $skipped row(s) skipped due to invalid values." : '');
    } catch (Exception $e) {
        $conn->rollback();
        $err = 'Save failed: ' . $e->getMessage();
    }
}

$enrolled = $conn->query("
    SELECT u.id, u.full_name, u.admission_no,
           cs.cat1, cs.cat2, cs.final_manual,
           (SELECT MAX(a.score) FROM attempts a
            JOIN exams e ON e.id = a.exam_id
            WHERE e.unit_id = $unit_id AND a.student_id = u.id AND a.status != 'in_progress') AS online_score
    FROM unit_enrollments ue
    JOIN users u ON u.id = ue.student_id
    LEFT JOIN cat_scores cs ON cs.student_id = u.id AND cs.unit_id = ue.unit_id
    WHERE ue.unit_id = $unit_id
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CAT & Final Scores — <?= esc($unit['code'].' '.$unit['name']) ?></title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
<style>
    .unit-banner {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: var(--white);
        border-radius: var(--radius);
        padding: 20px 24px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        box-shadow: var(--shadow);
    }
    .unit-banner-left { display: flex; flex-direction: column; gap: 4px; }
    .unit-banner-code {
        display: inline-block;
        background: var(--accent);
        color: var(--primary);
        font-weight: 800;
        font-size: .8rem;
        letter-spacing: 1px;
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
        width: fit-content;
    }
    .unit-banner-title {
        font-size: clamp(1.1rem, 2.5vw, 1.35rem);
        font-weight: 800;
        color: var(--white);
        margin-top: 4px;
    }
    .unit-banner-course {
        font-size: .85rem;
        color: rgba(255,255,255,.85);
    }
    .unit-banner-stats {
        text-align: right;
        font-size: .82rem;
        color: rgba(255,255,255,.9);
    }
    .unit-banner-stats strong { color: var(--accent); font-size: 1rem; }

    .score-input {
        width: 90px;
        padding: 8px 10px;
        border: 2px solid var(--border);
        border-radius: 6px;
        font-size: .9rem;
        text-align: center;
        font-variant-numeric: tabular-nums;
        transition: border-color .15s;
    }
    .score-input:focus { outline: none; border-color: var(--accent); }
    .online-badge {
        display: inline-block;
        background: #e6f0fa;
        color: var(--primary-light);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: .7rem;
        font-weight: 700;
        margin-left: 6px;
    }
    .manual-badge {
        display: inline-block;
        background: #e6f5ea;
        color: var(--success);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: .7rem;
        font-weight: 700;
        margin-left: 6px;
    }
    .total-pill {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-weight: 800;
        font-size: .82rem;
        background: var(--primary);
        color: var(--white);
    }
    .score-table-wrap {
        background: var(--white);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .score-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .88rem;
    }
    .score-table thead { background: var(--primary); color: var(--white); }
    .score-table th {
        padding: 12px;
        text-align: left;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .score-table td {
        padding: 12px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }
    .score-table tr:last-child td { border-bottom: none; }
    .score-table tbody tr:hover td { background: #f8fbfd; }
    .save-bar {
        position: sticky;
        bottom: 0;
        background: var(--white);
        padding: 14px 20px;
        border-top: 2px solid var(--accent);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    @media (max-width: 700px) {
        .score-table { display: block; overflow-x: auto; white-space: nowrap; }
        .score-input { width: 70px; padding: 6px; font-size: .82rem; }
        .unit-banner { flex-direction: column; align-items: flex-start; }
        .unit-banner-stats { text-align: left; }
    }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>CAT & Final Scores</h1>
    <p class="subtitle">Record CAT 1, CAT 2, and the Final exam score. All marks feed directly into each student's transcript.</p>

    <form method="GET" class="card-form" style="max-width:500px;margin-bottom:20px;">
        <div class="form-group">
            <label>Select Unit</label>
            <select name="unit" onchange="this.form.submit()">
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $unit_id===$u['id']?'selected':'' ?>>
                        <?= esc($u['code'].' — '.$u['name'].' ('.$u['course_name'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- Prominent unit banner -->
    <div class="unit-banner">
        <div class="unit-banner-left">
            <span class="unit-banner-code">UNIT CODE: <?= esc($unit['code']) ?></span>
            <span class="unit-banner-title"><?= esc($unit['name']) ?></span>
            <span class="unit-banner-course"><?= esc($unit['course_name']) ?></span>
        </div>
        <div class="unit-banner-stats">
            <div><strong><?= count($enrolled) ?></strong> student(s) enrolled</div>
            <div>CAT 1 /30 · CAT 2 /30 · FINAL /70</div>
        </div>
    </div>

    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <div style="background:#e6f0fa;color:var(--primary-light);padding:12px 16px;border-radius:8px;border-left:4px solid var(--primary-light);margin-bottom:20px;font-size:.88rem;">
        <strong>Weighting:</strong> CAT 1 = 15% · CAT 2 = 15% · FINAL = 70% → Total = <strong>100%</strong>. 
        If a student took the online exam, leave FINAL blank and the system will use their best online attempt automatically. <br>
        <small>The transcript uses <strong>either</strong> the manual FINAL you enter here <strong>or</strong> the online exam result — whichever has marks.</small>
    </div>

    <?php if (empty($enrolled)): ?>
        <div class="empty-state">
            <p>No students enrolled in <strong><?= esc($unit['code'].' — '.$unit['name']) ?></strong> yet.</p>
            <p><a class="link-btn" href="enroll-students.php?unit=<?= $unit_id ?>">Enroll Students →</a></p>
        </div>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="unit_id" value="<?= $unit_id ?>">
        <div class="score-table-wrap">
            <table class="score-table">
                <thead>
                    <tr>
                        <th colspan="6" style="background:var(--primary-light);color:var(--white);font-size:.82rem;padding:10px 12px;">
                            <?= esc($unit['code']) ?> — <?= esc($unit['name']) ?> &nbsp;·&nbsp; 
                            <span style="color:var(--accent);font-weight:700;"><?= esc($unit['course_name']) ?></span>
                        </th>
                    </tr>
                    <tr>
                        <th>Student</th>
                        <th>Admission No</th>
                        <th>CAT 1 <small>(/30)</small></th>
                        <th>CAT 2 <small>(/30)</small></th>
                        <th>Final <small>(/70)</small></th>
                        <th>Total <small>(/100)</small></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($enrolled as $e):
                    $cat1Val = $e['cat1'] !== null ? (int)$e['cat1'] : '';
                    $cat2Val = $e['cat2'] !== null ? (int)$e['cat2'] : '';
                    $finalVal = $e['final_manual'] !== null ? (int)$e['final_manual'] : '';
                    $onlineVal = $e['online_score'] !== null ? (int)$e['online_score'] : null;

                    if ($finalVal !== '') {
                        $effectiveFinal = (int)$finalVal;
                        $finalSource = 'Manual';
                    } elseif ($onlineVal !== null) {
                        $effectiveFinal = (int)$onlineVal;
                        $finalSource = 'Online';
                    } else {
                        $effectiveFinal = null;
                        $finalSource = null;
                    }

                    $totalPct = null;
                    if ($cat1Val !== '' && $cat2Val !== '' && $effectiveFinal !== null) {
                        $cat1Pct = ((int)$cat1Val / 30) * 15;
                        $cat2Pct = ((int)$cat2Val / 30) * 15;
                        $finalPct = ($effectiveFinal / 70) * 70;
                        $totalPct = round($cat1Pct + $cat2Pct + $finalPct, 1);
                    }
                ?>
                <tr>
                    <td><strong><?= esc($e['full_name']) ?></strong></td>
                    <td><?= esc($e['admission_no']) ?></td>
                    <td>
                        <input type="number" class="score-input" name="scores[<?= $e['id'] ?>][cat1]"
                               min="0" max="30" value="<?= $cat1Val ?>">
                    </td>
                    <td>
                        <input type="number" class="score-input" name="scores[<?= $e['id'] ?>][cat2]"
                               min="0" max="30" value="<?= $cat2Val ?>">
                    </td>
                    <td>
                        <input type="number" class="score-input" name="scores[<?= $e['id'] ?>][final_manual]"
                               min="0" max="70" value="<?= $finalVal ?>">
                    </td>
                    <td>
                        <?php if ($totalPct !== null): ?>
                            <span class="total-pill"><?= $totalPct ?>/100</span>
                            <?php if ($finalSource === 'Manual'): ?>
                                <span class="manual-badge">Manual</span>
                            <?php elseif ($finalSource === 'Online'): ?>
                                <span class="online-badge">Online</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:var(--muted);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="save-bar">
            <span style="font-size:.82rem;color:var(--muted);">
                <strong><?= esc($unit['code']) ?></strong> · <?= count($enrolled) ?> student(s) · Changes saved together
            </span>
            <button type="submit" class="btn-primary">Save All Scores</button>
        </div>
    </form>
    <?php endif; ?>
</main>
</div>
</body>
</html>