<?php
require_once '../config/db.php';
requireRole('admin');

$action = $_GET['action'] ?? '';
$userFilter = (int)($_GET['user'] ?? 0);
$where = [];
if ($action !== '') { $where[] = "action = '" . $conn->real_escape_string($action) . "'"; }
if ($userFilter > 0) { $where[] = "user_id = $userFilter"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$logs = $conn->query("
    SELECT l.*, u.full_name
    FROM audit_log l
    LEFT JOIN users u ON u.id = l.user_id
    $whereSql
    ORDER BY l.created_at DESC
    LIMIT 500
")->fetch_all(MYSQLI_ASSOC);

$actions = $conn->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT id, full_name FROM users WHERE id IN (SELECT DISTINCT user_id FROM audit_log WHERE user_id IS NOT NULL) ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audit Log — Admin</title>
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
    <h1>Activity Monitor</h1>
    <p class="subtitle">Every action taken by every user in the institution.</p>

    <form method="GET" class="card-form" style="max-width:760px;margin-bottom:24px;">
        <div class="form-row">
            <div class="form-group">
                <label>Action</label>
                <select name="action" onchange="this.form.submit()">
                    <option value="">All actions</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= esc($a['action']) ?>" <?= $action===$a['action']?'selected':'' ?>><?= esc($a['action']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>User</label>
                <select name="user" onchange="this.form.submit()">
                    <option value="0">All users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $userFilter==$u['id']?'selected':'' ?>><?= esc($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <table class="data-table">
        <thead><tr><th>When</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td style="white-space:nowrap;"><?= esc($l['created_at']) ?></td>
                <td><?= esc($l['full_name'] ?? 'System') ?></td>
                <td><?= esc($l['user_role'] ?? '—') ?></td>
                <td><span class="badge badge-blue"><?= esc($l['action']) ?></span></td>
                <td><?= esc($l['details'] ?? '—') ?></td>
                <td style="font-family:monospace;font-size:.75rem;"><?= esc($l['ip_address'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</div>
</body>
</html>