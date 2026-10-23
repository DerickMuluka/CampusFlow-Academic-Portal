<div class="wa-float">
    <div class="wa-bubble" id="waBubble">
        <strong>👋 Need help? Chat with Rifkins College</strong>
        Ask us about:
        <div class="wa-actions">
            <a href="https://wa.me/254715043739?text=Hello%20Rifkins%20College%2C%20I%27d%20like%20to%20enquire%20about%20courses%20and%20admission." target="_blank" rel="noopener">
                🎓 Course & admission enquiries
            </a>
            <a href="https://wa.me/254715043739?text=Hello%2C%20I%27d%20like%20to%20know%20the%20fee%20structure%20for%20my%20course%20of%20interest." target="_blank" rel="noopener">
                💰 Fees & payment plans
            </a>
            <a href="https://wa.me/254715043739?text=Hello%2C%20I%27m%20a%20current%20student.%20I%20need%20help%20with%20the%20online%20portal." target="_blank" rel="noopener">
                🖥 Online campus support
            </a>
            <a href="https://wa.me/254715043739?text=Hello%2C%20I%20have%20a%20general%20question." target="_blank" rel="noopener">
                💬 Other enquiries
            </a>
        </div>
    </div>
    <button class="wa-btn" id="waToggle" aria-label="Chat on WhatsApp">
        <svg viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm0 18.15h-.01a8.2 8.2 0 0 1-4.18-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.23 8.23 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.42-.14 0-.31-.02-.47-.02-.17 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z"/></svg>
    </button>
</div>

<script>
(function(){
    const btn = document.getElementById('waToggle');
    const bubble = document.getElementById('waBubble');
    if (!btn || !bubble) return;
    btn.addEventListener('click', () => bubble.classList.toggle('open'));
    document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !bubble.contains(e.target)) bubble.classList.remove('open');
    });
})();
</script>