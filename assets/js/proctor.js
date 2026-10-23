(function () {
    'use strict';
    let timeLeft = REMAINING, submitted = false, stream = null, snapTimer = null;
    const timerDisplay = document.getElementById('timerDisplay');
    const timerBox = document.getElementById('timerBox');
    const warningText = document.getElementById('warningText');
    const form = document.getElementById('examForm');
    const video = document.getElementById('camPreview');

    // Block copy, paste, cut, right-click, selection
    ['copy','paste','cut','contextmenu','dragstart','selectstart'].forEach(evt => {
        document.addEventListener(evt, e => { e.preventDefault(); logViolation('blocked_' + evt); }, true);
    });

    function tick() {
        if (submitted) return;
        const m = Math.floor(timeLeft/60), s = timeLeft%60;
        timerDisplay.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (timeLeft <= 300) timerBox.classList.add('timer-warning');
        if (timeLeft <= 0) {
            submitted = true; stopProctoring();
            const u = new URL(form.action, location.href);
            u.searchParams.set('auto','1');
            u.searchParams.set('attempt_id', ATTEMPT_ID);
            u.searchParams.set('exam_id', form.querySelector('input[name="exam_id"]').value);
            location.href = u.toString(); return;
        }
        timeLeft--;
    }
    setInterval(tick, 1000); tick();

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({video:{width:320,height:240},audio:false});
            video.srcObject = stream;
            snapTimer = setInterval(capture, 30000);
            setTimeout(capture, 2000);
        } catch(e) {
            document.querySelector('.proctor-info').innerHTML = '<span style="color:#ff8a8a">Camera unavailable — proctoring limited</span>';
        }
    }

    function capture() {
        if (!stream || submitted) return;
        const c = document.createElement('canvas'); c.width=320; c.height=240;
        c.getContext('2d').drawImage(video,0,0,320,240);
        const body = new URLSearchParams();
        body.append('attempt_id', ATTEMPT_ID);
        body.append('image', c.toDataURL('image/jpeg',0.6));
        fetch('../api/save-snapshot.php',{method:'POST',body}).catch(()=>{});
    }

    function stopProctoring() {
        if (snapTimer) clearInterval(snapTimer);
        if (stream) stream.getTracks().forEach(t=>t.stop());
    }

    function logViolation(type) {
        const body = new URLSearchParams();
        body.append('attempt_id', ATTEMPT_ID);
        body.append('type', type);
        fetch('../api/log-violation.php',{method:'POST',body}).catch(()=>{});
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden && !submitted) {
            logViolation('tab_switch');
            warningText.textContent = '⚠ Tab switch detected and logged.';
            warningText.style.color = '#c0392b';
        }
    });

    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement && !submitted) {
            logViolation('fullscreen_exit');
            warningText.textContent = '⚠ Fullscreen exit logged. Return to fullscreen.';
            warningText.style.color = '#c0392b';
            document.documentElement.requestFullscreen().catch(()=>{});
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.documentElement.requestFullscreen().catch(()=>{});
    });

    form.addEventListener('submit', () => { submitted = true; stopProctoring(); });

    startCamera();
})();