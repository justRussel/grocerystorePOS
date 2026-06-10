<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="pos-layout">

    <!-- ─── Left: Product Panel ─────────────────────────────────────────────── -->
    <div class="pos-product-panel">
        <!-- Search + category filter -->
        <div class="mb-2 d-flex gap-2 flex-wrap">
            <div class="flex-grow-1 position-relative">
                <i class="bi bi-search position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="text" id="posSearch" class="form-control ps-4"
                       placeholder="Search product or scan barcode (F2)…" autocomplete="off">
            </div>
        </div>

        <!-- Category filter buttons -->
        <div class="d-flex gap-2 flex-wrap mb-3" id="categoryFilters">
            <button class="btn btn-success btn-sm active" data-cat="">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn btn-outline-secondary btn-sm" data-cat="<?= (int) $cat['id'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Product grid -->
        <div class="row g-2" id="productGrid">
            <div class="col-12 text-center text-muted py-4">Loading products…</div>
        </div>
    </div>

    <!-- ─── Right: Cart Panel ────────────────────────────────────────────────── -->
    <div class="pos-cart-panel">
        <div class="pos-cart-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cart3 me-2"></i>Cart</span>
            <button class="btn btn-sm btn-outline-light" id="btnClearCart" title="Clear Cart (Esc)">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <div class="pos-cart-items" id="cartItems">
            <p class="text-muted text-center mt-4 small">Cart is empty</p>
        </div>

        <div class="pos-cart-footer">
            <!-- Discount -->
            <div class="d-flex gap-2 mb-2 align-items-center">
                <label class="small text-muted mb-0" style="min-width:60px;">Discount</label>
                <input type="number" id="discountValue" class="form-control form-control-sm" min="0" value="0" step="0.01">
                <select id="discountType" class="form-select form-select-sm" style="width:90px;">
                    <option value="fixed">₱ Fixed</option>
                    <option value="percent">% Pct</option>
                </select>
            </div>

            <!-- Totals -->
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Subtotal</span>
                <span id="cartSubtotal"><?= CURRENCY_SYMBOL ?>0.00</span>
            </div>
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Discount</span>
                <span id="cartDiscount">- <?= CURRENCY_SYMBOL ?>0.00</span>
            </div>
            <div class="d-flex justify-content-between fw-bold fs-5 mb-2">
                <span>Total</span>
                <span id="cartTotal" class="text-success"><?= CURRENCY_SYMBOL ?>0.00</span>
            </div>

            <!-- Payment method -->
            <div class="mb-2">
                <label class="small text-muted">Payment Method</label>
                <div class="btn-group w-100 mt-1" id="paymentMethodBtns">
                    <button class="btn btn-outline-success btn-sm active" data-pm="cash">
                        <i class="bi bi-cash me-1"></i>Cash
                    </button>
                    <button class="btn btn-outline-primary btn-sm" data-pm="digital">
                        <i class="bi bi-phone me-1"></i>Digital
                    </button>
                </div>
            </div>

            <!-- Cash tendered -->
            <div id="cashTenderedRow" class="mb-2">
                <label class="small text-muted">Cash Tendered</label>
                <input type="number" id="cashTendered" class="form-control form-control-sm mt-1"
                       min="0" step="0.01" value="0" placeholder="Enter amount…">
                <div class="d-flex justify-content-between small mt-1">
                    <span class="text-muted">Change</span>
                    <span id="changeAmount" class="fw-bold text-success"><?= CURRENCY_SYMBOL ?>0.00</span>
                </div>
            </div>

            <!-- Checkout button -->
            <button class="btn btn-success w-100 fw-bold" id="btnCheckout" title="Checkout (F12)">
                <i class="bi bi-check-circle me-2"></i>Checkout
            </button>
        </div>
    </div>
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
                <button type="button" class="btn btn-success btn-sm" id="btnPrintReceipt">
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
