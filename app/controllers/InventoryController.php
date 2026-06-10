<?php
/**
 * GroceryPOS - InventoryController
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/StockMovement.php';

class InventoryController
{
    public function index(): void
    {
        $module       = 'inventory';
        $pageTitle    = 'Inventory';
        $categories   = Category::findAll();
        $extraScripts = '<script src="' . BASE_URL . 'assets/js/inventory.js"></script>';
        require_once __DIR__ . '/../views/inventory/index.php';
    }
}
