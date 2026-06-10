<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0 fw-bold"><i class="bi bi-archive me-2 text-success"></i>Inventory</h4>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#stockMovementModal">
        <i class="bi bi-plus-lg me-1"></i>Stock Movement
    </button>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="inventoryTabs">
    <li class="nav-item">
        <button class="nav-link active" data-tab="movements">
            <i class="bi bi-arrow-left-right me-1"></i>Stock Movements
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-tab="low_stock">
            <i class="bi bi-exclamation-triangle me-1 text-warning"></i>Low Stock
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-tab="expiring">
            <i class="bi bi-calendar-x me-1 text-danger"></i>Expiring Soon
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-tab="valuation">
            <i class="bi bi-currency-dollar me-1 text-info"></i>Valuation
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div id="inventoryTabContent">
    <div class="text-center py-4 text-muted">Loading…</div>
</div>

<!-- Stock Movement Modal -->
<div class="modal fade" id="stockMovementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Stock Movement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Movement Type</label>
                    <select id="movementType" class="form-select">
                        <option value="stock_in">Stock In</option>
                        <option value="stock_out">Stock Out</option>
                        <option value="adjustment">Adjustment (set exact qty)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Product</label>
                    <input type="text" id="movProductSearch" class="form-control" placeholder="Search product name…" autocomplete="off">
                    <input type="hidden" id="movProductId">
                    <div id="movProductSuggestions" class="list-group mt-1" style="max-height:200px;overflow-y:auto;"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" id="qtyLabel">Quantity</label>
                    <input type="number" id="movQty" class="form-control" min="0" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <input type="text" id="movReason" class="form-control" placeholder="e.g. Delivery, Damaged goods…">
                </div>
                <div class="mb-3">
                    <label class="form-label">Reference</label>
                    <input type="text" id="movReference" class="form-control" placeholder="e.g. PO number, Invoice…">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btnSubmitMovement">
                    <i class="bi bi-check-lg me-1"></i>Submit
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
