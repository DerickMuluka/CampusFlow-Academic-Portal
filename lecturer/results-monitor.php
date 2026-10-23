<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

// Lecturer's units
$myUnits = $conn->query("
    SELECT un.*, c.name AS course_name
    FROM lecturer_units lu
    JOIN units un ON un.id = lu.unit_id
    JOIN courses c ON c.id = un.course_id
    WHERE lu.lecturer_id = $lid
    ORDER BY c.name, un.code
")->fetch_all(MYSQLI_ASSOC);

$unitFilter = (int)($_GET['unit'] ?? 0);
$search     = trim($_GET['q'] ?? '');

// Make sure the selected unit belongs to this lecturer
$allowedUnitIds = array_column($myUnits, 'id');
if ($unitFilter > 0 && !in_array($unitFilter, $allowedUnitIds)) $unitFilter = 0;

if (empty($myUnits)) {
    ?>
    <!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students' Results</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/results-monitor.css">
    <link rel="stylesheet" href="../assets/css/responsive.css"></head>
    <body>
    <?php include '../includes/header.php'; ?>
    <div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h1>My Students' Results</h1>
        <div class="empty-state">
            <p>You have no units assigned. Ask the Principal to assign you to units first.</p>
        </div>
    </main>
    </div></body></html>
    <?php
    exit;
}

$unitIdSql = $unitFilter > 0 ? $unitFilter : implode(',', array_map('intval', $allowedUnitIds));

$where = ["u.role = 'student'"];
if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $where[] = "(u.full_name LIKE '%$s%' OR u.admission_no LIKE '%$s%')";
}
$whereSql = implode(' AND ', $where);

$rows = $conn->query("
    SELECT u.id AS student_id, u.full_name, u.admission_no,
           c.name AS course_name, c.level AS course_level,
           un.id AS unit_id, un.code AS unit_code, un.name AS unit_name,
           cs.cat1, cs.cat2, cs.final_manual,
           (SELECT MAX(a.score) FROM attempts a
            JOIN exams e ON e.id = a.exam_id
            WHERE e.unit_id = un.id AND a.student_id = u.id AND a.status != 'in_progress') AS online_score
    FROM users u
    LEFT JOIN courses c ON c.id = u.course_id
    JOIN unit_enrollments ue ON ue.student_id = u.id AND ue.unit_id IN ($unitIdSql)
    JOIN units un ON un.id = ue.unit_id
    LEFT JOIN cat_scores cs ON cs.student_id = u.id AND cs.unit_id = un.id
    WHERE $whereSql
    ORDER BY un.code, u.full_name
")->fetch_all(MYSQLI_ASSOC);

function computePercent($cat1, $cat2, $final) {
    if ($cat1 === null || $cat2 === null || $final === null) return null;
    return round((($cat1 / 30) * 15) + (($cat2 / 30) * 15) + (($final / 70) * 70), 1);
}
function gradeFor($p) {
    if ($p === null) return ['—', 'grade-none'];
    if ($p >= 80) return ['A', 'grade-a'];
    if ($p >= 70) return ['B', 'grade-b'];
    if ($p >= 60) return ['C', 'grade-c'];
    if ($p >= 50) return ['D', 'grade-d'];
    if ($p >= 40) return ['E', 'grade-e'];
    return ['F', 'grade-f'];
}

// Group by unit
$groupedByUnit = [];
foreach ($rows as $r) {
    $manualFinal = $r['final_manual'] !== null ? (int)$r['final_manual'] : null;
    $onlineFinal = $r['online_score'] !== null ? (int)$r['online_score'] : null;
    if ($manualFinal !== null) { $final = $manualFinal; $src = 'Manual'; }
    elseif ($onlineFinal !== null) { $final = $onlineFinal; $src = 'Online'; }
    else { $final = null; $src = null; }

    $cat1 = $r['cat1'] !== null ? (int)$r['cat1'] : null;
    $cat2 = $r['cat2'] !== null ? (int)$r['cat2'] : null;
    $pct = computePercent($cat1, $cat2, $final);
    list($letter, $cls) = gradeFor($pct);

    $unitKey = $r['unit_id'];
    if (!isset($groupedByUnit[$unitKey])) {
        $groupedByUnit[$unitKey] = [
            'code' => $r['unit_code'],
            'name' => $r['unit_name'],
            'course' => $r['course_name'],
            'students' => [],
            'sum' => 0,
            'count' => 0,
        ];
    }
    $groupedByUnit[$unitKey]['students'][] = [
        'full_name' => $r['full_name'],
        'admission_no' => $r['admission_no'],
        'cat1' => $cat1, 'cat2' => $cat2,
        'final' => $final, 'finalSrc' => $src,
        'percent' => $pct, 'letter' => $letter, 'cls' => $cls,
    ];
    if ($pct !== null) {
        $groupedByUnit[$unitKey]['sum'] += $pct;
        $groupedByUnit[$unitKey]['count']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Students' Results — Lecturer</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/results-monitor.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="monitor-header">
        <div>
            <h1>My Students' Results</h1>
            <p class="subtitle">All results for the units you teach — unit by unit.</p>
        </div>
    </div>

    <form method="GET" class="monitor-filter">
        <div class="form-group">
            <label>Unit</label>
            <select name="unit" onchange="this.form.submit()">
                <option value="0">All My Units</option>
                <?php foreach ($myUnits as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $unitFilter==$u['id']?'selected':'' ?>>
                        <?= esc($u['code'].' — '.$u['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Search Student</label>
            <input type="text" name="q" value="<?= esc($search) ?>" placeholder="Name or admission no">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;">
            <button type="submit" class="btn-primary" style="width:100%;">Apply</button>
        </div>
    </form>

    <?php if (empty($groupedByUnit)): ?>
        <div class="empty-state"><p>No enrolled students or no recorded scores match the current filters.</p></div>
    <?php else: ?>
        <?php foreach ($groupedByUnit as $unitData):
            $unitAvg = $unitData['count'] > 0 ? round($unitData['sum'] / $unitData['count'], 1) : null;
        ?>
        <div class="course-group">
            <div class="course-group-header">
                <h3><span style="color:var(--accent);"><?= esc($unitData['code']) ?></span> — <?= esc($unitData['name']) ?></h3>
                <div class="course-meta">
                    <?= esc($unitData['course']) ?> · <?= count($unitData['students']) ?> student(s) · Unit average: <?= $unitAvg !== null ? $unitAvg . '%' : '—' ?>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>Student</th><th>Adm No</th>
                            <th>CAT 1<br><small>(/30)</small></th><th>CAT 2<br><small>(/30)</small></th>
                            <th>FINAL<br><small>(/70)</small></th><th>Average<br><small>(/100)</small></th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($unitData['students'] as $s): ?>
                        <tr>
                            <td><strong><?= esc($s['full_name']) ?></strong></td>
                            <td><?= esc($s['admission_no']) ?></td>
                            <td class="num-cell"><?= $s['cat1'] !== null ? $s['cat1'] : '—' ?></td>
                            <td class="num-cell"><?= $s['cat2'] !== null ? $s['cat2'] : '—' ?></td>
                            <td class="num-cell">
                                <?= $s['final'] !== null ? $s['final'] : '—' ?>
                                <?php if ($s['finalSrc']): ?><small></small><?php endif; ?>
                            </td>
                            <td class="num-cell avg-cell"><?= $s['percent'] !== null ? $s['percent'] . '%' : '—' ?></td>
                            <td class="num-cell"><span class="grade-pill <?= $s['cls'] ?>"><?= $s['letter'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
</body>
</html>