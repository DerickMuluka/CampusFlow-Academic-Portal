<?php
require_once '../config/db.php';
requireRole('student');

$uid = (int)$_SESSION['user_id'];
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_info') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name && $email) {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE id=?");
            $stmt->bind_param('ssi', $name, $email, $uid);
            $stmt->execute(); $stmt->close();
            $_SESSION['full_name'] = $name;
            $msg = 'Profile updated.';
        } else { $err = 'Name and email required.'; }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $row = $conn->query("SELECT password_hash FROM users WHERE id=$uid")->fetch_assoc();
        if (!password_verify($current, $row['password_hash'])) { $err = 'Current password is incorrect.'; }
        elseif (strlen($new) < 6) { $err = 'New password must be at least 6 characters.'; }
        elseif ($new !== $confirm) { $err = 'New passwords do not match.'; }
        else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?");
            $stmt->bind_param('si', $hash, $uid);
            $stmt->execute(); $stmt->close();
            $msg = 'Password updated.';
        }
    }
}

$user = $conn->query("SELECT u.*, c.name AS course_name FROM users u LEFT JOIN courses c ON c.id=u.course_id WHERE u.id=$uid")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — Student</title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1>My Profile</h1>
    <p class="subtitle">Manage your account information and password.</p>
    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert-error"><?= esc($err) ?></div><?php endif; ?>

    <form method="POST" class="card-form">
        <h2>Account Information</h2>
        <input type="hidden" name="action" value="update_info">
        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= esc($user['full_name']) ?>" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= esc($user['email']) ?>" required></div>
        <div class="form-group"><label>Role</label><input type="text" value="Student" disabled></div>
        <?php if ($user['admission_no']): ?><div class="form-group"><label>Admission No</label><input type="text" value="<?= esc($user['admission_no']) ?>" disabled></div><?php endif; ?>
        <?php if ($user['course_name']): ?><div class="form-group"><label>Course</label><input type="text" value="<?= esc($user['course_name']) ?>" disabled></div><?php endif; ?>
        <button type="submit" class="btn-primary">Update Info</button>
    </form>

    <form method="POST" class="card-form" style="margin-top:24px;">
        <h2>Change Password</h2>
        <input type="hidden" name="action" value="change_password">
        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="form-row">
            <div class="form-group"><label>New Password</label><input type="password" name="new_password" required minlength="6"></div>
            <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required minlength="6"></div>
        </div>
        <button type="submit" class="btn-primary">Change Password</button>
    </form>
</main>
</div>
<script src="../assets/js/profile.js"></script>
</body>
</html>