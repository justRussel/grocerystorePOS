/**
 * GroceryPOS – transactions.js
 * Transaction history with pagination, filters, receipt modal.
 */
(function () {
    'use strict';

    const BASE      = window.BASE_URL || '/';
    const CUR       = window.CURRENCY || '₱';
    const PAGE_SIZE = 20;
    let   currentPage = 0;
    let   totalTxns   = 0;

    function fmt(n) {
        return CUR + parseFloat(n || 0).toFixed(2);
    }

    // ─── Load transactions ────────────────────────────────────────────────────
    function loadTransactions(page) {
        currentPage = page || 0;

        const receipt = document.getElementById('filterReceipt')?.value || '';
        const from    = document.getElementById('filterFrom')?.value    || '';
        const to      = document.getElementById('filterTo')?.value      || '';
        const payment = document.getElementById('filterPayment')?.value  || '';

        const params = new URLSearchParams({
            action: 'list',
            limit:  PAGE_SIZE,
            offset: currentPage * PAGE_SIZE,
        });
        if (receipt) params.set('receipt_no',     receipt);
        if (from)    params.set('date_from',       from);
        if (to)      params.set('date_to',         to);
        if (payment) params.set('payment_method',  payment);

        apiFetch(BASE + 'api/transactions.php?' + params)
            .then(r => {
                const tbody = document.getElementById('transactionsBody');
                if (!tbody) return;
                totalTxns = r.total || 0;

                if (!r.success || !r.data.length) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No transactions found.</td></tr>';
                    renderPagination();
                    return;
                }

                tbody.innerHTML = r.data.map(t => {
                    const dt = new Date(t.created_at).toLocaleString('en-PH');
                    return `<tr>
                        <td><span class="font-monospace small">${escapeHtml(t.receipt_no)}</span></td>
                        <td class="text-end">${fmt(t.subtotal)}</td>
                        <td class="text-end text-danger">${t.discount_amount > 0 ? '-' + fmt(t.discount_amount) : '—'}</td>
                        <td class="text-end fw-bold">${fmt(t.total_amount)}</td>
                        <td><span class="badge bg-${t.payment_method === 'cash' ? 'success' : 'primary'}">${escapeHtml(t.payment_method)}</span></td>
                        <td>${escapeHtml(t.cashier_name || 'N/A')}</td>
                        <td class="small">${dt}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-success btn-view-receipt" data-id="${t.id}" title="View Receipt">
                                <i class="bi bi-receipt"></i>
                            </button>
                        </td>
                    </tr>`;
                }).join('');

                // Attach receipt view handlers
                document.querySelectorAll('.btn-view-receipt').forEach(btn => {
                    btn.addEventListener('click', function () {
                        viewReceipt(parseInt(this.dataset.id));
                    });
                });

                renderPagination();
            })
            .catch(() => {});
    }

    // ─── Pagination ───────────────────────────────────────────────────────────
    function renderPagination() {
        const container = document.getElementById('txnPagination');
        if (!container) return;

        const totalPages = Math.ceil(totalTxns / PAGE_SIZE);
        const from       = totalTxns ? currentPage * PAGE_SIZE + 1 : 0;
        const to         = Math.min((currentPage + 1) * PAGE_SIZE, totalTxns);

        container.innerHTML = `
            <span class="small text-muted">Showing ${from}–${to} of ${totalTxns}</span>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" id="btnPrevPage" ${currentPage === 0 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" id="btnNextPage" ${currentPage >= totalPages - 1 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>`;

        document.getElementById('btnPrevPage')?.addEventListener('click', () => loadTransactions(currentPage - 1));
        document.getElementById('btnNextPage')?.addEventListener('click', () => loadTransactions(currentPage + 1));
    }

    // ─── Receipt modal ────────────────────────────────────────────────────────
    function viewReceipt(id) {
        apiFetch(BASE + 'api/transactions.php?action=get&id=' + id)
            .then(r => {
                if (!r.success || !r.data) { showToast('Receipt not found.', 'danger'); return; }
                const t     = r.data;
                const items = t.items || [];
                let html    = `<div class="receipt-container">
                    <div class="text-center mb-2">
                        <strong>${escapeHtml(t.receipt_no)}</strong><br>
                        <small>${escapeHtml(t.created_at)}</small><br>
                        <small>Cashier: ${escapeHtml(t.cashier_name || 'N/A')}</small>
                    </div>
                    <table class="table table-sm table-borderless mb-1"><tbody>`;
                items.forEach(item => {
                    html += `<tr>
                        <td>${escapeHtml(item.product_name_snapshot)}</td>
                        <td class="text-end">x${item.quantity}</td>
                        <td class="text-end">${fmt(item.line_total)}</td>
                    </tr>`;
                });
                html += `</tbody></table><hr class="my-1">
                    <div class="d-flex justify-content-between small"><span>Subtotal</span><span>${fmt(t.subtotal)}</span></div>`;
                if (parseFloat(t.discount_amount) > 0) {
                    html += `<div class="d-flex justify-content-between small"><span>Discount</span><span>-${fmt(t.discount_amount)}</span></div>`;
                }
                html += `<div class="d-flex justify-content-between fw-bold"><span>TOTAL</span><span>${fmt(t.total_amount)}</span></div>`;
                if (t.cash_tendered) {
                    html += `<div class="d-flex justify-content-between small"><span>Cash</span><span>${fmt(t.cash_tendered)}</span></div>
                        <div class="d-flex justify-content-between small"><span>Change</span><span>${fmt(t.change_amount)}</span></div>`;
                }
                html += `<div class="text-center small mt-2">Payment: ${escapeHtml(t.payment_method)}</div></div>`;

                const content = document.getElementById('receiptContent');
                if (content) content.innerHTML = html;

                bootstrap.Modal.getOrCreate(document.getElementById('receiptModal')).show();
            })
            .catch(() => {});
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        loadTransactions(0);

        document.getElementById('btnSearch')?.addEventListener('click', () => loadTransactions(0));
        document.getElementById('btnReset')?.addEventListener('click', function () {
            ['filterReceipt', 'filterPayment'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            const d  = new Date();
            const m1 = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-01';
            const m2 = d.toISOString().split('T')[0];
            const fFrom = document.getElementById('filterFrom');
            const fTo   = document.getElementById('filterTo');
            if (fFrom) fFrom.value = m1;
            if (fTo)   fTo.value   = m2;
            loadTransactions(0);
        });

        document.getElementById('filterReceipt')?.addEventListener('keydown', e => {
            if (e.key === 'Enter') loadTransactions(0);
        });
    });
})();
