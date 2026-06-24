<?php
/**
 * GroceryPOS - Layout Sidebar
 * Renders the left navigation menu.
 * Expects $module (string) to be set by the controller for active-state highlighting.
 */

$currentModule = isset($module) ? $module : (
    isset($_GET['module']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['module'])) : 'dashboard'
);

/**
 * Helper: returns 'active' class string when the given slug matches current module.
 */
function sidebarActive(string $slug, string $current): string
{
    return $slug === $current ? 'active' : '';
}

$navItems = [
    ['slug' => 'dashboard',    'icon' => 'bi-speedometer2',  'label' => 'Dashboard'],
    ['slug' => 'pos',          'icon' => 'bi-cart-check',    'label' => 'Point of Sale'],
    ['slug' => 'products',     'icon' => 'bi-box-seam',      'label' => 'Products'],
    ['slug' => 'inventory',    'icon' => 'bi-archive',       'label' => 'Inventory'],
    ['slug' => 'sales',        'icon' => 'bi-graph-up-arrow','label' => 'Sales & Analytics'],
    ['slug' => 'suppliers',    'icon' => 'bi-truck',         'label' => 'Suppliers'],
    ['slug' => 'transactions', 'icon' => 'bi-receipt',       'label' => 'Transactions'],
    ['slug' => 'account',      'icon' => 'bi-gear',          'label' => 'Account Settings'],
];
?>
<!-- ─── Sidebar ──────────────────────────────────────────────────────────── -->
<nav id="appSidebar" class="sidebar bg-dark flex-shrink-0">
    <div class="sidebar-sticky py-3">

        <!-- Store name -->
        <div class="sidebar-brand px-3 mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-cart3 text-success fs-5"></i>
            <span class="text-white fw-semibold small text-truncate">
                Smart Grocery POS
            </span>
        </div>

        <hr class="border-secondary my-0 mb-2">

        <!-- Navigation links -->
        <ul class="nav flex-column px-2">
            <?php foreach ($navItems as $item): ?>
                <li class="nav-item">
                    <a
                        href="<?= BASE_URL ?>?module=<?= $item['slug'] ?>"
                        class="nav-link sidebar-link <?= sidebarActive($item['slug'], $currentModule) ?>"
                    >
                        <i class="bi <?= $item['icon'] ?> me-2"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Bottom logout shortcut -->
        <hr class="border-secondary mt-3 mb-2">
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a
                    href="<?= BASE_URL ?>?module=auth&action=logout"
                    class="nav-link sidebar-link text-danger"
                >
                    <i class="bi bi-box-arrow-right me-2"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>

    </div>
</nav>
<!-- ─── Main Content Area ─────────────────────────────────────────────────── -->
<main class="main-content flex-grow-1">
    <div class="container-fluid py-4 px-4">
