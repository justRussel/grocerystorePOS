<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <h4 class="mb-0 fw-bold"><i class="bi bi-gear me-2 text-success"></i>Account Settings</h4>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="accountTabs">
    <li class="nav-item"><button class="nav-link active" data-tab="profile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="store">Store Info</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="password">Change Password</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="backup">Database Backup</button></li>
</ul>

<!-- ─── Profile Tab ──────────────────────────────────────────────────────── -->
<div id="tab-profile">
    <div class="card">
        <div class="card-body">
            <div class="row align-items-start g-4">
                <!-- Avatar -->
                <div class="col-md-3 text-center">
                    <?php
                    $photoSrc = !empty($user['photo'])
                        ? BASE_URL . 'uploads/' . htmlspecialchars($user['photo'])
                        : BASE_URL . 'assets/img/placeholder.png';
                    ?>
                    <img src="<?= $photoSrc ?>" alt="Avatar" class="rounded-circle mb-2"
                         style="width:100px;height:100px;object-fit:cover;" id="avatarPreview">
                    <form method="POST" action="<?= BASE_URL ?>?module=account&action=uploadPhoto"
                          enctype="multipart/form-data">
                        <label class="btn btn-outline-secondary btn-sm mt-1 w-100" for="photoInput">
                            <i class="bi bi-camera me-1"></i>Change Photo
                        </label>
                        <input type="file" name="photo" id="photoInput" accept="image/*" class="d-none"
                               onchange="previewAvatar(this)">
                        <button type="submit" class="btn btn-success btn-sm w-100 mt-1">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                    </form>
                </div>

                <!-- Profile form -->
                <div class="col-md-9">
                    <form method="POST" action="<?= BASE_URL ?>?module=account&action=updateProfile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <!-- Store fields hidden here for profile tab -->
                            <input type="hidden" name="store_name"    value="<?= htmlspecialchars($user['store_name']    ?? '') ?>">
                            <input type="hidden" name="store_address" value="<?= htmlspecialchars($user['store_address'] ?? '') ?>">
                            <input type="hidden" name="tax_id"        value="<?= htmlspecialchars($user['tax_id']        ?? '') ?>">
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg me-1"></i>Save Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Store Info Tab ────────────────────────────────────────────────────── -->
<div id="tab-store" class="d-none">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>?module=account&action=updateProfile">
                <!-- pass through profile fields unchanged -->
                <input type="hidden" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                <input type="hidden" name="email"     value="<?= htmlspecialchars($user['email']     ?? '') ?>">
                <input type="hidden" name="phone"     value="<?= htmlspecialchars($user['phone']     ?? '') ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Store Name</label>
                        <input type="text" name="store_name" class="form-control"
                               value="<?= htmlspecialchars($user['store_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tax ID</label>
                        <input type="text" name="tax_id" class="form-control"
                               value="<?= htmlspecialchars($user['tax_id'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Store Address</label>
                        <textarea name="store_address" class="form-control" rows="3"><?= htmlspecialchars($user['store_address'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i>Save Store Info
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Change Password Tab ───────────────────────────────────────────────── -->
<div id="tab-password" class="d-none">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>?module=account&action=updatePassword"
                  style="max-width:480px;">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password <small class="text-muted">(min 8 chars)</small></label>
                    <input type="password" name="new_password" class="form-control" required
                           minlength="8" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required
                           minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-key me-1"></i>Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ─── Database Backup Tab ───────────────────────────────────────────────── -->
<div id="tab-backup" class="d-none">
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-database-down display-3 text-success mb-3 d-block"></i>
            <h5>Export Database Backup</h5>
            <p class="text-muted mb-4">Download the current SQL schema backup file of GroceryPOS.</p>
            <a href="<?= BASE_URL ?>?module=account&action=exportDatabase"
               class="btn btn-success btn-lg">
                <i class="bi bi-download me-2"></i>Export SQL Backup
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    // Tab switching
    const tabs    = document.querySelectorAll('#accountTabs button.nav-link');
    const panels  = ['profile', 'store', 'password', 'backup'];

    function showTab(tab) {
        tabs.forEach(t => t.classList.remove('active'));
        panels.forEach(p => document.getElementById('tab-' + p).classList.add('d-none'));
        document.getElementById('tab-' + tab).classList.remove('d-none');
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    }

    tabs.forEach(t => t.addEventListener('click', () => showTab(t.dataset.tab)));

    // Avatar preview
    window.previewAvatar = function (input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => { document.getElementById('avatarPreview').src = e.target.result; };
            reader.readAsDataURL(input.files[0]);
        }
    };
})();
</script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
