    </div><!-- /.container-fluid -->
</main><!-- /.main-content -->
</div><!-- /.pos-wrapper -->

<!-- Bootstrap 5 JS Bundle — must load before app.js and other scripts -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmS5VKoZb7FE1LFjMuSVlLDLRmX"
    crossorigin="anonymous"
></script>

<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    window.CURRENCY = '<?= CURRENCY_SYMBOL ?>';
</script>

<!-- Global App JS -->
<script src="<?= BASE_URL ?>assets/js/app.js"></script>

<!-- Sidebar toggle — pure JS -->
<script>
(function () {
    'use strict';
    var toggleBtn = document.getElementById('sidebarToggleBtn');
    var sidebar   = document.getElementById('appSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
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