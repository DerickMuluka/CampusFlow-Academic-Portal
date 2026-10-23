<?php
require_once '../config/db.php';
requireRole('admin');

// Filters
$courseFilter = (int)($_GET['course'] ?? 0);
$unitFilter   = (int)($_GET['unit'] ?? 0);
$search       = trim($_GET['q'] ?? '');

$courses = $conn->query("SELECT c.*, d.name AS dept FROM courses c JOIN departments d ON d.id=c.department_id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
$units = $courseFilter > 0
    ? $conn->query("SELECT * FROM units WHERE course_id = $courseFilter ORDER BY code")->fetch_all(MYSQLI_ASSOC)
    : $conn->query("SELECT * FROM units ORDER BY code")->fetch_all(MYSQLI_ASSOC);

// Build query for all students + their unit results
$where = ["u.role = 'student'"];
if ($courseFilter > 0) $where[] = "u.course_id = $courseFilter";
if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $where[] = "(u.full_name LIKE '%$s%' OR u.admission_no LIKE '%$s%')";
}
$whereSql = implode(' AND ', $where);

$unitJoin = $unitFilter > 0 ? " AND un.id = $unitFilter" : "";

$rows = $conn->query("
    SELECT u.id AS student_id, u.full_name, u.admission_no, u.course_id,
           c.name AS course_name, c.level AS course_level, d.name AS dept_name,
           un.id AS unit_id, un.code AS unit_code, un.name AS unit_name,
           cs.cat1, cs.cat2, cs.final_manual,
           (SELECT MAX(a.score) FROM attempts a
            JOIN exams e ON e.id = a.exam_id
            WHERE e.unit_id = un.id AND a.student_id = u.id AND a.status != 'in_progress') AS online_score
    FROM users u
    LEFT JOIN courses c ON c.id = u.course_id
    LEFT JOIN departments d ON d.id = c.department_id
    JOIN units un ON un.course_id = u.course_id $unitJoin
    LEFT JOIN cat_scores cs ON cs.student_id = u.id AND cs.unit_id = un.id
    WHERE $whereSql
    ORDER BY c.name, u.full_name, un.code
")->fetch_all(MYSQLI_ASSOC);

// Helper: compute weighted percent
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

// Group results by student + course
$grouped = []; // [courseName][studentId] => ['info', 'units' => [...]]
$totalsByCourse = [];
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

    $courseName = $r['course_name'] ?? 'No Course';
    $key = $r['student_id'];
    if (!isset($grouped[$courseName][$key])) {
        $grouped[$courseName][$key] = [
            'info' => $r,
            'units' => [],
            'sum' => 0,
            'count' => 0,
        ];
    }
    $grouped[$courseName][$key]['units'][] = [
        'code' => $r['unit_code'],
        'name' => $r['unit_name'],
        'cat1' => $cat1, 'cat2' => $cat2,
        'final' => $final, 'finalSrc' => $src,
        'percent' => $pct, 'letter' => $letter, 'cls' => $cls,
    ];
    if ($pct !== null) {
        $grouped[$courseName][$key]['sum'] += $pct;
        $grouped[$courseName][$key]['count']++;
    }
}

// Grand stats
$totalStudents = count(array_unique(array_column($rows, 'student_id')));
$totalCourses = count($grouped);
$allPercents = [];
foreach ($grouped as $students) {
    foreach ($students as $s) {
        if ($s['count'] > 0) $allPercents[] = $s['sum'] / $s['count'];
    }
}
$avgAll = count($allPercents) ? round(array_sum($allPercents) / count($allPercents), 1) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Student Results — Admin</title>
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
            <h1>All Student Results</h1>
            <p class="subtitle">Every student, every course, every unit — full breakdown.</p>
        </div>
    </div>

    <form method="GET" class="monitor-filter">
        <div class="form-group">
            <label>Course</label>
            <select name="course" onchange="this.form.submit()">
                <option value="0">All Courses</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $courseFilter==$c['id']?'selected':'' ?>>
                        <?= esc($c['name']) ?> (<?= esc($c['level']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Unit</label>
            <select name="unit">
                <option value="0">All Units</option>
                <?php foreach ($units as $u): ?>
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

    <div class="monitor-summary">
        <div class="stat-card"><span class="stat-num"><?= $totalStudents ?></span><span>Students</span></div>
        <div class="stat-card"><span class="stat-num"><?= $totalCourses ?></span><span>Courses</span></div>
        <div class="stat-card"><span class="stat-num"><?= $avgAll !== null ? $avgAll . '%' : '—' ?></span><span>Overall Average</span></div>
    </div>

    <?php if (empty($grouped)): ?>
        <div class="empty-state"><p>No results match the current filters.</p></div>
    <?php else: ?>
        <?php foreach ($grouped as $courseName => $students):
            $courseStats = [];
            foreach ($students as $s) if ($s['count'] > 0) $courseStats[] = $s['sum'] / $s['count'];
            $courseAvg = count($courseStats) ? round(array_sum($courseStats) / count($courseStats), 1) : null;
        ?>
        <div class="course-group">
            <div class="course-group-header">
                <h3><?= esc($courseName) ?></h3>
                <div class="course-meta">
                    <?= count($students) ?> student(s) · Course average: <?= $courseAvg !== null ? $courseAvg . '%' : '—' ?>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Adm No</th>
                            <th>Unit Code</th>
                            <th>Unit Title</th>
                            <th>CAT 1<br><small>(/30)</small></th>
                            <th>CAT 2<br><small>(/30)</small></th>
                            <th>FINAL<br><small>(/70)</small></th>
                            <th>Average<br><small>(/100)</small></th>
                            <th>Grade</th>
                            <th>Student Avg</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $s):
                        $studentPct = $s['count'] > 0 ? round($s['sum'] / $s['count'], 1) : null;
                        list($sLetter, $sCls) = gradeFor($studentPct);
                        $firstUnit = true;
                        foreach ($s['units'] as $u):
                            $rowspan = count($s['units']);
                    ?>
                        <tr>
                            <?php if ($firstUnit): ?>
                                <td rowspan="<?= $rowspan ?>"><strong><?= esc($s['info']['full_name']) ?></strong></td>
                                <td rowspan="<?= $rowspan ?>"><?= esc($s['info']['admission_no']) ?></td>
                            <?php endif; ?>
                            <td class="unit-code-cell"><strong><?= esc($u['code']) ?></strong></td>
                            <td><?= esc($u['name']) ?></td>
                            <td class="num-cell"><?= $u['cat1'] !== null ? $u['cat1'] : '—' ?></td>
                            <td class="num-cell"><?= $u['cat2'] !== null ? $u['cat2'] : '—' ?></td>
                            <td class="num-cell">
                                <?= $u['final'] !== null ? $u['final'] : '—' ?>
                                <?php if ($u['finalSrc']): ?><small>(<?= $u['finalSrc'] ?>)</small><?php endif; ?>
                            </td>
                            <td class="num-cell avg-cell"><?= $u['percent'] !== null ? $u['percent'] . '%' : '—' ?></td>
                            <td class="num-cell"><span class="grade-pill <?= $u['cls'] ?>"><?= $u['letter'] ?></span></td>
                            <?php if ($firstUnit): ?>
                                <td rowspan="<?= $rowspan ?>" class="num-cell">
                                    <?= $studentPct !== null ? $studentPct . '%' : '—' ?><br>
                                    <span class="grade-pill <?= $sCls ?>"><?= $sLetter ?></span>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php $firstUnit = false; endforeach; ?>
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