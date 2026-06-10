<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-success"></i>Products</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload me-1"></i>Import CSV
        </button>
        <a href="<?= BASE_URL ?>?module=products&action=add" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Product
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Search name or barcode…">
            </div>
            <div class="col-md-3">
                <select id="filterCategory" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                    <option value="expiring_soon">Expiring Soon</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-success btn-sm w-100" id="btnFilterProducts">
                    <i class="bi bi-search me-1"></i>Search
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0" id="productsTable">
                <thead class="table-light">
                    <tr>
                        <th width="60">Image</th>
                        <th>Name</th>
                        <th>Barcode</th>
                        <th>Category</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Stock</th>
                        <th>Status</th>
                        <th>Expiry</th>
                        <th width="110">Actions</th>
                    </tr>
                </thead>
                <tbody id="productsBody">
                    <tr><td colspan="10" class="text-center py-4 text-muted">Loading products…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete confirmation form (hidden) -->
<form id="deleteForm" method="POST" action="<?= BASE_URL ?>?module=products&action=delete" style="display:none;">
    <input type="hidden" name="id" id="deleteProductId">
</form>

<!-- CSV Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Products via CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <p class="small text-muted mb-2">CSV columns: <code>barcode, name, category_id, cost_price, selling_price, stock_qty, low_stock_threshold, expiry_date</code></p>
                    <input type="file" name="csv" class="form-control" accept=".csv" required>
                    <div id="importResult" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/products.js"></script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
