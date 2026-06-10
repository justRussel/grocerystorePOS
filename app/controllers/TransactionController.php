<?php
/**
 * GroceryPOS - TransactionController
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/TransactionItem.php';
require_once __DIR__ . '/../models/User.php';

class TransactionController
{
    public function index(): void
    {
        $module       = 'transactions';
        $pageTitle    = 'Transaction History';
        $extraScripts = '<script src="' . BASE_URL . 'assets/js/transactions.js"></script>';
        require_once __DIR__ . '/../views/transactions/index.php';
    }
}
