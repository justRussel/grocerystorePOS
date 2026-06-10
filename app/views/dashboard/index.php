<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card revenue h-100">
            <div class="card-body">
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-value text-success" id="kpi-revenue"><?= CURRENCY_SYMBOL ?>0.00</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card profit h-100">
            <div class="card-body">
                <div class="stat-label">Today's Profit</div>
                <div class="stat-value text-info" id="kpi-profit"><?= CURRENCY_SYMBOL ?>0.00</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card orders h-100">
            <div class="card-body">
                <div class="stat-label">Today's Transactions</div>
                <div class="stat-value text-primary" id="kpi-transactions">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card orders h-100">
            <div class="card-body">
                <div class="stat-label">Items Sold</div>
                <div class="stat-value text-secondary" id="kpi-items">0</div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-warning h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-2"></i>
                <div>
                    <div class="stat-label">Low Stock Products</div>
                    <div class="stat-value" id="kpi-low-stock">0</div>
                </div>
                <a href="<?= BASE_URL ?>?module=inventory" class="btn btn-outline-warning btn-sm ms-auto">View</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-danger h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-calendar-x-fill text-danger fs-2"></i>
                <div>
                    <div class="stat-label">Expiring Soon</div>
                    <div class="stat-value" id="kpi-expiring">0</div>
                </div>
                <a href="<?= BASE_URL ?>?module=inventory" class="btn btn-outline-danger btn-sm ms-auto">View</a>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row g-3 mb-4">
    <!-- Best Sellers -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-trophy-fill text-warning"></i> Best Sellers Today
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody id="best-sellers-body">
                        <tr><td colspan="4" class="text-center text-muted py-3">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-receipt text-primary"></i> Recent Transactions
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Receipt #</th>
                                <th class="text-end">Total</th>
                                <th>Payment</th>
                                <th>Cashier</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="recent-transactions-body">
                            <tr><td colspan="5" class="text-center text-muted py-3">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Chart -->
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-fill text-success"></i>
        Monthly Revenue &amp; Profit — <?= date('Y') ?>
    </div>
    <div class="card-body">
        <canvas id="monthlyChart" height="100"></canvas>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/dashboard.js"></script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
