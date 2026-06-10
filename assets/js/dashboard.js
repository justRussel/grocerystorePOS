/**
 * GroceryPOS – dashboard.js
 * Loads dashboard metrics, populates tables and renders Chart.js bar chart.
 */
(function () {
    'use strict';

    const BASE = window.BASE_URL || '/grocerypos/';
    const CUR  = window.CURRENCY || '₱';
    let   monthlyChart = null;

    function fmt(n) {
        return CUR + parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    // ─── Fetch metrics ────────────────────────────────────────────────────────
    function loadMetrics() {
        apiFetch(BASE + 'api/dashboard.php?action=metrics')
            .then(r => {
                if (!r.success) return;
                const d = r.data;
                setText('kpi-revenue',      fmt(d.revenue));
                setText('kpi-profit',       fmt(d.profit));
                setText('kpi-transactions', parseInt(d.transactions || 0));
                setText('kpi-items',        parseInt(d.items_sold  || 0));
            })
            .catch(() => {});
    }

    // ─── Fetch alerts ─────────────────────────────────────────────────────────
    function loadAlerts() {
        apiFetch(BASE + 'api/dashboard.php?action=alerts')
            .then(r => {
                if (!r.success) return;
                setText('kpi-low-stock', r.data.low_stock);
                setText('kpi-expiring',  r.data.expiring);
            })
            .catch(() => {});
    }

    // ─── Best sellers ─────────────────────────────────────────────────────────
    function loadBestSellers() {
        apiFetch(BASE + 'api/dashboard.php?action=best_sellers')
            .then(r => {
                const tbody = document.getElementById('best-sellers-body');
                if (!tbody) return;
                if (!r.success || !r.data.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-2">No sales today</td></tr>';
                    return;
                }
                tbody.innerHTML = r.data.map((p, i) =>
                    `<tr>
                        <td>${i + 1}</td>
                        <td>${escapeHtml(p.name)}</td>
                        <td class="text-end">${parseInt(p.qty_sold)}</td>
                        <td class="text-end">${fmt(p.revenue)}</td>
                    </tr>`
                ).join('');
            })
            .catch(() => {});
    }

    // ─── Recent transactions ──────────────────────────────────────────────────
    function loadRecentTransactions() {
        apiFetch(BASE + 'api/dashboard.php?action=recent_transactions')
            .then(r => {
                const tbody = document.getElementById('recent-transactions-body');
                if (!tbody) return;
                if (!r.success || !r.data.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-2">No transactions yet</td></tr>';
                    return;
                }
                tbody.innerHTML = r.data.map(t => {
                    const time = new Date(t.created_at).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
                    return `<tr>
                        <td><span class="font-monospace small">${escapeHtml(t.receipt_no)}</span></td>
                        <td class="text-end">${fmt(t.total_amount)}</td>
                        <td><span class="badge bg-${t.payment_method === 'cash' ? 'success' : 'primary'}">${escapeHtml(t.payment_method)}</span></td>
                        <td>${escapeHtml(t.cashier_name || 'N/A')}</td>
                        <td class="small">${time}</td>
                    </tr>`;
                }).join('');
            })
            .catch(() => {});
    }

    // ─── Monthly chart ────────────────────────────────────────────────────────
    function loadMonthlyChart() {
        const year = new Date().getFullYear();
        apiFetch(BASE + 'api/dashboard.php?action=monthly_chart&year=' + year)
            .then(r => {
                if (!r.success) return;

                const labels   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                const revenues = r.data.map(d => parseFloat(d.revenue || 0));
                const profits  = r.data.map(d => parseFloat(d.profit  || 0));

                const ctx = document.getElementById('monthlyChart');
                if (!ctx) return;

                if (monthlyChart) monthlyChart.destroy();

                monthlyChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: revenues,
                                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                                borderColor: '#198754',
                                borderWidth: 1,
                            },
                            {
                                label: 'Profit',
                                data: profits,
                                backgroundColor: 'rgba(13, 202, 240, 0.7)',
                                borderColor: '#0dcaf0',
                                borderWidth: 1,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'top' } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: v => CUR + v.toLocaleString() },
                            },
                        },
                    },
                });
            })
            .catch(() => {});
    }

    // ─── Initial load + auto-refresh ─────────────────────────────────────────
    function loadAll() {
        loadMetrics();
        loadAlerts();
        loadBestSellers();
        loadRecentTransactions();
        loadMonthlyChart();
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadAll();
        setInterval(loadAll, 60000); // refresh every 60 seconds
    });
})();
