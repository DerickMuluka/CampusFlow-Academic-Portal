document.getElementById('mobileToggle')?.addEventListener('click', function() {
    document.getElementById('pubNav')?.classList.toggle('open');
});
// Close mobile nav when clicking outside
document.addEventListener('click', function(e) {
    const nav = document.getElementById('pubNav');
    const toggle = document.getElementById('mobileToggle');
    if (!nav || !toggle) return;
    if (!nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('open');
    }
});