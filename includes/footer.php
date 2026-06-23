    </main>
</div><!-- /.main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function () {
    'use strict';

    const moreBtn   = document.getElementById('mobMoreBtn');
    const sheet     = document.getElementById('mobSheet');
    const backdrop  = document.getElementById('mobBackdrop');

    function openSheet() {
        sheet.classList.add('show');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeSheet() {
        sheet.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (moreBtn)  moreBtn.addEventListener('click', openSheet);
    if (backdrop) backdrop.addEventListener('click', closeSheet);

    if (sheet) {
        let startY = 0;
        sheet.addEventListener('touchstart', e => { startY = e.touches[0].clientY; }, { passive: true });
        sheet.addEventListener('touchend',   e => { if (e.changedTouches[0].clientY - startY > 60) closeSheet(); }, { passive: true });
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSheet(); });
})();
</script>
</body>
</html>
