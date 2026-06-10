    </div><!-- /.container-fluid -->
</main><!-- /.main-content -->
</div><!-- /.pos-wrapper -->

<!-- ─── Bootstrap 5 JS Bundle (Popper included) ───────────────────────────── -->
<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    window.CURRENCY = '<?= CURRENCY_SYMBOL ?>';
</script>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmS5VKoZb7FE1LFjMuSVlLDLRmX"
    crossorigin="anonymous"
></script>

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
            sidebar.classList.toggle('sidebar-collapsed');
        });
    }
})();
</script>

<?php
/**
 * Output any page-specific scripts injected by the controller via $extraScripts.
 * Usage in controller view: $extraScripts = '<script src="..."></script>';
 */
if (!empty($extraScripts)) {
    echo $extraScripts;
}
?>

</body>
</html>
