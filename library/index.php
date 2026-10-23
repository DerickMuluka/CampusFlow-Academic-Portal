<?php
require_once '../config/db.php';
requireLogin();

$role = $_SESSION['role'];
$uid = (int)$_SESSION['user_id'];

$where = '';

if ($role === 'student') {
    $units = getStudentUnits($conn, $uid);
    $courseIds = [];
    $unitIds = [];
    foreach ($units as $u) {
        $unitIds[] = (int)$u['id'];
        if (!empty($u['course_id'])) $courseIds[] = (int)$u['course_id'];
    }
    $me = $conn->query("SELECT course_id FROM users WHERE id = $uid")->fetch_assoc();
    if ($me && $me['course_id']) $courseIds[] = (int)$me['course_id'];
    $courseIds = array_values(array_unique(array_filter($courseIds)));
    $unitIds   = array_values(array_unique(array_filter($unitIds)));

    if (empty($courseIds) && empty($unitIds)) {
        $where = "WHERE 1=0";
    } else {
        $parts = [];
        if ($courseIds) $parts[] = "r.course_id IN (" . implode(',', $courseIds) . ")";
        if ($unitIds)   $parts[] = "r.unit_id IN (" . implode(',', $unitIds) . ")";
        $where = "WHERE (" . implode(' OR ', $parts) . ")";
    }
} elseif ($role === 'lecturer') {
    $units = $conn->query("
        SELECT un.id, un.course_id FROM lecturer_units lu
        JOIN units un ON un.id = lu.unit_id
        WHERE lu.lecturer_id = $uid
    ")->fetch_all(MYSQLI_ASSOC);
    $courseIds = array_values(array_unique(array_filter(array_column($units, 'course_id'))));
    $unitIds   = array_values(array_unique(array_filter(array_column($units, 'id'))));

    if (empty($courseIds) && empty($unitIds)) {
        $where = "WHERE 1=0";
    } else {
        $parts = [];
        if ($courseIds) $parts[] = "r.course_id IN (" . implode(',', $courseIds) . ")";
        if ($unitIds)   $parts[] = "r.unit_id IN (" . implode(',', $unitIds) . ")";
        $where = "WHERE (" . implode(' OR ', $parts) . ")";
    }
}
// admin/principal see everything (no WHERE)

$resources = $conn->query("
    SELECT r.*, c.name AS course_name, un.code AS unit_code, un.name AS unit_name
    FROM library_resources r
    LEFT JOIN courses c ON c.id = r.course_id
    LEFT JOIN units un ON un.id = r.unit_id
    $where
    ORDER BY c.name, un.code, r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$grouped = [];
foreach ($resources as $r) {
    $key = $r['course_name'] ?? 'General Resources';
    if ($r['unit_code']) $key .= ' · ' . $r['unit_code'] . ' ' . $r['unit_name'];
    $grouped[$key][] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>eLibrary — Rifkins College</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
<style>
    .resource-card { background: var(--white); border-radius: var(--radius); padding: 18px 20px; margin-bottom: 12px; box-shadow: var(--shadow); border-left: 4px solid var(--accent); display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
    .resource-body { flex: 1; min-width: 220px; }
    .resource-body h4 { color: var(--primary); font-size: .98rem; margin-bottom: 6px; }
    .resource-body p { color: var(--muted); font-size: .85rem; line-height: 1.55; }
    .type-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: .68rem; font-weight: 800; text-transform: uppercase; background: var(--bg); color: var(--accent); margin-bottom: 8px; }
    .group-section { margin-bottom: 32px; }
    .group-title { color: var(--primary); font-size: 1.02rem; padding-bottom: 8px; border-bottom: 2px solid var(--accent); margin-bottom: 14px; display: inline-block; }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>eLibrary</h1>
    <p class="subtitle">
        <?php if ($role === 'student'): ?>Resources for your enrolled units and course.
        <?php elseif ($role === 'lecturer'): ?>All resources for the departments and units you teach.
        <?php else: ?>All resources across every department, course, and unit.
        <?php endif; ?>
    </p>

    <?php if (empty($grouped)): ?>
        <div class="empty-state"><p>No resources available yet. <?php if ($role === 'student'): ?>Ask your lecturer to enroll you or upload materials.<?php endif; ?></p></div>
    <?php else: ?>
        <?php foreach ($grouped as $groupTitle => $items): ?>
        <div class="group-section">
            <h3 class="group-title"><?= esc($groupTitle) ?></h3>
            <?php foreach ($items as $r): ?>
                <div class="resource-card">
                    <div class="resource-body">
                        <span class="type-badge"><?= esc($r['resource_type']) ?></span>
                        <h4><?= esc($r['title']) ?></h4>
                        <?php if ($r['description']): ?><p><?= esc($r['description']) ?></p><?php endif; ?>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
                        <?php if ($r['external_url']): ?>
                            <a class="link-btn" href="<?= esc($r['external_url']) ?>" target="_blank" rel="noopener">Open Resource →</a>
                        <?php elseif ($r['file_path']): ?>
                            <a class="link-btn" href="../uploads/<?= esc($r['file_path']) ?>" target="_blank">Download →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
</body>
</html>