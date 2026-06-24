    </div><!-- /.container-fluid -->
</main><!-- /.main-content -->
</div><!-- /.pos-wrapper -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<script src="<?= BASE_URL ?>assets/js/app.js?v=<?= time() ?>"></script>

<script>
// Dropdown toggle
var _ddBtn  = document.getElementById('userDropdownBtn');
var _ddMenu = document.getElementById('userDropdownMenu');
if (_ddBtn && _ddMenu) {
    _ddBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        _ddMenu.style.display = _ddMenu.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function(e) {
        if (_ddMenu && !_ddBtn.contains(e.target)) {
            _ddMenu.style.display = 'none';
        }
    });
}

// Burger / sidebar toggle
var _togBtn = document.getElementById('sidebarToggleBtn');
var _sidebar = document.getElementById('appSidebar');
if (_togBtn && _sidebar) {
    _togBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        _sidebar.classList.toggle('sidebar-open');
    });
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 992 && _sidebar.classList.contains('sidebar-open')
            && !_sidebar.contains(e.target) && !_togBtn.contains(e.target)) {
            _sidebar.classList.remove('sidebar-open');
        }
    });
}
</script>

<?php if (!empty($extraScripts)) { echo $extraScripts; } ?>

</body>
</html>