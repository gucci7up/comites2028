    </main>
</div><!-- /.main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function () {
    'use strict';

    /* ── FAB MOBILE MENU ─────────────────────────────── */
    const fabBtn      = document.getElementById('fabBtn');
    const fabSheet    = document.getElementById('fabSheet');
    const fabBackdrop = document.getElementById('fabBackdrop');
    const fabIcon     = document.getElementById('fabIcon');

    function openFab() {
        fabSheet.classList.add('show');
        fabBackdrop.classList.add('show');
        fabBtn.classList.add('open');
        fabIcon.className = 'fas fa-times';
        document.body.style.overflow = 'hidden';
    }

    function closeFab() {
        fabSheet.classList.remove('show');
        fabBackdrop.classList.remove('show');
        fabBtn.classList.remove('open');
        fabIcon.className = 'fas fa-bars';
        document.body.style.overflow = '';
    }

    if (fabBtn) {
        fabBtn.addEventListener('click', function () {
            fabSheet.classList.contains('show') ? closeFab() : openFab();
        });
    }

    if (fabBackdrop) {
        fabBackdrop.addEventListener('click', closeFab);
    }

    /* Swipe down to close sheet */
    if (fabSheet) {
        let startY = 0;
        fabSheet.addEventListener('touchstart', function (e) { startY = e.touches[0].clientY; }, { passive: true });
        fabSheet.addEventListener('touchend',   function (e) {
            if (e.changedTouches[0].clientY - startY > 60) closeFab();
        }, { passive: true });
    }

    /* Close on Escape */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeFab();
    });
})();
</script>
</body>
</html>
