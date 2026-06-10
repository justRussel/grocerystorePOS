<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Sales &amp; Analytics</h4>
</div>

<!-- Period tabs -->
<ul class="nav nav-tabs mb-3" id="salesTabs">
    <li class="nav-item">
        <button class="nav-link active" data-period="daily">Today</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-period="weekly">This Week</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-period="monthly">This Month</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-period="yearly">This Year</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-period="custom">Custom</button>
    </li>
</ul>

<!-- Custom date range (hidden by default) -->
<div id="customRangeRow" class="card mb-3 d-none">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="small">From</label>
                <input type="date" id="customFrom" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-4">
                <label class="small">To</label>
                <input type="date" id="customTo" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
                <button class="btn btn-success btn-sm w-100" id="btnCustomSearch">
                    <i class="bi bi-search me-1"></i>Search
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card revenue h-100">
            <div class="card-body">
                <div class="stat-label">Revenue</div>
                <div class="stat-value text-success" id="s-revenue"><?= CURRENCY_SYMBOL ?>0.00</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card profit h-100">
            <div class="card-body">
                <div class="stat-label">Profit</div>
                <div class="stat-value text-info" id="s-profit"><?= CURRENCY_SYMBOL ?>0.00</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card orders h-100">
            <div class="card-body">
                <div class="stat-label">Transactions</div>
                <div class="stat-value text-primary" id="s-transactions">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card orders h-100">
            <div class="card-body">
                <div class="stat-label">Items Sold</div>
                <div class="stat-value text-secondary" id="s-items">0</div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue vs Profit chart + Product Performance -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Revenue vs Profit</div>
            <div class="card-body">
                <canvas id="salesChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Top Products</div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Profit</th>
                        </tr>
                    </thead>
                    <tbody id="productPerfBody">
                        <tr><td colspan="4" class="text-center text-muted py-3">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    window.CURRENCY = '<?= CURRENCY_SYMBOL ?>';
</script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
