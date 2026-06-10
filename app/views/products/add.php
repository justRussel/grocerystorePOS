<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= BASE_URL ?>?module=products" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-success"></i>Add Product</h4>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>?module=products&action=add" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Barcode</label>
                    <input type="text" name="barcode" class="form-control"
                           value="<?= htmlspecialchars($_POST['barcode'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"
                                <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cost Price (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                    <input type="number" name="cost_price" class="form-control" step="0.01" min="0" required
                           value="<?= htmlspecialchars($_POST['cost_price'] ?? '0.00') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Selling Price (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                    <input type="number" name="selling_price" class="form-control" step="0.01" min="0.01" required
                           value="<?= htmlspecialchars($_POST['selling_price'] ?? '0.00') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stock Qty</label>
                    <input type="number" name="stock_qty" class="form-control" min="0"
                           value="<?= (int) ($_POST['stock_qty'] ?? 0) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" class="form-control" min="0"
                           value="<?= (int) ($_POST['low_stock_threshold'] ?? LOW_STOCK_DEFAULT) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['expiry_date'] ?? '') ?>">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*" id="imageInput">
                    <div class="mt-2">
                        <img id="imagePreview" src="<?= BASE_URL ?>assets/img/placeholder.png"
                             alt="Preview" style="max-height:120px;object-fit:contain;display:none;">
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= BASE_URL ?>?module=products" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Save Product</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('imageInput').addEventListener('change', function () {
    const preview = document.getElementById('imagePreview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
