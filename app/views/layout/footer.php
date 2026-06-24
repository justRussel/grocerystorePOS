    </div><!-- /.container-fluid -->
</main><!-- /.main-content -->
</div><!-- /.pos-wrapper -->

<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    window.CURRENCY = '<?= CURRENCY_SYMBOL ?>';
</script>

<!-- ─── Global App JS ─────────────────────────────────────────────────────── -->
<script src="<?= BASE_URL ?>assets/js/app.js"></script>

<!-- ─── Sidebar toggle script ────────────────────────────────────────────── -->
<script>
(function () {
    'use strict';

    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar   = document.getElementById('appSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-open');
        });
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992
                && !sidebar.contains(e.target)
                && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('sidebar-open');
            }
        });
    }
})();
</script>

<?php if (!empty($extraScripts)) { echo $extraScripts; } ?>

</body>
</html>