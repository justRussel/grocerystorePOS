<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= BASE_URL ?>?module=suppliers" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0 fw-bold"><i class="bi bi-building me-2 text-success"></i>
        <?= htmlspecialchars($supplier['company_name']) ?>
    </h4>
</div>

<!-- Supplier Info Card -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Supplier Information</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th width="40%">Contact Person</th><td><?= htmlspecialchars($supplier['contact_person'] ?? '—') ?></td></tr>
                    <tr><th>Phone</th><td><?= htmlspecialchars($supplier['phone'] ?? '—') ?></td></tr>
                    <tr><th>Email</th><td><?= htmlspecialchars($supplier['email'] ?? '—') ?></td></tr>
                    <tr><th>Address</th><td><?= htmlspecialchars($supplier['address'] ?? '—') ?></td></tr>
                    <tr><th>Products</th><td><?= (int) $productCount ?></td></tr>
                    <tr><th>Since</th><td><?= htmlspecialchars($supplier['created_at'] ?? '—') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <button class="nav-link active" id="tabPOs">Purchase Orders</button>
    </li>
</ul>

<!-- Purchase Orders Table -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Purchase Orders</span>
        <button class="btn btn-success btn-sm" id="btnNewPO" data-supplier-id="<?= (int) $supplier['id'] ?>">
            <i class="bi bi-plus-lg me-1"></i>New PO
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>PO #</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Ordered</th>
                        <th>Received</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="6" class="text-center py-3 text-muted">No purchase orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $po): ?>
                            <tr>
                                <td><?= htmlspecialchars($po['po_number']) ?></td>
                                <td class="text-end"><?= CURRENCY_SYMBOL . number_format($po['total_amount'], 2) ?></td>
                                <td>
                                    <span class="status-badge <?= htmlspecialchars($po['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($po['status'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($po['ordered_at']) ?></td>
                                <td><?= htmlspecialchars($po['received_at'] ?? '—') ?></td>
                                <td>
                                    <?php if ($po['status'] === 'pending'): ?>
                                        <button class="btn btn-xs btn-success btn-sm btn-receive-po"
                                                data-id="<?= (int) $po['id'] ?>">Receive</button>
                                        <button class="btn btn-xs btn-danger btn-sm btn-cancel-po"
                                                data-id="<?= (int) $po['id'] ?>">Cancel</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.BASE_URL = '<?= BASE_URL ?>';
    window.CURRENCY = '<?= CURRENCY_SYMBOL ?>';
    window.SUPPLIER_ID = <?= (int) $supplier['id'] ?>;

    // Receive PO
    document.querySelectorAll('.btn-receive-po').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Mark this PO as received? This will update stock.')) return;
            apiFetch(BASE_URL + 'api/suppliers.php?action=po_receive', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'po_receive', id: parseInt(this.dataset.id)})
            }).then(r => {
                if (r.success) { showToast('PO received. Stock updated.', 'success'); location.reload(); }
                else showToast(r.error || 'Failed', 'danger');
            });
        });
    });

    // Cancel PO
    document.querySelectorAll('.btn-cancel-po').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Cancel this purchase order?')) return;
            apiFetch(BASE_URL + 'api/suppliers.php?action=po_cancel', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'po_cancel', id: parseInt(this.dataset.id)})
            }).then(r => {
                if (r.success) { showToast('PO cancelled.', 'success'); location.reload(); }
                else showToast(r.error || 'Failed', 'danger');
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
