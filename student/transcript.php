<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

$student = $conn->query("
    SELECT u.*, c.name AS course_name, c.level AS course_level, d.name AS dept_name
    FROM users u
    LEFT JOIN courses c ON c.id = u.course_id
    LEFT JOIN departments d ON d.id = c.department_id
    WHERE u.id = $sid
")->fetch_assoc();

$course_id = (int)($student['course_id'] ?? 0);
$academicYear = $student['academic_year'] ?: (date('Y') . '-' . (date('Y') + 1));

$rows = [];
if ($course_id > 0) {
    $rows = $conn->query("
        SELECT un.id, un.code, un.name,
               cs.cat1, cs.cat2, cs.final_manual,
               (SELECT MAX(a.score) FROM attempts a
                JOIN exams e ON e.id = a.exam_id
                WHERE e.unit_id = un.id AND a.student_id = $sid AND a.status != 'in_progress') AS online_score
        FROM units un
        LEFT JOIN cat_scores cs ON cs.unit_id = un.id AND cs.student_id = $sid
        WHERE un.course_id = $course_id
        ORDER BY un.code
    ")->fetch_all(MYSQLI_ASSOC);
}

function gradeFromPercent($percent) {
    if ($percent === null) return ['—', 'grade-none'];
    if ($percent >= 80) return ['A', 'grade-a'];
    if ($percent >= 70) return ['B', 'grade-b'];
    if ($percent >= 60) return ['C', 'grade-c'];
    if ($percent >= 50) return ['D', 'grade-d'];
    if ($percent >= 40) return ['E', 'grade-e'];
    return ['F', 'grade-f'];
}

$totalPercent = 0;
$countedUnits = 0;
$processed = [];

foreach ($rows as $r) {
    $cat1 = $r['cat1'] !== null ? (int)$r['cat1'] : null;
    $cat2 = $r['cat2'] !== null ? (int)$r['cat2'] : null;
    $manualFinal = $r['final_manual'] !== null ? (int)$r['final_manual'] : null;
    $onlineFinal = $r['online_score'] !== null ? (int)$r['online_score'] : null;

    // ONLY ONE source is used — manual takes precedence
    if ($manualFinal !== null) {
        $final = $manualFinal;
        $finalSource = 'Manual';
    } elseif ($onlineFinal !== null) {
        $final = $onlineFinal;
        $finalSource = 'Online';
    } else {
        $final = null;
        $finalSource = null;
    }

    // Weighted percentage out of 100
    $percent = null;
    if ($cat1 !== null && $cat2 !== null && $final !== null) {
        $cat1Pct = ($cat1 / 30) * 15;
        $cat2Pct = ($cat2 / 30) * 15;
        $finalPct = ($final / 70) * 70;
        $percent = round($cat1Pct + $cat2Pct + $finalPct, 1);
    } elseif (($cat1 !== null || $cat2 !== null) && $final === null) {
        $cat1Pct = $cat1 !== null ? ($cat1 / 30) * 15 : 0;
        $cat2Pct = $cat2 !== null ? ($cat2 / 30) * 15 : 0;
        $percent = round($cat1Pct + $cat2Pct, 1);
    } elseif ($final !== null) {
        $percent = round(($final / 70) * 70, 1);
    }

    list($letter, $gradeClass) = gradeFromPercent($percent);

    if ($percent !== null) {
        $totalPercent += $percent;
        $countedUnits++;
    }

    $processed[] = [
        'code' => $r['code'],
        'name' => $r['name'],
        'cat1' => $cat1,
        'cat2' => $cat2,
        'final' => $final,
        'finalSource' => $finalSource,
        'percent' => $percent,
        'letter' => $letter,
        'gradeClass' => $gradeClass,
    ];
}

$overallAvg = $countedUnits > 0 ? round($totalPercent / $countedUnits, 1) : null;
list($overallLetter, $overallClass) = gradeFromPercent($overallAvg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Academic Transcript — Rifkins College</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/transcript.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="transcript-header">
        <div class="transcript-brand">
            <h1>RIFKINS COLLEGE</h1>
            <p class="transcript-tag">Academic Transcript</p>
            <span class="accred-badge">TVETA/PRIVATE/TVC/0005/2022</span>
        </div>
        <div class="transcript-actions">
            <button class="btn-print" onclick="window.print()">🖨 Print</button>
            <a class="link-btn" href="dashboard.php">← Back</a>
        </div>
    </div>

    <div class="student-info-card">
        <div class="info-row">
            <div class="info-block">
                <span class="info-label">Admission Number</span>
                <span class="info-value"><?= esc($student['admission_no'] ?? '—') ?></span>
            </div>
            <div class="info-block">
                <span class="info-label">Full Name</span>
                <span class="info-value"><?= esc($student['full_name']) ?></span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-block">
                <span class="info-label">Academic Year</span>
                <span class="info-value"><?= esc($academicYear) ?></span>
            </div>
            <div class="info-block">
                <span class="info-label">Course</span>
                <span class="info-value">
                    <?= esc($student['course_name'] ?? 'Not assigned') ?>
                    <?php if ($student['course_level']): ?>
                        <span class="level-badge"><?= esc($student['course_level']) ?></span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php if ($student['dept_name']): ?>
        <div class="info-row">
            <div class="info-block">
                <span class="info-label">Department</span>
                <span class="info-value"><?= esc($student['dept_name']) ?></span>
            </div>
            <div class="info-block">
                <span class="info-label">Status</span>
                <span class="info-value badge badge-green">Active</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($processed)): ?>
        <div class="empty-state">
            <p>No units found for your course yet. Once your lecturer enrolls you and records CAT or exam scores, they will appear here.</p>
        </div>
    <?php else: ?>

    <div class="transcript-summary">
        <div class="summary-card">
            <span class="summary-label">Units Recorded</span>
            <span class="summary-value"><?= $countedUnits ?> / <?= count($processed) ?></span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Overall Average</span>
            <span class="summary-value"><?= $overallAvg !== null ? $overallAvg . '%' : '—' ?></span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Overall Grade</span>
            <span class="summary-value grade-pill <?= $overallClass ?>"><?= $overallLetter ?></span>
        </div>
    </div>

    <div class="transcript-table-wrap">
        <table class="transcript-table">
            <thead>
                <tr>
                    <th>Unit Code</th>
                    <th>Unit Title</th>
                    <th>CAT 1<br><small>(/30)</small></th>
                    <th>CAT 2<br><small>(/30)</small></th>
                    <th>FINAL<br><small>(/70)</small></th>
                    <th>Average<br><small>(/100)</small></th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($processed as $p): ?>
                <tr>
                    <td class="unit-code-cell"><strong><?= esc($p['code']) ?></strong></td>
                    <td><?= esc($p['name']) ?></td>
                    <td class="num-cell"><?= $p['cat1'] !== null ? $p['cat1'] : '—' ?></td>
                    <td class="num-cell"><?= $p['cat2'] !== null ? $p['cat2'] : '—' ?></td>
                    <td class="num-cell">
                        <?= $p['final'] !== null ? $p['final'] : '—' ?>
                        <?php if ($p['finalSource']): ?>
                            <small class="final-max"></small>
                        <?php endif; ?>
                    </td>
                    <td class="num-cell avg-cell"><?= $p['percent'] !== null ? $p['percent'] . '%' : '—' ?></td>
                    <td class="num-cell">
                        <span class="grade-pill <?= $p['gradeClass'] ?>"><?= $p['letter'] ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="tfoot-label">Overall Average</td>
                    <td class="num-cell avg-cell"><?= $overallAvg !== null ? $overallAvg . '%' : '—' ?></td>
                    <td class="num-cell"><span class="grade-pill <?= $overallClass ?>"><?= $overallLetter ?></span></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="transcript-footnote">
        <p><strong>Grading Scale:</strong> A = 80+ · B = 70–79 · C = 60–69 · D = 50–59 · E = 40–49 · F = below 40</p>
        <p><strong>Calculation:</strong> Unit total = 100%. CAT 1 (/30) × 15% + CAT 2 (/30) × 15% + FINAL (/70) × 70% = 100%. FINAL uses <strong>either</strong> the manual (physical exam) <strong>or</strong> online exam score — the one with recorded marks.</p>
        <p class="print-date">Generated on <?= date('d M Y, H:i') ?> · Rifkins College · info@rifkinscollege.com · 0715 043739</p>
    </div>

    <?php endif; ?>
</main>
</div>
</body>
</html>