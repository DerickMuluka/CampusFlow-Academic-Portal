<?php
$current = basename($_SERVER['PHP_SELF']);
function pubNav($href, $label, $current) {
    $active = ($current === basename($href)) ? ' class="active"' : '';
    echo "<a href=\"$href\"$active>$label</a>";
}
?>
<div class="pub-topbar">
    <div class="container">
        <span> Focal Point Building, Digo Road, Mombasa · P.O. Box 84728</span>
        <span>📞 <a href="tel:+254715043739">0715 043739</a> · ✉ <a href="mailto:info@campus.com">info@rifkinscollege.com</a></span>
    </div>
</div>

<header class="pub-header">
    <div class="container">
        <a href="index.php" class="pub-logo">
            <div class="pub-logo-mark">RC</div>
            <div class="pub-logo-text">
                <strong>RIFKINS COLLEGE</strong>
                <small>Educating Since 1953 · TVETA/PRIVATE/TVC/0005/2022</small>
            </div>
        </a>
        <button class="pub-mobile-toggle" id="mobileToggle" aria-label="Menu">
            <svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M3 6h18v2H3zM3 11h18v2H3zM3 16h18v2H3z"/></svg>
        </button>
        <nav class="pub-nav" id="pubNav">
            <?php pubNav('index.php', 'Home', $current); ?>
            <?php pubNav('about.php', 'About Us', $current); ?>
            <?php pubNav('courses.php', 'Courses', $current); ?>
            <?php pubNav('how-to-apply.php', 'How to Apply', $current); ?>
            <?php pubNav('online-campus.php', 'Online Campus', $current); ?>
            <?php pubNav('contact.php', 'Contact', $current); ?>
            <a href="../login.php" class="btn-portal">LOGIN</a>
        </nav>
    </div>
</header>
<script src="../assets/js/public.js"></script>