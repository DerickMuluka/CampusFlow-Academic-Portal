<?php
require_once '../config/db.php';
requireRole('student');
$sid = (int)$_SESSION['user_id'];

// Auto-mark attendance when a session is joined
if (isset($_GET['join'])) {
    $sessionId = (int)$_GET['join'];
    $chk = $conn->prepare("
        SELECT s.id FROM online_sessions s
        JOIN unit_enrollments ue ON ue.unit_id = s.unit_id
        WHERE s.id = ? AND ue.student_id = ? AND s.is_active = 1
    ");
    $chk->bind_param('ii', $sessionId, $sid);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) {
        $ins = $conn->prepare("INSERT INTO session_attendance (session_id, student_id) VALUES (?,?) ON DUPLICATE KEY UPDATE joined_at = NOW()");
        $ins->bind_param('ii', $sessionId, $sid);
        $ins->execute();
        $ins->close();
        logAction($conn, 'joined_session', 'session', $sessionId);
    }
    $chk->close();
    $url = $conn->query("SELECT join_url FROM online_sessions WHERE id = $sessionId")->fetch_assoc();
    if ($url) {
        header('Location: ' . $url['join_url']);
        exit;
    }
    redirect('online-sessions.php');
}

$units = getStudentUnits($conn, $sid);
$unitIds = !empty($units) ? implode(',', array_map('intval', array_column($units, 'id'))) : '0';

$sessions = $conn->query("
    SELECT s.*, un.name AS unit_name, un.code AS unit_code,
           sa.joined_at AS my_joined_at
    FROM online_sessions s
    JOIN units un ON un.id = s.unit_id
    LEFT JOIN session_attendance sa ON sa.session_id = s.id AND sa.student_id = $sid
    WHERE s.unit_id IN ($unitIds) AND s.is_active = 1
    ORDER BY s.scheduled_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Classes — Student</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
<style>
    .session-card { background: var(--white); border-radius: var(--radius); padding: 22px; margin-bottom: 16px; box-shadow: var(--shadow); border-left: 4px solid var(--accent); }
    .platform-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: .7rem; font-weight: 800; text-transform: uppercase; margin-left: 8px; }
    .platform-zoom { background:#e6f0fa;color:#0c4a6e;}
    .platform-google_meet{background:#e6f5ea;color:#166534;}
    .platform-teams{background:#ede9fe;color:#5b21b6;}
    .platform-jitsi{background:#fef3c7;color:#854d0e;}
    .platform-other{background:#eef1f4;color:var(--muted);}
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>Online Classes</h1>
    <p class="subtitle">Join live sessions scheduled by your lecturers. Attendance is marked automatically when you click Join.</p>

    <?php if (empty($sessions)): ?>
        <div class="empty-state"><p>No upcoming online sessions for your units. Check back later.</p></div>
    <?php else: ?>
        <?php foreach ($sessions as $s): ?>
        <div class="session-card">
            <div class="q-header" style="margin-bottom:10px;">
                <div>
                    <strong style="color:var(--primary);"><?= esc($s['title']) ?></strong>
                    <span class="platform-badge platform-<?= esc($s['platform']) ?>"><?= str_replace('_',' ',esc($s['platform'])) ?></span>
                    <?php if ($s['my_joined_at']): ?><span class="badge badge-green" style="margin-left:6px;">✓ Attended</span><?php endif; ?>
                </div>
            </div>
            <p class="text-muted" style="font-size:.85rem;"><?= esc($s['unit_code'].' — '.$s['unit_name']) ?> · <?= esc($s['scheduled_at']) ?> · <?= (int)$s['duration_minutes'] ?> min</p>
            <?php if ($s['description']): ?><p style="font-size:.88rem;margin-top:8px;"><?= nl2br(esc($s['description'])) ?></p><?php endif; ?>
            <?php if ($s['meeting_id']): ?><p style="font-size:.85rem;margin-top:6px;"><strong>Meeting ID:</strong> <?= esc($s['meeting_id']) ?><?php if ($s['passcode']): ?> · <strong>Passcode:</strong> <?= esc($s['passcode']) ?><?php endif; ?></p><?php endif; ?>
            <p style="margin-top:14px;">
                <a class="btn-primary" href="?join=<?= $s['id'] ?>" target="_blank" rel="noopener">Join Session</a>
            </p>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</div>
</body>
</html>