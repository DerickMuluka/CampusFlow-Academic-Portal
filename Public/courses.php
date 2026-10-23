<?php
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Our Courses — Rifkins College';

// Fetch departments with their courses
$departments = $conn->query("
    SELECT d.id AS dept_id, d.name AS dept_name, d.code AS dept_code,
           c.id AS course_id, c.name AS course_name, c.code AS course_code,
           c.level, c.duration, c.exam_body
    FROM departments d
    LEFT JOIN courses c ON c.department_id = d.id
    ORDER BY d.name, c.name
")->fetch_all(MYSQLI_ASSOC);

// Group by department
$grouped = [];
foreach ($departments as $row) {
    $dept = $row['dept_name'];
    if (!isset($grouped[$dept])) {
        $grouped[$dept] = [
            'code' => $row['dept_code'],
            'courses' => [],
        ];
    }
    if ($row['course_id']) {
        $grouped[$dept]['courses'][] = $row;
    }
}

// Entry requirements and extra info by course code (kept as reference map — extend as needed)
$entryGrades = [
    'ACC-CERT' => 'D plain in KCSE',
    'ACC-DIP'  => 'D+ in KCSE',
    'HOS-DIP'  => 'D+ in KCSE',
    'TRT-CERT' => 'D plain in KCSE',
    'TRT-DIP'  => 'D+ in KCSE',
    'BEA-DIP'  => 'D plain in KCSE',
    'ICT-DIP'  => 'D+ in KCSE',
    'SHL-DIP'  => 'D+ in KCSE',
    'SWK-DIP'  => 'C- in KCSE',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<meta name="description" content="Diploma and Certificate courses at Rifkins College Mombasa — Accounting, Tourism, Hospitality, IT, Beauty, Logistics, Social Work, Business, Languages.">
<link rel="stylesheet" href="../assets/css/public.css">
<style>
    .course-group { margin-bottom: 48px; }
    .course-group h3.group-title {
        color: var(--primary);
        font-family: var(--font-display);
        font-size: 1.35rem;
        padding-bottom: 10px;
        border-bottom: 3px solid var(--accent);
        margin-bottom: 22px;
        display: inline-block;
    }
    .course-toc {
        background: var(--white);
        border-radius: var(--radius);
        padding: 20px 26px;
        box-shadow: var(--shadow);
        margin-bottom: 40px;
        border-left: 4px solid var(--accent);
    }
    .course-toc h4 { color: var(--primary); margin-bottom: 10px; font-size: .92rem; }
    .course-toc ul { list-style: none; display: flex; flex-wrap: wrap; gap: 8px 16px; }
    .course-toc a { color: var(--primary-light); font-weight: 600; font-size: .85rem; text-decoration: none; }
    .course-toc a:hover { color: var(--accent); }
    .dept-anchor { scroll-margin-top: 100px; }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/public-header.php'; ?>

<section class="pub-section" style="padding-top:120px;">
    <div class="container">
        <div class="pub-section-header">
            <span class="pub-section-eyebrow">Our Courses</span>
            <h2>Marketable Diploma & Certificate Courses</h2>
            <p class="lead">Study on campus in Mombasa or online from anywhere in the world. UK and local qualifications accredited by ICM (UK), ABMA (UK), KNEC, and CSK.</p>
        </div>

        <?php if (empty($grouped)): ?>
            <div class="empty-state" style="background:var(--white);padding:40px;text-align:center;border-radius:var(--radius);box-shadow:var(--shadow);">
                <p style="color:var(--muted);">Course listings are being updated. Please check back soon or contact <strong>0715 043739</strong> for current programmes.</p>
            </div>
        <?php else: ?>
            <div class="course-toc">
                <h4>Jump to Department</h4>
                <ul>
                    <?php foreach ($grouped as $deptName => $info): ?>
                        <?php if (empty($info['courses'])) continue; ?>
                        <li>
                            <a href="#dept-<?= (int)preg_replace('/[^0-9]/', '', md5($deptName)) ?>">
                                <?= htmlspecialchars($deptName) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php foreach ($grouped as $deptName => $info): ?>
                <?php if (empty($info['courses'])) continue; ?>
                <div class="course-group dept-anchor" id="dept-<?= (int)preg_replace('/[^0-9]/', '', md5($deptName)) ?>">
                    <h3 class="group-title"><?= htmlspecialchars($deptName) ?></h3>
                    <div class="pub-courses">
                        <?php foreach ($info['courses'] as $c): ?>
                            <div class="pub-course-card">
                                <div class="pub-course-body">
                                    <span class="pub-course-tag">
                                        <?= htmlspecialchars($c['level']) ?>
                                        <?php if ($c['exam_body']): ?> · <?= htmlspecialchars($c['exam_body']) ?><?php endif; ?>
                                    </span>
                                    <h3><?= htmlspecialchars($c['course_name']) ?></h3>
                                    <div class="pub-course-meta">
                                        <?php if ($c['duration']): ?>
                                            <span><strong>Duration:</strong> <?= htmlspecialchars($c['duration']) ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($entryGrades[$c['course_code']])): ?>
                                            <span><strong>Entry:</strong> <?= htmlspecialchars($entryGrades[$c['course_code']]) ?></span>
                                        <?php endif; ?>
                                        <?php if ($c['exam_body']): ?>
                                            <span><strong>Exam Body:</strong> <?= htmlspecialchars($c['exam_body']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="apply.php" class="pub-course-link">Apply →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="pub-feature" style="margin-top:50px;background:var(--bg-light);border-left:4px solid var(--primary);">
            <h3>Not sure which course is right for you?</h3>
            <p>Call, SMS, or WhatsApp <strong>0715 043739</strong>. Our admissions team will help you choose the right programme based on your KCSE grade, interests, and career goals.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>