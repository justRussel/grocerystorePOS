/**
 * GroceryPOS – suppliers.js
 * Supplier list AJAX, Add/Edit/Delete modal, PO management on profile page.
 */
(function () {
    'use strict';

    const BASE = window.BASE_URL || '/grocerypos/';
    const CUR  = window.CURRENCY || '₱';

    function fmt(n) {
        return CUR + parseFloat(n || 0).toFixed(2);
    }

    // ─── Load suppliers ───────────────────────────────────────────────────────
    function loadSuppliers() {
        apiFetch(BASE + 'api/suppliers.php?action=list')
            .then(r => {
                const tbody = document.getElementById('suppliersBody');
                if (!tbody) return;
                if (!r.success || !r.data.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No suppliers yet.</td></tr>';
                    return;
                }
                tbody.innerHTML = r.data.map(s => `<tr>
                    <td>
                        <a href="${BASE}?module=suppliers&action=profile&id=${s.id}" class="fw-semibold text-decoration-none">
                            ${escapeHtml(s.company_name)}
                        </a>
                    </td>
                    <td>${escapeHtml(s.contact_person || '—')}</td>
                    <td>${escapeHtml(s.phone || '—')}</td>
                    <td>${escapeHtml(s.email || '—')}</td>
                    <td class="text-center">${s.product_count || 0}</td>
                    <td class="small">${s.last_order_date ? new Date(s.last_order_date).toLocaleDateString('en-PH') : '—'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-warning me-1 btn-edit-supplier"
                                data-id="${s.id}"
                                data-company="${escapeHtml(s.company_name)}"
                                data-contact="${escapeHtml(s.contact_person || '')}"
                                data-phone="${escapeHtml(s.phone || '')}"
                                data-email="${escapeHtml(s.email || '')}"
                                data-address="${escapeHtml(s.address || '')}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-supplier" data-id="${s.id}" data-name="${escapeHtml(s.company_name)}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`).join('');

                // Edit handlers
                document.querySelectorAll('.btn-edit-supplier').forEach(btn => {
                    btn.addEventListener('click', function () {
                        openModal('edit', this.dataset);
                    });
                });

                // Delete handlers
                document.querySelectorAll('.btn-delete-supplier').forEach(btn => {
                    btn.addEventListener('click', function () {
                        if (!confirm('Delete supplier "' + this.dataset.name + '"?')) return;
                        apiFetch(BASE + 'api/suppliers.php?action=delete', {
                            method: 'POST', headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({action: 'delete', id: parseInt(this.dataset.id)}),
                        }).then(r => {
                            if (r.success) { showToast('Supplier deleted.', 'success'); loadSuppliers(); }
                            else showToast(r.error || 'Failed', 'danger');
                        }).catch(() => {});
                    });
                });
            })
            .catch(() => {});
    }

    // ─── Modal helpers ────────────────────────────────────────────────────────
    function openModal(mode, data) {
        const modal   = document.getElementById('supplierModal');
        const title   = document.getElementById('supplierModalTitle');
        if (!modal) return;

        document.getElementById('supplierId').value      = data?.id     || '';
        document.getElementById('supplierCompany').value  = data?.company || '';
        document.getElementById('supplierContact').value  = data?.contact || '';
        document.getElementById('supplierPhone').value    = data?.phone   || '';
        document.getElementById('supplierEmail').value    = data?.email   || '';
        document.getElementById('supplierAddress').value  = data?.address || '';

        if (title) title.textContent = mode === 'edit' ? 'Edit Supplier' : 'Add Supplier';

        bootstrap.Modal.getOrCreate(modal).show();
    }

    // ─── Save supplier ────────────────────────────────────────────────────────
    function saveSupplier() {
        const id      = document.getElementById('supplierId')?.value;
        const payload = {
            action:         id ? 'update' : 'create',
            id:             id ? parseInt(id) : undefined,
            company_name:   document.getElementById('supplierCompany')?.value.trim(),
            contact_person: document.getElementById('supplierContact')?.value.trim(),
            phone:          document.getElementById('supplierPhone')?.value.trim(),
            email:          document.getElementById('supplierEmail')?.value.trim(),
            address:        document.getElementById('supplierAddress')?.value.trim(),
        };

        if (!payload.company_name) { showToast('Company name is required.', 'warning'); return; }

        const url = BASE + 'api/suppliers.php?action=' + payload.action;
        apiFetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        }).then(r => {
            if (r.success) {
                showToast(id ? 'Supplier updated.' : 'Supplier added.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('supplierModal'))?.hide();
                loadSuppliers();
            } else {
                showToast(r.error || 'Failed to save.', 'danger');
            }
        }).catch(() => {});
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('suppliersBody')) {
            loadSuppliers();
        }

        document.getElementById('btnAddSupplier')?.addEventListener('click', () => openModal('add', {}));
        document.getElementById('btnSaveSupplier')?.addEventListener('click', saveSupplier);
    });
})();
