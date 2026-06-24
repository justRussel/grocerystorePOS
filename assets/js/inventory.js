/**
 * GroceryPOS – inventory.js
 * Tab AJAX loader, stock movement modal with product autocomplete.
 */
(function () {
    'use strict';

    const BASE = window.BASE_URL || '/';
    const CUR  = window.CURRENCY || '₱';
    let   activeTab = 'movements';
    let   productSearchTimer = null;

    function fmt(n) {
        return CUR + parseFloat(n || 0).toFixed(2);
    }

    // ─── Tab render functions ─────────────────────────────────────────────────

    function renderMovements(data) {
        if (!data.length) return '<p class="text-muted text-center py-3">No stock movements found.</p>';
        return `<div class="table-responsive"><table class="table table-hover table-sm align-middle">
            <thead class="table-light"><tr>
                <th>Product</th><th>Type</th>
                <th class="text-end">Before</th>
                <th class="text-end">Change</th>
                <th class="text-end">After</th>
                <th>Reason</th><th>Ref</th><th>By</th><th>Date</th>
            </tr></thead>
            <tbody>
            ${data.map(m => {
                const typeColors = { stock_in: 'success', stock_out: 'danger', adjustment: 'warning', sale: 'primary' };
                const col = typeColors[m.movement_type] || 'secondary';
                return `<tr>
                    <td>${escapeHtml(m.product_name || '—')}</td>
                    <td><span class="badge bg-${col}">${escapeHtml(m.movement_type)}</span></td>
                    <td class="text-end">${m.qty_before}</td>
                    <td class="text-end ${parseInt(m.qty_change) >= 0 ? 'text-success' : 'text-danger'}">
                        ${parseInt(m.qty_change) > 0 ? '+' : ''}${m.qty_change}
                    </td>
                    <td class="text-end">${m.qty_after}</td>
                    <td>${escapeHtml(m.reason || '—')}</td>
                    <td>${escapeHtml(m.reference || '—')}</td>
                    <td>${escapeHtml(m.created_by_name || '—')}</td>
                    <td class="small">${escapeHtml(m.created_at)}</td>
                </tr>`;
            }).join('')}
            </tbody></table></div>`;
    }

    function renderLowStock(data) {
        if (!data.length) return '<div class="alert alert-success">No low-stock products. 🎉</div>';
        return `<div class="table-responsive"><table class="table table-hover table-sm">
            <thead class="table-light"><tr>
                <th>Product</th><th>Category</th>
                <th class="text-end">Stock</th><th class="text-end">Threshold</th><th>Status</th>
            </tr></thead>
            <tbody>${data.map(p => `<tr>
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.category_name || '—')}</td>
                <td class="text-end text-warning fw-bold">${p.stock_qty}</td>
                <td class="text-end">${p.low_stock_threshold}</td>
                <td><span class="status-badge ${escapeHtml(p.status)}">${escapeHtml(p.status.replace('_', ' '))}</span></td>
            </tr>`).join('')}
            </tbody></table></div>`;
    }

    function renderExpiring(data) {
        if (!data.length) return '<div class="alert alert-success">No products expiring soon. 🎉</div>';
        return `<div class="table-responsive"><table class="table table-hover table-sm">
            <thead class="table-light"><tr>
                <th>Product</th><th>Category</th>
                <th class="text-end">Stock</th><th>Expiry Date</th><th>Status</th>
            </tr></thead>
            <tbody>${data.map(p => `<tr>
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.category_name || '—')}</td>
                <td class="text-end">${p.stock_qty}</td>
                <td>${escapeHtml(p.expiry_date)}</td>
                <td><span class="status-badge expiring_soon">Expiring Soon</span></td>
            </tr>`).join('')}
            </tbody></table></div>`;
    }

    function renderValuation(data) {
        return `<div class="row g-3">
            <div class="col-md-4">
                <div class="card text-center p-3">
                    <div class="stat-label">Total Cost Value</div>
                    <div class="stat-value text-warning">${fmt(data.total_cost_value)}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-3">
                    <div class="stat-label">Total Selling Value</div>
                    <div class="stat-value text-success">${fmt(data.total_selling_value)}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-3">
                    <div class="stat-label">Active Products</div>
                    <div class="stat-value text-primary">${data.product_count}</div>
                </div>
            </div>
        </div>`;
    }

    // ─── Load tab ─────────────────────────────────────────────────────────────
    function loadTab(tab) {
        const content = document.getElementById('inventoryTabContent');
        if (!content) return;
        content.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm"></div> Loading…</div>';

        const actionMap = {
            movements: 'movements',
            low_stock: 'low_stock',
            expiring:  'expiring',
            valuation: 'valuation',
        };

        apiFetch(BASE + 'api/inventory.php?action=' + actionMap[tab])
            .then(r => {
                if (!r.success) { content.innerHTML = '<div class="alert alert-danger">Failed to load.</div>'; return; }
                const d = r.data;
                switch (tab) {
                    case 'movements': content.innerHTML = renderMovements(d); break;
                    case 'low_stock': content.innerHTML = renderLowStock(d);  break;
                    case 'expiring':  content.innerHTML = renderExpiring(d);  break;
                    case 'valuation': content.innerHTML = renderValuation(d); break;
                }
            })
            .catch(() => { content.innerHTML = '<div class="alert alert-danger">Error loading data.</div>'; });
    }

    // ─── Stock Movement Modal ─────────────────────────────────────────────────
    function initMovementModal() {
        // Product autocomplete
        const searchInput   = document.getElementById('movProductSearch');
        const hiddenId      = document.getElementById('movProductId');
        const suggestions   = document.getElementById('movProductSuggestions');
        const movType       = document.getElementById('movementType');
        const qtyLabel      = document.getElementById('qtyLabel');

        movType?.addEventListener('change', function () {
            if (qtyLabel) qtyLabel.textContent = this.value === 'adjustment' ? 'New Exact Quantity' : 'Quantity';
        });

        searchInput?.addEventListener('input', function () {
            clearTimeout(productSearchTimer);
            const kw = this.value.trim();
            if (kw.length < 2) { suggestions.innerHTML = ''; return; }
            productSearchTimer = setTimeout(() => {
                apiFetch(BASE + 'api/products.php?action=search&keyword=' + encodeURIComponent(kw))
                    .then(r => {
                        if (!r.success) return;
                        suggestions.innerHTML = r.data.slice(0, 8).map(p =>
                            `<a href="#" class="list-group-item list-group-item-action py-1 small suggest-item"
                                data-id="${p.id}" data-name="${escapeHtml(p.name)}">
                                ${escapeHtml(p.name)} <span class="text-muted">(Stock: ${p.stock_qty})</span>
                            </a>`
                        ).join('');

                        suggestions.querySelectorAll('.suggest-item').forEach(a => {
                            a.addEventListener('click', function (e) {
                                e.preventDefault();
                                searchInput.value = this.dataset.name;
                                hiddenId.value    = this.dataset.id;
                                suggestions.innerHTML = '';
                            });
                        });
                    })
                    .catch(() => {});
            }, 300);
        });

        // Submit movement
        document.getElementById('btnSubmitMovement')?.addEventListener('click', function () {
            const productId = hiddenId?.value;
            const qty       = parseInt(document.getElementById('movQty')?.value || 0);
            const type      = movType?.value;
            const reason    = document.getElementById('movReason')?.value || '';
            const reference = document.getElementById('movReference')?.value || '';

            if (!productId) { showToast('Please select a product.', 'warning'); return; }
            if (qty <= 0)   { showToast('Quantity must be greater than 0.', 'warning'); return; }

            apiFetch(BASE + 'api/inventory.php?action=' + type, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ action: type, product_id: parseInt(productId), quantity: qty, reason, reference }),
            }).then(r => {
                if (r.success) {
                    showToast('Stock updated. New qty: ' + r.new_qty, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('stockMovementModal'))?.hide();
                    loadTab(activeTab);
                    // Reset modal
                    if (searchInput) searchInput.value = '';
                    if (hiddenId)    hiddenId.value    = '';
                    if (document.getElementById('movQty')) document.getElementById('movQty').value = 1;
                } else {
                    showToast(r.error || 'Movement failed.', 'danger');
                }
            }).catch(() => {});
        });
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Tab switching
        document.querySelectorAll('#inventoryTabs button').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#inventoryTabs button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                activeTab = this.dataset.tab;
                loadTab(activeTab);
            });
        });

        loadTab(activeTab);
        initMovementModal();
    });
})();
