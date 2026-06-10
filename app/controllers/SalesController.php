<?php
/**
 * GroceryPOS - SalesController
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/TransactionItem.php';
require_once __DIR__ . '/../services/AnalyticsService.php';

class SalesController
{
    public function index(): void
    {
        $module       = 'sales';
        $pageTitle    = 'Sales & Analytics';
        $extraScripts = '<script src="' . BASE_URL . 'assets/js/sales.js"></script>';
        require_once __DIR__ . '/../views/sales/index.php';
    }
}
