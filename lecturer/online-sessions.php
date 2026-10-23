<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

$units = $conn->query("
    SELECT un.* FROM lecturer_units lu
    JOIN units un ON un.id = lu.unit_id
    WHERE lu.lecturer_id = $lid ORDER BY un.name
")->fetch_all(MYSQLI_ASSOC);

if (empty($units)) {
    ?>
    <!DOCTYPE html><html><head><meta charset="UTF-8"><title>Online Sessions</title>
    <link rel="stylesheet" href="../assets/css/base.css"><link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css"><link rel="stylesheet" href="../assets/css/responsive.css"></head>
    <body><?php include '../includes/header.php'; ?><div class="app-layout"><?php include '../includes/sidebar.php'; ?>
    <main class="main-content"><h1>Online Sessions</h1><div class="empty-state"><p>No units assigned.</p></div></main></div></body></html>
    <?php
    exit;
}

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $unitId = (int)$_POST['unit_id'];
    $title = trim($_POST['title'] ?? '');
    $platform = $_POST['platform'] ?? 'zoom';
    $joinUrl = trim($_POST['join_url'] ?? '');
    $meetingId = trim($_POST['meeting_id'] ?? '');
    $passcode = trim($_POST['passcode'] ?? '');
    $scheduledAt = $_POST['scheduled_at'] ?? '';
    $duration = (int)($_POST['duration_minutes'] ?? 90);
    $desc = trim($_POST['description'] ?? '');

    if (!lecturerOwnsUnit($conn, $lid, $unitId)) {
        $err = 'You do not teach that unit.';
    } elseif ($title === '' || $joinUrl === '' || $scheduledAt === '') {
        $err = 'Title, join URL, and scheduled time are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO online_sessions (unit_id, lecturer_id, title, platform, join_url, meeting_id, passcode, scheduled_at, duration_minutes, description) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iissssssis', $unitId, $lid, $title, $platform, $joinUrl, $meetingId, $passcode, $scheduledAt, $duration, $desc);
        if ($stmt->execute()) {
            logAction($conn, 'created_online_session', 'session', $conn->insert_id, "Created: $title");
            $msg = 'Session created.';
        } else {
            $err = 'Could not save session.';
        }
        $stmt->close();
    }
}

if (isset($_GET['toggle'])) {
    $sid = (int)$_GET['toggle'];
    $chk = $conn->query("SELECT lecturer_id FROM online_sessions WHERE id = $sid")->fetch_assoc();
    if ($chk && (int)$chk['lecturer_id'] === $lid) {
        $conn->query("UPDATE online_sessions SET is_active = 1 - is_active WHERE id = $sid");
    }
    redirect('online-sessions.php');
}
if (isset($_GET['delete'])) {
    $sid = (int)$_GET['delete'];
    $chk = $conn->query("SELECT lecturer_id FROM online_sessions WHERE id = $sid")->fetch_assoc();
    if ($chk && (int)$chk['lecturer_id'] === $lid) {
        $conn->query("DELETE FROM online_sessions WHERE id = $sid");
        logAction($conn, 'deleted_online_session', 'session', $sid);
    }
    redirect('online-sessions.php');
}

$unitFilter = (int)($_GET['unit'] ?? 0);
$where = "WHERE lecturer_id = $lid";
if ($unitFilter > 0) $where .= " AND unit_id = $unitFilter";

$sessions = $conn->query("
    SELECT s.*, un.name AS unit_name, un.code AS unit_code,
           (SELECT COUNT(*) FROM session_attendance sa WHERE sa.session_id = s.id) AS attendance_count
    FROM online_sessions s
    JOIN units un ON un.id = s.unit_id
    $where ORDER BY s.scheduled_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Sessions — Lecturer</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
<style>
    .session-card { background: var(--white); border-radius: var(--radius); padding: 22px; margin-bottom: 16px; box-shadow: var(--shadow); border-left: 4px solid var(--accent); }
    .platform-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; margin-left: 8px; }
    .platform-zoom { background: #e6f0fa; color: #0c4a6e; }
    .platform-google_meet { background: #e6f5ea; color: #166534; }
    .platform-teams { background: #ede9fe; color: #5b21b6; }
    .platform-jitsi { background: #fef3c7; color: #854d0e; }
    .platform-other { background: #eef1f4; color: var(--muted); }
    .session-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); align-items: center; }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Online Sessions</h1>
    <p class="subtitle">Schedule Zoom, Google Meet, or Teams classes for your units.</p>

    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <form method="POST" class="card-form" style="max-width:900px;margin-bottom:30px;">
        <input type="hidden" name="action" value="create">
        <h2>Create New Session</h2>
        <div class="form-row">
            <div class="form-group">
                <label>Unit</label>
                <select name="unit_id" required>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= esc($u['code'].' — '.$u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Platform</label>
                <select name="platform">
                    <option value="zoom">Zoom</option>
                    <option value="google_meet">Google Meet</option>
                    <option value="teams">Microsoft Teams</option>
                    <option value="jitsi">Jitsi Meet</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Session Title</label><input type="text" name="title" required placeholder="e.g. Week 5 — Trial Balance"></div>
        <div class="form-group"><label>Join URL</label><input type="url" name="join_url" required placeholder="https://zoom.us/j/123456789"></div>
        <div class="form-row">
            <div class="form-group"><label>Meeting ID (optional)</label><input type="text" name="meeting_id"></div>
            <div class="form-group"><label>Passcode (optional)</label><input type="text" name="passcode"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Scheduled At</label><input type="datetime-local" name="scheduled_at" required></div>
            <div class="form-group"><label>Duration (min)</label><input type="number" name="duration_minutes" value="90" min="15" required></div>
        </div>
        <div class="form-group"><label>Description (optional)</label><textarea name="description" rows="2"></textarea></div>
        <button type="submit" class="btn-primary">Create Session</button>
    </form>

    <h2>Existing Sessions (<?= count($sessions) ?>)</h2>
    <form method="GET" style="max-width:400px;margin-bottom:20px;">
        <select name="unit" onchange="this.form.submit()" style="padding:8px 12px;border:2px solid var(--border);border-radius:8px;width:100%;">
            <option value="0">All units</option>
            <?php foreach ($units as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $unitFilter==$u['id']?'selected':'' ?>><?= esc($u['code'].' — '.$u['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (empty($sessions)): ?>
        <div class="empty-state"><p>No sessions created yet.</p></div>
    <?php else: ?>
        <?php foreach ($sessions as $s): ?>
        <div class="session-card">
            <div class="q-header" style="margin-bottom:10px;">
                <div>
                    <strong style="color:var(--primary);"><?= esc($s['title']) ?></strong>
                    <span class="platform-badge platform-<?= esc($s['platform']) ?>"><?= str_replace('_',' ',esc($s['platform'])) ?></span>
                    <span class="badge badge-<?= $s['is_active']?'green':'grey' ?>" style="margin-left:6px;"><?= $s['is_active']?'Active':'Inactive' ?></span>
                </div>
            </div>
            <p class="text-muted" style="font-size:.85rem;">
                <?= esc($s['unit_code'].' — '.$s['unit_name']) ?> · <?= esc($s['scheduled_at']) ?> · <?= (int)$s['duration_minutes'] ?> min
            </p>
            <?php if ($s['description']): ?><p style="font-size:.88rem;margin-top:8px;"><?= nl2br(esc($s['description'])) ?></p><?php endif; ?>
            <p style="margin-top:10px;">
                <a class="link-btn" href="<?= esc($s['join_url']) ?>" target="_blank" rel="noopener">🔗 Join Link</a>
                <?php if ($s['meeting_id']): ?><span class="text-muted" style="margin-left:12px;">ID: <?= esc($s['meeting_id']) ?></span><?php endif; ?>
                <?php if ($s['passcode']): ?><span class="text-muted" style="margin-left:12px;">Pass: <?= esc($s['passcode']) ?></span><?php endif; ?>
            </p>

            <!-- Action row with Attendance link placed here -->
            <div class="session-actions">
                <a class="link-btn" href="session-attendance.php?session=<?= $s['id'] ?>">
                    📋 Attendance (<?= (int)$s['attendance_count'] ?>)
                </a>
                <a class="link-btn" href="?toggle=<?= $s['id'] ?>"><?= $s['is_active']?'Deactivate':'Activate' ?></a>
                <a class="link-btn" href="?delete=<?= $s['id'] ?>" onclick="return confirm('Delete this session?')" style="color:#c0392b;">Delete</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
</body>
</html>