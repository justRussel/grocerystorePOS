/**
 * GroceryPOS – pos.js
 * Cart state management, product search, barcode scanner, checkout flow.
 */
(function () {
    'use strict';

    const BASE = window.BASE_URL || '/';
    const CUR  = window.CURRENCY || '₱';

    // ─── Cart State ───────────────────────────────────────────────────────────
    let cartState = {
        items:         [],
        discount:      0,
        discountType:  'fixed',
        paymentMethod: 'cash',
        cashTendered:  0,
    };

    let allProducts      = [];
    let activeCategory   = '';
    let searchDebounce   = null;
    let barcodeBuffer    = '';
    let barcodeTimer     = null;

    function fmt(n) {
        return CUR + parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ─── Product Grid ─────────────────────────────────────────────────────────
    function loadProducts(keyword, categoryId) {
        const params = new URLSearchParams({ action: 'search', keyword: keyword || '' });
        if (categoryId) params.set('category_id', categoryId);

        apiFetch(BASE + 'api/products.php?' + params)
            .then(r => {
                if (!r.success) return;
                allProducts = r.data;
                renderProductGrid(r.data);
            })
            .catch(() => {});
    }

    function renderProductGrid(products) {
        const grid = document.getElementById('productGrid');
        if (!grid) return;

        if (!products.length) {
            grid.innerHTML = '<div class="col-12 text-center text-muted py-4">No products found.</div>';
            return;
        }

        grid.innerHTML = products.map(p => {
            const imgSrc   = p.image
                ? BASE + 'uploads/products/' + escapeHtml(p.image)
                : BASE + 'assets/img/placeholder.png';
            const outStock = parseInt(p.stock_qty) === 0;
            return `<div class="col-6 col-sm-4 col-md-3 col-xl-2">
                <div class="card product-card ${outStock ? 'opacity-50' : ''}"
                     data-id="${p.id}" ${outStock ? 'style="pointer-events:none"' : ''}>
                    <img src="${imgSrc}" class="card-img-top" alt="${escapeHtml(p.name)}"
                         onerror="this.src='${BASE}assets/img/placeholder.png'">
                    <div class="card-body">
                        <div class="card-title">${escapeHtml(p.name)}</div>
                        <div class="product-price">${fmt(p.selling_price)}</div>
                        <div class="product-stock">${outStock ? '<span class="text-danger">Out of stock</span>' : 'Stock: ' + p.stock_qty}</div>
                    </div>
                </div>
            </div>`;
        }).join('');

        grid.querySelectorAll('.product-card:not([style*="pointer-events"])').forEach(card => {
            card.addEventListener('click', function () {
                const product = allProducts.find(p => p.id == this.dataset.id);
                if (product) addToCart(product);
            });
        });
    }

    // ─── Cart Operations ──────────────────────────────────────────────────────
    function addToCart(product) {
        const existing = cartState.items.find(i => i.product_id === product.id);
        if (existing) {
            if (existing.qty >= parseInt(product.stock_qty)) {
                showToast('Not enough stock for ' + product.name, 'warning');
                return;
            }
            existing.qty++;
        } else {
            cartState.items.push({
                product_id:    product.id,
                name:          product.name,
                selling_price: parseFloat(product.selling_price),
                cost_price:    parseFloat(product.cost_price),
                stock_qty:     parseInt(product.stock_qty),
                qty:           1,
            });
        }
        renderCart();
        showToast(product.name + ' added to cart', 'success');
    }

    function removeFromCart(productId) {
        cartState.items = cartState.items.filter(i => i.product_id !== productId);
        renderCart();
    }

    function updateQty(productId, qty) {
        const item = cartState.items.find(i => i.product_id === productId);
        if (!item) return;
        qty = parseInt(qty);
        if (qty <= 0) { removeFromCart(productId); return; }
        if (qty > item.stock_qty) { showToast('Not enough stock', 'warning'); qty = item.stock_qty; }
        item.qty = qty;
        renderCart();
    }

    function clearCart() {
        cartState.items        = [];
        cartState.discount     = 0;
        cartState.cashTendered = 0;
        const dv = document.getElementById('discountValue');
        const ct = document.getElementById('cashTendered');
        if (dv) dv.value = 0;
        if (ct) ct.value = 0;
        renderCart();
    }

    // ─── Render Cart ──────────────────────────────────────────────────────────
    function renderCart() {
        const container = document.getElementById('cartItems');
        if (!container) return;

        if (!cartState.items.length) {
            container.innerHTML = '<p class="text-muted text-center mt-4 small">Cart is empty</p>';
            calculateTotals();
            return;
        }

        container.innerHTML = cartState.items.map(item => `
            <div class="cart-item" data-id="${item.product_id}">
                <span class="cart-item-name" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</span>
                <div class="cart-item-qty">
                    <button class="btn btn-outline-secondary btn-qty-minus" data-id="${item.product_id}">−</button>
                    <input type="number" class="form-control input-qty" value="${item.qty}"
                           min="1" max="${item.stock_qty}" data-id="${item.product_id}">
                    <button class="btn btn-outline-secondary btn-qty-plus" data-id="${item.product_id}">+</button>
                </div>
                <span class="cart-item-total">${fmt(item.selling_price * item.qty)}</span>
                <button class="btn btn-link btn-sm text-danger p-0 ms-1 btn-remove" data-id="${item.product_id}">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>`
        ).join('');

        // Bind qty controls
        container.querySelectorAll('.btn-qty-minus').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = cartState.items.find(i => i.product_id == btn.dataset.id);
                if (item) updateQty(item.product_id, item.qty - 1);
            });
        });
        container.querySelectorAll('.btn-qty-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = cartState.items.find(i => i.product_id == btn.dataset.id);
                if (item) updateQty(item.product_id, item.qty + 1);
            });
        });
        container.querySelectorAll('.input-qty').forEach(input => {
            input.addEventListener('change', function () {
                updateQty(parseInt(this.dataset.id), parseInt(this.value));
            });
        });
        container.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', () => removeFromCart(parseInt(btn.dataset.id)));
        });

        calculateTotals();
    }

    // ─── Calculate Totals ─────────────────────────────────────────────────────
    function calculateTotals() {
        let subtotal = 0;
        cartState.items.forEach(i => { subtotal += i.selling_price * i.qty; });

        const discountVal  = parseFloat(document.getElementById('discountValue')?.value || 0);
        const discountType = document.getElementById('discountType')?.value || 'fixed';
        cartState.discount     = discountVal;
        cartState.discountType = discountType;

        let discountAmount = 0;
        if (discountType === 'percent') {
            discountAmount = subtotal * Math.min(100, Math.max(0, discountVal)) / 100;
        } else {
            discountAmount = Math.min(discountVal, subtotal);
        }

        const total        = Math.max(0, subtotal - discountAmount);
        const cashTendered = parseFloat(document.getElementById('cashTendered')?.value || 0);
        const change       = Math.max(0, cashTendered - total);

        document.getElementById('cartSubtotal').textContent = fmt(subtotal);
        document.getElementById('cartDiscount').textContent = '- ' + fmt(discountAmount);
        document.getElementById('cartTotal').textContent    = fmt(total);
        document.getElementById('changeAmount').textContent = fmt(change);
        cartState.cashTendered = cashTendered;
    }

    // ─── Checkout ─────────────────────────────────────────────────────────────
    function doCheckout() {
        if (!cartState.items.length) {
            showToast('Cart is empty.', 'warning');
            return;
        }
        if (cartState.paymentMethod === 'cash') {
            const total        = parseFloat(document.getElementById('cartTotal').textContent.replace(/[^\d.]/g, ''));
            const cashTendered = parseFloat(document.getElementById('cashTendered')?.value || 0);
            if (cashTendered < total) {
                showToast('Cash tendered is less than total.', 'danger');
                return;
            }
        }

        const payload = {
            cart:            cartState.items,
            discount_value:  cartState.discount,
            discount_type:   cartState.discountType,
            payment_method:  cartState.paymentMethod,
            cash_tendered:   cartState.cashTendered,
        };

        apiFetch(BASE + 'api/pos.php?action=checkout', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        }).then(r => {
            if (!r.success) {
                showToast(r.error || 'Checkout failed.', 'danger');
                return;
            }
            clearCart();
            showReceipt(r.receipt);
            showToast('Checkout complete! Receipt #' + r.receipt_no, 'success');
        }).catch(() => {});
    }

    // ─── Receipt modal ────────────────────────────────────────────────────────
    function showReceipt(receipt) {
        if (!receipt || !receipt.transaction) return;
        const t        = receipt.transaction;
        const items    = receipt.items || [];
        const storeName = receipt.store_name || 'GroceryPOS';

        let html = `<div class="receipt-container">
            <div class="receipt-header text-center mb-2">
                <strong>${escapeHtml(storeName)}</strong><br>
                <small>Receipt: ${escapeHtml(t.receipt_no)}</small><br>
                <small>${escapeHtml(t.created_at || '')}</small>
            </div>
            <table class="table table-sm table-borderless mb-1">
                <tbody>`;
        items.forEach(item => {
            html += `<tr>
                <td>${escapeHtml(item.product_name_snapshot)}</td>
                <td class="text-end">x${item.quantity}</td>
                <td class="text-end">${fmt(item.line_total)}</td>
            </tr>`;
        });
        html += `</tbody></table>
            <hr class="my-1">
            <div class="d-flex justify-content-between small"><span>Subtotal</span><span>${fmt(t.subtotal)}</span></div>`;
        if (parseFloat(t.discount_amount) > 0) {
            html += `<div class="d-flex justify-content-between small"><span>Discount</span><span>-${fmt(t.discount_amount)}</span></div>`;
        }
        html += `<div class="d-flex justify-content-between fw-bold"><span>TOTAL</span><span>${fmt(t.total_amount)}</span></div>`;
        if (t.cash_tendered) {
            html += `<div class="d-flex justify-content-between small"><span>Cash</span><span>${fmt(t.cash_tendered)}</span></div>
                <div class="d-flex justify-content-between small"><span>Change</span><span>${fmt(t.change_amount)}</span></div>`;
        }
        html += `<div class="text-center mt-2 small">Thank you!</div></div>`;

        const content = document.getElementById('receiptContent');
        if (content) content.innerHTML = html;

        const modal = bootstrap.Modal.getOrCreate(document.getElementById('receiptModal'));
        modal.show();
    }

    function printReceipt() {
        const content = document.getElementById('receiptContent');
        if (!content) return;
        const win = window.open('', '_blank', 'width=320,height=600');
        win.document.write('<html><head><title>Receipt</title></head><body>' + content.innerHTML + '</body></html>');
        win.document.close();
        win.print();
    }

    // ─── Barcode Scanner ──────────────────────────────────────────────────────
    function handleBarcodeInput(char) {
        barcodeBuffer += char;
        clearTimeout(barcodeTimer);
        barcodeTimer = setTimeout(() => {
            if (barcodeBuffer.length >= 4) {
                // Treat as barcode scan
                apiFetch(BASE + 'api/products.php?action=get&barcode=' + encodeURIComponent(barcodeBuffer))
                    .then(r => {
                        if (r.success && r.data) {
                            addToCart(r.data);
                            const searchEl = document.getElementById('posSearch');
                            if (searchEl) searchEl.value = '';
                        }
                    })
                    .catch(() => {});
            }
            barcodeBuffer = '';
        }, 100);
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Load initial products
        loadProducts('', '');

        // Search with debounce
        const searchEl = document.getElementById('posSearch');
        if (searchEl) {
            searchEl.addEventListener('input', function () {
                clearTimeout(searchDebounce);
                const val = this.value.trim();
                searchDebounce = setTimeout(() => loadProducts(val, activeCategory), 300);
            });

            searchEl.addEventListener('keypress', function (e) {
                if (this.dataset.barcodeMode) return;
                handleBarcodeInput(e.key);
            });
        }

        // Category filter buttons
        document.querySelectorAll('#categoryFilters button').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#categoryFilters button').forEach(b => b.classList.remove('active', 'btn-success'));
                document.querySelectorAll('#categoryFilters button').forEach(b => b.classList.add('btn-outline-secondary'));
                this.classList.remove('btn-outline-secondary');
                this.classList.add('active', 'btn-success');
                activeCategory = this.dataset.cat;
                const kw = searchEl ? searchEl.value.trim() : '';
                loadProducts(kw, activeCategory);
            });
        });

        // Discount changes
        document.getElementById('discountValue')?.addEventListener('input', calculateTotals);
        document.getElementById('discountType')?.addEventListener('change', calculateTotals);

        // Cash tendered
        document.getElementById('cashTendered')?.addEventListener('input', calculateTotals);

        // Payment method toggle
        document.querySelectorAll('#paymentMethodBtns button').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#paymentMethodBtns button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                cartState.paymentMethod = this.dataset.pm;
                const cashRow = document.getElementById('cashTenderedRow');
                if (cashRow) cashRow.style.display = cartState.paymentMethod === 'cash' ? '' : 'none';
            });
        });

        // Checkout
        document.getElementById('btnCheckout')?.addEventListener('click', doCheckout);

        // Clear
        document.getElementById('btnClearCart')?.addEventListener('click', function () {
            if (cartState.items.length && !confirm('Clear cart?')) return;
            clearCart();
        });

        // Print
        document.getElementById('btnPrintReceipt')?.addEventListener('click', printReceipt);

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            if (e.key === 'F2') {
                e.preventDefault();
                document.getElementById('posSearch')?.focus();
            }
            if (e.key === 'F12') {
                e.preventDefault();
                doCheckout();
            }
            if (e.key === 'Escape') {
                clearCart();
            }
        });

        renderCart();
    });
})();
