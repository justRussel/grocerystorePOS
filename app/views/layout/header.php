<?php
/**
 * GroceryPOS - Layout Header
 * Starts the session, enforces authentication, and outputs the <head> + navbar open tags.
 *
 * Variables expected from the controller (optional):
 *   $pageTitle  — string, appended to APP_NAME in <title>
 *   $module     — string, current module slug (used by sidebar for active state)
 */

// ─── Session bootstrap ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ─── Load helpers ─────────────────────────────────────────────────────────────
$helpersDir = dirname(__DIR__, 2) . '/helpers/';
if (file_exists($helpersDir . 'flash.php'))  require_once $helpersDir . 'flash.php';
if (file_exists($helpersDir . 'csrf.php'))   require_once $helpersDir . 'csrf.php';

// ─── Auth guard ───────────────────────────────────────────────────────────────
// Allow the auth module (login/logout) to bypass the guard
$currentModule = isset($module) ? $module : (
    isset($_GET['module']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['module'])) : 'dashboard'
);

if ($currentModule !== 'auth' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?module=auth&action=login');
    exit;
}

// ─── Page title ───────────────────────────────────────────────────────────────
$title = APP_NAME;
if (!empty($pageTitle)) {
    $title .= ' – ' . htmlspecialchars($pageTitle);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>

    <!-- Bootstrap 5 CSS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Chart.js -->
    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"
        crossorigin="anonymous"
    ></script>

    <!-- App CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
</head>
<body>

<!-- ─── Top Navbar ─────────────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top shadow-sm">
    <div class="container-fluid">
        <!-- Sidebar toggle (mobile) -->
        <button
            class="btn btn-success me-2 d-lg-none"
            id="sidebarToggleBtn"
            type="button"
            aria-label="Toggle sidebar"
        >
            <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Brand -->
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>">
            <i class="bi bi-cart3 fs-5"></i>
            <span><?= htmlspecialchars(APP_NAME) ?></span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <!-- User dropdown -->
                <div class="position-relative" id="userDropdownWrap">
                    <button
                        class="btn btn-success d-flex align-items-center gap-2"
                        type="button"
                        id="userDropdownBtn"
                    >
                        <?php if (!empty($_SESSION['user_photo'])): ?>
                            <img
                                src="<?= htmlspecialchars(BASE_URL . 'uploads/' . $_SESSION['user_photo']) ?>"
                                alt="Avatar"
                                class="rounded-circle"
                                width="28" height="28"
                                style="object-fit:cover;"
                            >
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-5"></i>
                        <?php endif; ?>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <ul id="userDropdownMenu"
                        style="display:none;position:absolute;right:0;top:calc(100% + 4px);z-index:9999;min-width:200px;background:#fff;border:1px solid rgba(0,0,0,.15);border-radius:.375rem;padding:.5rem 0;box-shadow:0 .5rem 1rem rgba(0,0,0,.175);">
                        <li>
                            <a style="display:block;padding:.4rem 1rem;color:#212529;text-decoration:none;"
                               onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background=''"
                               href="<?= BASE_URL ?>?module=account">
                                <i class="bi bi-gear me-2"></i>Account Settings
                            </a>
                        </li>
                        <li><hr style="margin:.5rem 0;border:0;border-top:1px solid #e9ecef;"></li>
                        <li>
                            <a style="display:block;padding:.4rem 1rem;color:#dc3545;text-decoration:none;"
                               onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background=''"
                               href="<?= BASE_URL ?>?module=auth&action=logout">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ─── Page wrapper (sidebar + main content) ─────────────────────────────── -->
<div class="pos-wrapper d-flex">

<?php
// ─── Flash message banner ─────────────────────────────────────────────────────
if (function_exists('getFlash')) {
    $flash = getFlash();
    if ($flash):
?>
<div class="position-fixed top-0 start-50 translate-middle-x mt-5 pt-2" style="z-index:9000;min-width:320px;max-width:560px;">
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php
    endif;
}
?>
