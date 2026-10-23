<?php $pageTitle = 'How to Apply — Rifkins College'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="../assets/css/public.css">
</head>
<body>
<?php include __DIR__ . '/includes/public-header.php'; ?>

<section class="pub-section" style="padding-top:120px;">
    <div class="container">
        <div class="pub-section-header">
            <span class="pub-section-eyebrow">How to Apply</span>
            <h2>Simple, Seamless Admission</h2>
            <p class="lead">Follow these steps to join Rifkins College. Our staff is available to assist you at every stage.</p>
        </div>
        <div class="pub-features">
            <div class="pub-feature">
                <h3>Step 1: Choose Your Course</h3>
                <p>Browse our <a href="courses.php" style="color:var(--primary-light);font-weight:700;">Courses page</a> and select the Diploma or Certificate programme you wish to pursue. If you need career guidance, call, SMS, or WhatsApp <strong>0715 043739</strong>.</p>
            </div>
            <div class="pub-feature">
                <h3>Step 2: Check Requirements</h3>
                <p>Verify that you meet the minimum entry grade for your chosen course. Requirements are listed on each course card on the Courses page. If in doubt, contact us for assistance.</p>
            </div>
            <div class="pub-feature">
                <h3>Step 3: Find Out Fees</h3>
                <p>Call or WhatsApp <strong>0715 043739</strong> for the current fee structure. Flexible payment plans are available.</p>
            </div>
            <div class="pub-feature">
                <h3>Step 4: Apply</h3>
                <p>Apply online using the <a href="apply.php" style="color:var(--primary-light);font-weight:700;">Online Application Form</a>, or visit any of our campuses to apply in person.</p>
            </div>
            <div class="pub-feature">
                <h3>Step 5: Pay Application Fee</h3>
                <p>Pay the application fee of <strong>Kshs 500</strong> via M-Pesa PayBill <strong>4088921</strong>, Account: <em>Your First Name</em> (no spacing). Once confirmed, you will be enrolled.</p>
            </div>
            <div class="pub-feature">
                <h3>Step 6: Confirmation</h3>
                <p>After payment, you will receive confirmation and login credentials for the online student portal. Attending physically? Visit the campus on the first day of the intake.</p>
            </div>
        </div>

        <div class="pub-feature" style="margin-top:40px;background:var(--bg-light);border-left:4px solid var(--accent);">
            <h3>Required Documents</h3>
            <p>
                ✓ KCSE Result Slip / Certificate<br>
                ✓ KCSE Index Number (for KNEC courses)<br>
                ✓ National ID or Birth Certificate<br>
                ✓ Two passport-size photographs<br>
                ✓ Any previous academic certificates (if applicable)
            </p>
        </div>

        <div class="pub-feature" style="margin-top:20px;background:var(--bg-light);border-left:4px solid var(--primary);">
            <h3>Need Assistance?</h3>
            <p>Call <strong>0715 043739</strong> (Main), <strong>0705 799553</strong> (Mariakani), or <strong>0717 058441</strong> (Diani).<br>
            Email: <a href="mailto:info@rifkinscollege.com" style="color:var(--primary-light);font-weight:700;">info@rifkinscollege.com</a></p>
        </div>
    </div>
</section>

<section class="pub-cta">
    <div class="container">
        <h2>Ready to Apply?</h2>
        <p>Complete our online application form in under 5 minutes.</p>
        <a href="apply.php" class="btn-hero btn-hero-primary">Apply Online Now</a>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>