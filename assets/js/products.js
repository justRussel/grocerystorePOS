/**
 * GroceryPOS – products.js
 * AJAX product list, delete confirm, CSV import modal.
 */
(function () {
    'use strict';

    const BASE = window.BASE_URL || '/';
    const CUR  = window.CURRENCY || '₱';

    function fmt(n) {
        return CUR + parseFloat(n || 0).toFixed(2);
    }

    function statusBadge(status) {
        const map = {
            active:        'success',
            low_stock:     'warning',
            out_of_stock:  'danger',
            expiring_soon: 'warning',
        };
        const color = map[status] || 'secondary';
        return `<span class="badge bg-${color}">${escapeHtml(status.replace('_', ' '))}</span>`;
    }

    // ─── Load products ────────────────────────────────────────────────────────
    function loadProducts() {
        const search   = document.getElementById('filterSearch')?.value   || '';
        const category = document.getElementById('filterCategory')?.value || '';
        const status   = document.getElementById('filterStatus')?.value   || '';

        const params = new URLSearchParams({ action: 'list' });
        if (search)   params.set('search',      search);
        if (category) params.set('category_id', category);
        if (status)   params.set('status',       status);

        apiFetch(BASE + 'api/products.php?' + params)
            .then(r => {
                const tbody = document.getElementById('productsBody');
                if (!tbody) return;

                if (!r.success || !r.data.length) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">No products found.</td></tr>';
                    return;
                }

                tbody.innerHTML = r.data.map(p => {
                    const imgSrc = p.image
                        ? BASE + 'uploads/products/' + escapeHtml(p.image)
                        : BASE + 'assets/img/placeholder.png';
                    const expiry = p.expiry_date && p.expiry_date !== '0000-00-00' ? p.expiry_date : '—';
                    return `<tr>
                        <td><img src="${imgSrc}" alt="" width="44" height="44" style="object-fit:cover;border-radius:4px;"></td>
                        <td><strong>${escapeHtml(p.name)}</strong></td>
                        <td><code class="small">${escapeHtml(p.barcode || '—')}</code></td>
                        <td>${escapeHtml(p.category_name || '—')}</td>
                        <td class="text-end">${fmt(p.cost_price)}</td>
                        <td class="text-end">${fmt(p.selling_price)}</td>
                        <td class="text-end">${parseInt(p.stock_qty)}</td>
                        <td>${statusBadge(p.status)}</td>
                        <td class="small">${expiry}</td>
                        <td>
                            <a href="${BASE}?module=products&action=edit&id=${p.id}"
                               class="btn btn-xs btn-sm btn-outline-warning me-1" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-xs btn-sm btn-outline-danger btn-delete"
                                    data-id="${p.id}" data-name="${escapeHtml(p.name)}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                }).join('');

                // Attach delete handlers
                document.querySelectorAll('.btn-delete').forEach(btn => {
                    btn.addEventListener('click', function () {
                        if (!confirm('Delete "' + this.dataset.name + '"?')) return;
                        document.getElementById('deleteProductId').value = this.dataset.id;
                        document.getElementById('deleteForm').submit();
                    });
                });
            })
            .catch(() => {});
    }

    // ─── CSV Import ───────────────────────────────────────────────────────────
    function handleImport(e) {
        e.preventDefault();
        const form     = document.getElementById('importForm');
        const result   = document.getElementById('importResult');
        const formData = new FormData(form);
        formData.append('action', 'import');

        apiFetch(BASE + 'api/products.php', { method: 'POST', body: formData })
            .then(r => {
                if (r.success) {
                    let html = `<div class="alert alert-success">Imported ${r.count} product(s).</div>`;
                    if (r.errors && r.errors.length) {
                        html += '<ul class="small text-danger mb-0">' +
                            r.errors.map(e => `<li>${escapeHtml(e)}</li>`).join('') + '</ul>';
                    }
                    result.innerHTML = html;
                    loadProducts();
                } else {
                    result.innerHTML = `<div class="alert alert-danger">${escapeHtml(r.error || 'Import failed')}</div>`;
                }
            })
            .catch(() => {
                result.innerHTML = '<div class="alert alert-danger">Import request failed.</div>';
            });
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        loadProducts();

        document.getElementById('btnFilterProducts')?.addEventListener('click', loadProducts);
        document.getElementById('filterSearch')?.addEventListener('keydown', e => {
            if (e.key === 'Enter') loadProducts();
        });
        document.getElementById('importForm')?.addEventListener('submit', handleImport);
    });
})();
