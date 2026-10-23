<?php
$role = $_SESSION['role'] ?? '';
$roleLabel = ucfirst($role);
?>
<header class="topnav">
    <div class="topnav-left">
        <button id="sidebarToggle" class="icon-btn" aria-label="Toggle menu">
            <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M3 6h18v2H3zM3 11h18v2H3zM3 16h18v2H3z"/></svg>
        </button>
        <span class="brand">RIFKINS COLLEGE <small><?= esc($roleLabel) ?> Portal</small></span>
    </div>
    <div class="topnav-right">
        <span class="user-chip">
            <span class="avatar"><?= esc(getInitials($_SESSION['full_name'] ?? 'U')) ?></span>
            <span class="user-name"><?= esc($_SESSION['full_name'] ?? '') ?></span>
        </span>
        <a href="../logout.php" class="icon-btn logout-btn" title="Logout">
            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M16 13v-2H7V8l-5 4 5 4v-3zM20 3h-8v2h8v14h-8v2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/></svg>
        </a>
    </div>
</header>
<script src="../assets/js/sidebar.js"></script>