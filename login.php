<?php
require_once 'config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password_hash, role FROM users WHERE email=? AND is_active=1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header("Location: {$user['role']}/dashboard.php");
            exit;
        }
        $error = 'Invalid email or password, or account is inactive.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rifkins College — Sign In</title>
<link rel="stylesheet" href="assets/css/base.css">
<style>
    .login-body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:linear-gradient(135deg,var(--primary),var(--primary-light)); padding:20px; }
    .login-card { background:var(--white); border-radius:var(--radius); padding:44px 36px; width:100%; max-width:430px; box-shadow:0 20px 60px rgba(0,0,0,.3); }
    .login-brand { text-align:center; margin-bottom:28px; }
    .login-brand h1 { color:var(--primary); font-size:1.7rem; letter-spacing:2px; }
    .login-brand p { color:var(--accent); font-weight:600; font-size:.9rem; }
    .accred { display:inline-block; background:var(--bg); padding:3px 10px; border-radius:20px; font-size:.7rem; color:var(--muted); margin-top:8px; }
    .form-group { margin-bottom:18px; }
    .form-group label { display:block; font-weight:600; font-size:.85rem; margin-bottom:6px; color:var(--primary); }
    .form-group input { width:100%; padding:12px 42px 12px 14px; border:2px solid var(--border); border-radius:8px; font-size:1rem; font-family:inherit; }
    .form-group input:focus { outline:none; border-color:var(--accent); }
    .pw-wrap { position:relative; }
    .pw-toggle { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--muted); padding:6px; border-radius:6px; display:flex; align-items:center; }
    .pw-toggle:hover { background:var(--bg); }
    .pw-toggle.pw-showing { color:var(--accent); }
    .btn-primary { width:100%; padding:14px; background:var(--primary); color:var(--white); border:none; border-radius:8px; font-size:1rem; font-weight:700; cursor:pointer; }
    .btn-primary:hover { background:var(--primary-light); }
    .alert-error { background:#fde8e8; color:var(--danger); padding:10px 14px; border-radius:8px; margin-bottom:15px; font-size:.9rem; border-left:4px solid var(--danger); }
    .login-hint { text-align:center; font-size:.75rem; color:var(--muted); margin-top:20px; line-height:1.5; }
</style>
</head>
<body class="login-body">
<div class="login-card">
    <div class="login-brand">
        <h1>RIFKINS COLLEGE</h1>
        <p>Educating Since 1953</p>
        <span class="accred">TVETA/PRIVATE/TVC/0005/2022</span>
    </div>
    <?php if ($error): ?><div class="alert-error"><?= esc($error) ?></div><?php endif; ?>
    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required value="<?= esc($_POST['email'] ?? '') ?>" placeholder="you@rifkinscollege.com">
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="pw-wrap">
                <input type="password" id="password" name="password" required placeholder="Enter your password">
                <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                    <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5c-1.7-4.4-6-7.5-11-7.5zm0 12.5a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>
                    <svg id="eyeClosed" width="20" height="20" viewBox="0 0 24 24" style="display:none;"><path fill="currentColor" d="M12 7a5 5 0 0 1 5 5c0 .65-.13 1.26-.36 1.83l2.92 2.92A11.9 11.9 0 0 0 23 12c-1.73-4.39-6-7.5-11-7.5-1.27 0-2.49.2-3.64.57l2.17 2.16C10.7 7.13 11.33 7 12 7zM2.71 3.16 1.39 4.47l2.32 2.32A11.86 11.86 0 0 0 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l2.7 2.7 1.31-1.31L2.71 3.16zM12 17a5 5 0 0 1-5-5c0-.66.13-1.29.36-1.87l1.55 1.55c-.02.1-.03.21-.03.32a3 3 0 0 0 4.72 2.55l1.55 1.55A4.93 4.93 0 0 1 12 17z"/></svg>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-primary">Sign In</button>
    </form>
    <p class="login-hint">
        Accounts are created by the administrator.<br>
        Contact: info@rifkinscollege.com · 0715 043739
    </p>
</div>
<script>
document.getElementById('pwToggle').addEventListener('click', function() {
    const inp = document.getElementById('password');
    const isHidden = inp.type === 'password';
    inp.type = isHidden ? 'text' : 'password';
    this.classList.toggle('pw-showing', isHidden);
    document.getElementById('eyeOpen').style.display = isHidden ? 'none' : 'block';
    document.getElementById('eyeClosed').style.display = isHidden ? 'block' : 'none';
});
</script>
</body>
</html>