<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-success"></i>Transaction History</h4>
    <a href="<?= BASE_URL ?>api/transactions.php?action=export" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" id="filterReceipt" class="form-control form-control-sm"
                       placeholder="Receipt #…">
            </div>
            <div class="col-md-2">
                <input type="date" id="filterFrom" class="form-control form-control-sm"
                       value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-2">
                <input type="date" id="filterTo" class="form-control form-control-sm"
                       value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-2">
                <select id="filterPayment" class="form-select form-select-sm">
                    <option value="">All Payments</option>
                    <option value="cash">Cash</option>
                    <option value="digital">Digital</option>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-success btn-sm w-100" id="btnSearch">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" id="btnReset">Reset</button>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Receipt #</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Total</th>
                        <th>Payment</th>
                        <th>Cashier</th>
                        <th>Date/Time</th>
                        <th width="80">Actions</th>
                    </tr>
                </thead>
                <tbody id="transactionsBody">
                    <tr><td colspan="8" class="text-center py-4 text-muted">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center" id="txnPagination"></div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2" id="receiptContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    window.CURRENCY = '<?= CURRENCY_SYMBOL ?>';
</script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
