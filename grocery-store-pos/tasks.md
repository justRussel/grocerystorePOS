# Implementation Plan: Grocery Store POS System

## Overview

Build a full-featured, browser-based Grocery Store Point of Sale system using PHP 8.x, MySQL 8.x, and vanilla HTML/CSS/JavaScript with Bootstrap 5. The system runs on XAMPP (localhost) and covers sales transactions, inventory management, supplier tracking, analytics reporting, and account settings, all denominated in Philippine Peso (₱).

## Tasks

- [-] 1. Project Foundation & Database Setup
  - Create `c:\xampp\htdocs\grocerypos\` root with all required subdirectories (app/controllers, app/models, app/views layout+modules, api, assets/css assets/js assets/img, uploads/products, exports/pdf, exports/excel)
  - Write `composer.json` with tecnickcom/tcpdf ^6.6, phpoffice/phpspreadsheet ^2.1, phpunit/phpunit ^10.0 (dev)
  - Write `config.php` with DB credentials (localhost, root, no password, db: grocerypos) and app constants (APP_NAME, BASE_URL, UPLOAD_PATH, EXPORT_PATH)
  - Write `grocerypos.sql` with all CREATE TABLE statements in dependency order (users, categories, products, transactions, transaction_items, stock_movements, suppliers, supplier_products, purchase_orders, purchase_order_items)
  - Write `.htaccess` for URL rewriting and directory protection of uploads/ and exports/
  - Write seed SQL: default admin user (bcrypt-hashed password) and 8 default product categories

- [x] 2. Core PHP Infrastructure (Router, Database Singleton, Layout Views)
  - Write `index.php` front controller: parse `?module=` and `?action=` query params, require config.php, dispatch to controller classes, default to dashboard
  - Write `app/models/Database.php`: PDO singleton with getInstance(), utf8mb4 charset, ERRMODE_EXCEPTION
  - Write `app/views/layout/header.php`: Bootstrap 5 CDN, Bootstrap Icons CDN, Chart.js CDN, app.css link, session_start(), login redirect guard
  - Write `app/views/layout/sidebar.php`: navigation links for all 8 modules with active state highlighting
  - Write `app/views/layout/footer.php`: closing HTML, common script tags
  - Write `assets/css/app.css`: sidebar styles, POS layout, status badge colors, receipt print styles
  - _Requires_: 1

- [-] 3. Authentication (Login / Logout)
  - Write `app/models/User.php`: findByEmail(), findById(), updateProfile(), updatePassword(), updatePhoto() using PDO prepared statements
  - Write `app/controllers/AuthController.php`: login() POST handler (validate credentials, start session, redirect), logout() (destroy session, redirect)
  - Write `app/views/auth/login.php`: Bootstrap 5 login card with email + password fields and error messages
  - Update `index.php` dispatcher to handle module=auth and protect all other modules with session check
  - _Requires_: 2

- [~] 4. Dashboard Module
  - Write `app/controllers/DashboardController.php`: index() renders dashboard view
  - Write `api/dashboard.php`: actions — metrics (today's sales/transactions/revenue/profit), alerts (low_stock_count, expiring_count), best_sellers (top 5 by qty today), recent_transactions (last 10), monthly_chart (12-month revenue), inventory_status (% breakdown by status)
  - Write `app/views/dashboard/index.php`: 4 KPI stat cards, 2 alert cards, Best Sellers table, Recent Transactions table, 12-month bar chart canvas
  - Write `assets/js/dashboard.js`: fetch all data via AJAX on load, render Chart.js bar chart, populate tables, auto-refresh every 60 seconds
  - _Requires_: 3

- [~] 5. Product Catalogue Module (CRUD + CSV Import)
  - Write `app/models/Category.php`: findAll(), findById(), create(), update(), delete() static methods
  - Write `app/models/Product.php`: findAll() with filters, findById(), findByBarcode(), search(), create(), update(), delete() soft-delete, updateStock(), getLowStock(), getExpiringSoon(), computeStatus(), importFromCSV()
  - Write `app/controllers/ProductController.php`: index(), add(), edit(), delete() actions with image upload (MIME + extension validation, save to uploads/products/) and CSRF check
  - Write `api/products.php`: handle actions list, search, get, create, update, delete, import — all return JSON
  - Write `app/views/products/index.php`: data table with search, category filter, status filter; Add Product and Import CSV buttons; Edit/Delete per row with confirm modal
  - Write `app/views/products/add.php` and `edit.php`: Bootstrap 5 form with all product fields and image preview
  - Write `assets/js/products.js`: AJAX delete, image preview, CSV import modal
  - _Requires_: 3

- [~] 6. POS (Point of Sale) Module
  - Write `app/services/SalesService.php`: processCheckout(), calculateCart(), deductStock(), buildReceiptData() — full business logic with DB transactions, stock validation, and rollback on error
  - Write `app/controllers/POSController.php`: index() renders POS view, checkout() POST delegates to SalesService, searchProducts() returns JSON
  - Write `api/pos.php`: actions checkout (delegates to SalesService::processCheckout) and void (admin, reverses stock movements)
  - Write `app/views/pos/index.php`: two-column layout — left product grid with search/category filter, right cart panel with qty controls, discount, total, payment selector, cash tendered, change display, Checkout and Clear buttons
  - Write `assets/js/pos.js`: full cartState management, barcode scanner support (keypress listener), AJAX product search with debounce, checkout POST with receipt modal, printReceipt(), keyboard shortcuts (F2=focus search, F12=checkout, Esc=clear)
  - _Requires_: 5

- [~] 7. Inventory Module
  - Write `app/models/StockMovement.php`: record(), findByProduct(), findAll() with filters, getAuditLogs() static methods
  - Write `app/controllers/InventoryController.php`: index() renders inventory view
  - Write `api/inventory.php`: actions movements, low_stock, expiring, valuation, stock_in, stock_out, adjustment — implement processStockMovement() with DB transaction and product status recompute
  - Write `app/views/inventory/index.php`: tabbed layout — Stock Movements log, Low Stock alerts with quick Stock-In, Expiring Soon table, Inventory Valuation summary; FAB for Stock In/Out/Adjustment modal
  - Write `assets/js/inventory.js`: AJAX tab load, stock movement modal with product search, submit with confirmation
  - _Requires_: 5

- [~] 8. Sales & Analytics Module
  - Write `app/services/AnalyticsService.php`: getDailySummary(), getWeeklySummary(), getMonthlySummary(), getYearlySummary(), getProductPerformance(), getMonthComparison(), getRevenueTrend() — all aggregate queries via PDO
  - Write `app/controllers/SalesController.php`: index() renders sales view
  - Write `api/sales.php`: actions daily, weekly, monthly, yearly, trend, product_performance, month_comparison, export_pdf (TCPDF), export_excel (PhpSpreadsheet)
  - Write `app/views/sales/index.php`: period selector tabs (Today/Week/Month/Year + custom date range); 4 summary stat cards; revenue vs profit bar chart; product performance table; month comparison section
  - Write `assets/js/sales.js`: initRevenueChart(), initTrendChart(), loadAnalytics() with AJAX, date range picker, export button handlers
  - _Requires_: 3

- [~] 9. Transaction History Module
  - Write `app/models/Transaction.php`: create(), findById(), findByReceiptNo(), findAll() with filters, getSummary(), generateReceiptNo(), getPaymentSplit() static methods
  - Write `app/models/TransactionItem.php`: findByTransaction(), bulkInsert() static methods
  - Write `app/controllers/TransactionController.php`: index() renders transactions view
  - Write `api/transactions.php`: actions list (paginated, filterable), get (with items), receipt (HTML thermal receipt template), summary, export_pdf, export_excel
  - Write `app/views/transactions/index.php`: filter bar (receipt# search, date range, payment method); transactions data table with all fields; Export PDF and Export Excel buttons
  - Write `app/views/transactions/receipt.php`: printable 80mm thermal receipt layout (store name, receipt#, itemized list, totals, payment info)
  - _Requires_: 6

- [~] 10. Supplier & Purchase Order Module
  - Write `app/models/Supplier.php`: findAll(), findById(), create(), update(), delete(), getProductCount(), getLastOrderDate() static methods
  - Write `app/models/PurchaseOrder.php` and `app/models/PurchaseOrderItem.php`: create(), findAll(), findById(), markReceived() (triggers stock_in per item), generatePONumber(), cancel() static methods
  - Write `app/controllers/SupplierController.php`: index(), profile() actions
  - Write `api/suppliers.php`: actions list, get, create, update, delete, po_create, po_receive (with stock-in trigger), po_list
  - Write `app/views/suppliers/index.php`: supplier table with company name, contact, product count, last order date; Add Supplier modal; Edit/Delete per row
  - Write `app/views/suppliers/profile.php`: supplier info, linked products tab, purchase orders tab with Create PO button and Receive/Cancel per PO, PO detail modal
  - _Requires_: 7

- [~] 11. Account Settings Module
  - Write `app/controllers/AccountController.php`: index(), updateProfile() POST, updatePassword() POST, exportDatabase() (SQL file download)
  - Write `app/views/account/index.php`: tabbed page — Profile (avatar upload, name, email, phone); Store Info (store name, address, tax ID); Change Password (current + new + confirm); Database Backup (Export SQL button)
  - Add photo upload handling in AccountController: validate image MIME/extension, save to uploads/ with user ID prefix, update users.photo
  - _Requires_: 3

- [~] 12. Security Helpers, Global Polish & Integration
  - Write `app/helpers/csrf.php`: generateToken() and validateToken() helpers; add tokens to all POST forms and AJAX headers
  - Write `app/helpers/flash.php`: set() and get() using $_SESSION; render dismissible Bootstrap alerts in header.php
  - Create `assets/img/placeholder.png`: a simple grey 200x200 placeholder image
  - Polish `assets/css/app.css`: responsive sidebar collapse, POS two-column responsive layout, @media print receipt styles, loading spinner overlay, toast notification styles
  - Write `assets/js/app.js`: global AJAX error handler (401 → redirect login, 500 → error toast), loading spinner on all fetch calls
  - Final integration: verify all navigation links, AJAX endpoints return proper JSON with success field, all forms have CSRF tokens, all uploads validate MIME type
  - _Requires_: 4, 5, 6, 7, 8, 9, 10, 11

## Task Dependency Graph

```json
{
  "waves": [
    { "wave": 1, "tasks": ["1"] },
    { "wave": 2, "tasks": ["2"] },
    { "wave": 3, "tasks": ["3"] },
    { "wave": 4, "tasks": ["4", "5", "8", "11"] },
    { "wave": 5, "tasks": ["6", "7"] },
    { "wave": 6, "tasks": ["9", "10"] },
    { "wave": 7, "tasks": ["12"] }
  ]
}
```

## Notes

- All PHP database queries must use PDO prepared statements — no raw string interpolation in SQL.
- All monetary values are in Philippine Peso (₱) using DECIMAL(10,2).
- Product status (active, low_stock, out_of_stock, expiring_soon) is recomputed and stored on every save.
- The checkout process uses a DB transaction with rollback on any error to ensure stock consistency.
- Price snapshots in transaction_items are immutable — they capture prices at the time of sale.
- Bootstrap 5 and Chart.js are loaded from CDN; TCPDF and PhpSpreadsheet are installed via Composer.
- The application is installed at `c:\xampp\htdocs\grocerypos\` and accessed at `http://localhost/grocerypos/`.
