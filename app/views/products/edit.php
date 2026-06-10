<?php
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/csrf.php';
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= BASE_URL ?>?module=products" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Product</h4>
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

<?php
// Use posted data if available, otherwise fall back to product data
$val = function(string $key) use ($product): string {
    return htmlspecialchars($_POST[$key] ?? $product[$key] ?? '');
};
?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>?module=products&action=edit&id=<?= (int) $product['id'] ?>"
              enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Barcode</label>
                    <input type="text" name="barcode" class="form-control" value="<?= $val('barcode') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= $val('name') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"
                                <?= (($_POST['category_id'] ?? $product['category_id']) == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cost Price (<?= CURRENCY_SYMBOL ?>)</label>
                    <input type="number" name="cost_price" class="form-control" step="0.01" min="0"
                           value="<?= $val('cost_price') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Selling Price (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                    <input type="number" name="selling_price" class="form-control" step="0.01" min="0.01" required
                           value="<?= $val('selling_price') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stock Qty</label>
                    <input type="number" name="stock_qty" class="form-control" min="0"
                           value="<?= $val('stock_qty') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" class="form-control" min="0"
                           value="<?= $val('low_stock_threshold') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control"
                           value="<?= $val('expiry_date') ?>">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*" id="imageInput">
                    <?php $imgSrc = !empty($product['image'])
                        ? UPLOAD_URL . htmlspecialchars($product['image'])
                        : BASE_URL . 'assets/img/placeholder.png'; ?>
                    <div class="mt-2">
                        <img id="imagePreview" src="<?= $imgSrc ?>"
                             alt="Preview" style="max-height:120px;object-fit:contain;">
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= BASE_URL ?>?module=products" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Update Product</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('imageInput').addEventListener('change', function () {
    const preview = document.getElementById('imagePreview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
