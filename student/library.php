<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

$units = getStudentUnits($conn, $sid);
$unitIds = array_column($units, 'id');
$me = $conn->query("SELECT course_id FROM users WHERE id = $sid")->fetch_assoc();
$courseId = (int)($me['course_id'] ?? 0);

$where = [];
if ($courseId) $where[] = "r.course_id = $courseId";
if (!empty($unitIds)) $where[] = "r.unit_id IN (" . implode(',', array_map('intval', $unitIds)) . ")";
if (empty($where)) {
    $whereSql = "WHERE 1=0";
} else {
    $whereSql = "WHERE (" . implode(' OR ', $where) . ")";
}

$resources = $conn->query("
    SELECT r.*, c.name AS course_name, un.code AS unit_code, un.name AS unit_name
    FROM library_resources r
    LEFT JOIN courses c ON c.id = r.course_id
    LEFT JOIN units un ON un.id = r.unit_id
    $whereSql
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
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My eLibrary — Student</title>
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
    .group-title { color: var(--primary); font-size: 1.02rem; padding-bottom: 8px; border-bottom: 2px solid var(--accent); margin-bottom: 14px; display: inline-block; }
    .group-section { margin-bottom: 32px; }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>My eLibrary</h1>
    <p class="subtitle">Resources for your course and enrolled units.</p>

    <?php if (empty($grouped)): ?>
        <div class="empty-state"><p>No library resources for your course yet. Ask your lecturer to add materials.</p></div>
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
                    <div>
                        <?php if ($r['external_url']): ?>
                            <a class="link-btn" href="<?= esc($r['external_url']) ?>" target="_blank" rel="noopener">Open →</a>
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