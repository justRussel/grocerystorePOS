<?php
/**
 * GroceryPOS - SupplierController
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../models/PurchaseOrder.php';
require_once __DIR__ . '/../models/PurchaseOrderItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/StockMovement.php';
require_once __DIR__ . '/../helpers/flash.php';

class SupplierController
{
    public function index(): void
    {
        $module       = 'suppliers';
        $pageTitle    = 'Suppliers';
        $extraScripts = '<script src="' . BASE_URL . 'assets/js/suppliers.js"></script>';
        require_once __DIR__ . '/../views/suppliers/index.php';
    }

    public function profile(): void
    {
        $module    = 'suppliers';
        $pageTitle = 'Supplier Profile';

        $id       = (int) ($_GET['id'] ?? 0);
        $supplier = Supplier::findById($id);

        if (!$supplier) {
            setFlash('danger', 'Supplier not found.');
            header('Location: ' . BASE_URL . '?module=suppliers');
            exit;
        }

        $orders       = PurchaseOrder::findAll(['supplier_id' => $id]);
        $productCount = Supplier::getProductCount($id);
        $extraScripts = '<script src="' . BASE_URL . 'assets/js/suppliers.js"></script>';

        require_once __DIR__ . '/../views/suppliers/profile.php';
    }
}
