<?php
// This page is a friendly redirect to the login screen
$pageTitle = 'Results Portal — Rifkins College';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="2;url=../index.php">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="../assets/css/public.css">
</head>
<body>
<?php include __DIR__ . '/includes/public-header.php'; ?>

<section class="pub-section" style="padding-top:120px;">
    <div class="container">
        <div class="pub-section-header">
            <span class="pub-section-eyebrow">Results Portal</span>
            <h2>Redirecting to Login...</h2>
            <p class="lead">The Results Portal is available to enrolled students only. You will be redirected to the login page in a moment.</p>
        </div>

        <div class="pub-feature" style="max-width:600px;margin:0 auto;text-align:center;">
            <div class="pub-feature-icon" style="margin:0 auto 16px;">📊</div>
            <h3>What You Can Do in the Results Portal</h3>
            <p style="text-align:left;margin-top:16px;">
                ✓ View your full academic transcript<br>
                ✓ Check CAT 1, CAT 2, and FINAL exam scores<br>
                ✓ See your weighted percentage and letter grade per unit<br>
                ✓ Review past exam answers with correct answers shown<br>
                ✓ Download or print your transcript
            </p>
            <p style="margin-top:20px;"><a href="../index.php" class="btn-hero btn-hero-primary">Go to Login Now</a></p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>