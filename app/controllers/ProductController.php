<?php
/**
 * GroceryPOS - ProductController
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../helpers/flash.php';
require_once __DIR__ . '/../helpers/csrf.php';

class ProductController
{
    public function index(): void
    {
        $module     = 'products';
        $pageTitle  = 'Products';
        $categories = Category::findAll();
        require_once __DIR__ . '/../views/products/index.php';
    }

    public function add(): void
    {
        $module     = 'products';
        $pageTitle  = 'Add Product';
        $categories = Category::findAll();
        $errors     = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'barcode'             => trim($_POST['barcode']             ?? ''),
                'name'                => trim($_POST['name']                ?? ''),
                'category_id'         => (int) ($_POST['category_id']      ?? 0),
                'cost_price'          => (float) ($_POST['cost_price']      ?? 0),
                'selling_price'       => (float) ($_POST['selling_price']   ?? 0),
                'stock_qty'           => (int) ($_POST['stock_qty']         ?? 0),
                'low_stock_threshold' => (int) ($_POST['low_stock_threshold'] ?? LOW_STOCK_DEFAULT),
                'expiry_date'         => trim($_POST['expiry_date']         ?? ''),
                'image'               => null,
            ];

            if (empty($data['name']))        $errors[] = 'Product name is required.';
            if ($data['category_id'] <= 0)   $errors[] = 'Please select a category.';
            if ($data['selling_price'] <= 0) $errors[] = 'Selling price must be greater than 0.';

            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $result = $this->handleImageUpload($_FILES['image']);
                if ($result['success']) {
                    $data['image'] = $result['filename'];
                } else {
                    $errors[] = $result['error'];
                }
            }

            if (empty($errors)) {
                try {
                    Product::create($data);
                    setFlash('success', 'Product "' . htmlspecialchars($data['name']) . '" added successfully.');
                    header('Location: ' . BASE_URL . '?module=products');
                    exit;
                } catch (Exception $e) {
                    $errors[] = 'Failed to save product: ' . $e->getMessage();
                }
            }
        }

        require_once __DIR__ . '/../views/products/add.php';
    }

    public function edit(): void
    {
        $module     = 'products';
        $pageTitle  = 'Edit Product';
        $categories = Category::findAll();
        $errors     = [];

        $id      = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $product = Product::findById($id);

        if (!$product) {
            setFlash('danger', 'Product not found.');
            header('Location: ' . BASE_URL . '?module=products');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'barcode'             => trim($_POST['barcode']             ?? ''),
                'name'                => trim($_POST['name']                ?? ''),
                'category_id'         => (int) ($_POST['category_id']      ?? 0),
                'cost_price'          => (float) ($_POST['cost_price']      ?? 0),
                'selling_price'       => (float) ($_POST['selling_price']   ?? 0),
                'stock_qty'           => (int) ($_POST['stock_qty']         ?? 0),
                'low_stock_threshold' => (int) ($_POST['low_stock_threshold'] ?? LOW_STOCK_DEFAULT),
                'expiry_date'         => trim($_POST['expiry_date']         ?? ''),
                'image'               => $product['image'],
            ];

            if (empty($data['name']))        $errors[] = 'Product name is required.';
            if ($data['category_id'] <= 0)   $errors[] = 'Please select a category.';
            if ($data['selling_price'] <= 0) $errors[] = 'Selling price must be greater than 0.';

            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $result = $this->handleImageUpload($_FILES['image']);
                if ($result['success']) {
                    $data['image'] = $result['filename'];
                } else {
                    $errors[] = $result['error'];
                }
            }

            if (empty($errors)) {
                try {
                    Product::update($id, $data);
                    setFlash('success', 'Product updated successfully.');
                    header('Location: ' . BASE_URL . '?module=products');
                    exit;
                } catch (Exception $e) {
                    $errors[] = 'Failed to update product: ' . $e->getMessage();
                }
            }
        }

        require_once __DIR__ . '/../views/products/edit.php';
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?module=products');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id && Product::delete($id)) {
            setFlash('success', 'Product deleted successfully.');
        } else {
            setFlash('danger', 'Failed to delete product.');
        }

        header('Location: ' . BASE_URL . '?module=products');
        exit;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function handleImageUpload(array $file): array
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Image upload error.'];
        }

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedMimes, true)) {
            return ['success' => false, 'error' => 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            return ['success' => false, 'error' => 'Invalid file extension.'];
        }

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0775, true);
        }

        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest     = UPLOAD_PATH . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'error' => 'Failed to save image.'];
        }

        return ['success' => true, 'filename' => $filename];
    }
}
