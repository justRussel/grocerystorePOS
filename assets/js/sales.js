/**
 * GroceryPOS – sales.js
 * Period tab switching, analytics AJAX, Chart.js rendering.
 */
(function () {
    'use strict';

    const BASE = window.BASE_URL || '/grocerypos/';
    const CUR  = window.CURRENCY || '₱';
    let   salesChart = null;
    let   activePeriod = 'daily';

    function fmt(n) {
        return CUR + parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    // ─── Build API URL based on period ────────────────────────────────────────
    function buildUrl(period, customFrom, customTo) {
        const base = BASE + 'api/sales.php?action=';
        const now  = new Date();
        switch (period) {
            case 'daily':
                return base + 'daily&date=' + now.toISOString().split('T')[0];
            case 'weekly': {
                const mon = new Date(now);
                mon.setDate(now.getDate() - ((now.getDay() + 6) % 7));
                return base + 'weekly&week_start=' + mon.toISOString().split('T')[0];
            }
            case 'monthly':
                return base + 'monthly&year=' + now.getFullYear() + '&month=' + (now.getMonth() + 1);
            case 'yearly':
                return base + 'yearly&year=' + now.getFullYear();
            case 'custom':
                return BASE + 'api/sales.php?action=daily&date=' + customFrom +
                    '&_cf=' + customFrom + '&_ct=' + customTo; // will use product_performance fallback
        }
        return base + 'daily';
    }

    function buildPerfUrl(period, customFrom, customTo) {
        const now = new Date();
        let from, to;
        switch (period) {
            case 'daily':
                from = to = now.toISOString().split('T')[0]; break;
            case 'weekly': {
                const mon = new Date(now);
                mon.setDate(now.getDate() - ((now.getDay() + 6) % 7));
                from = mon.toISOString().split('T')[0];
                const sun = new Date(mon); sun.setDate(mon.getDate() + 6);
                to = sun.toISOString().split('T')[0]; break;
            }
            case 'monthly':
                from = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-01';
                to   = now.toISOString().split('T')[0]; break;
            case 'yearly':
                from = now.getFullYear() + '-01-01';
                to   = now.getFullYear() + '-12-31'; break;
            case 'custom':
                from = customFrom; to = customTo; break;
            default:
                from = to = now.toISOString().split('T')[0];
        }
        return BASE + 'api/sales.php?action=product_performance&date_from=' + from + '&date_to=' + to;
    }

    // ─── Load analytics ───────────────────────────────────────────────────────
    function loadAnalytics(period, customFrom, customTo) {
        activePeriod = period;

        // For custom, use a summary approach
        let summaryUrl;
        if (period === 'custom') {
            summaryUrl = BASE + 'api/transactions.php?action=summary&date_from=' + customFrom + '&date_to=' + customTo;
        } else {
            summaryUrl = buildUrl(period, customFrom, customTo);
        }

        apiFetch(summaryUrl)
            .then(r => {
                if (!r.success) return;
                const d = r.data;
                setText('s-revenue',      fmt(d.total_revenue     ?? d.revenue      ?? 0));
                setText('s-profit',       fmt(d.total_profit      ?? d.profit       ?? 0));
                setText('s-transactions', parseInt(d.transaction_count ?? d.transactions ?? 0));
                setText('s-items',        parseInt(d.items_sold   ?? 0));

                // Render mini bar chart with available data
                renderSalesChart(period, d);
            })
            .catch(() => {});

        // Product performance
        apiFetch(buildPerfUrl(period, customFrom, customTo))
            .then(r => {
                const tbody = document.getElementById('productPerfBody');
                if (!tbody) return;
                if (!r.success || !r.data.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>';
                    return;
                }
                tbody.innerHTML = r.data.map(p => `<tr>
                    <td>${escapeHtml(p.name)}</td>
                    <td class="text-end">${parseInt(p.qty_sold)}</td>
                    <td class="text-end">${fmt(p.revenue)}</td>
                    <td class="text-end">${fmt(p.profit)}</td>
                </tr>`).join('');
            })
            .catch(() => {});
    }

    function renderSalesChart(period, data) {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;
        if (salesChart) salesChart.destroy();

        const revenue = parseFloat(data.total_revenue ?? data.revenue ?? 0);
        const profit  = parseFloat(data.total_profit  ?? data.profit  ?? 0);

        salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Revenue', 'Profit'],
                datasets: [{
                    data:            [revenue, profit],
                    backgroundColor: ['rgba(25,135,84,0.7)', 'rgba(13,202,240,0.7)'],
                    borderColor:     ['#198754', '#0dcaf0'],
                    borderWidth:     1,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => CUR + v.toLocaleString() } },
                },
            },
        });
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Period tab switching
        document.querySelectorAll('#salesTabs button').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#salesTabs button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const period = this.dataset.period;

                const customRow = document.getElementById('customRangeRow');
                if (customRow) customRow.classList.toggle('d-none', period !== 'custom');

                if (period !== 'custom') loadAnalytics(period);
            });
        });

        document.getElementById('btnCustomSearch')?.addEventListener('click', function () {
            const from = document.getElementById('customFrom')?.value;
            const to   = document.getElementById('customTo')?.value;
            if (from && to) loadAnalytics('custom', from, to);
        });

        loadAnalytics('daily');
    });
})();
