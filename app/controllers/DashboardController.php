<?php
/**
 * GroceryPOS - DashboardController
 */

require_once __DIR__ . '/../helpers/flash.php';
require_once __DIR__ . '/../helpers/csrf.php';

class DashboardController
{
    public function index(): void
    {
        $module    = 'dashboard';
        $pageTitle = 'Dashboard';
        require_once __DIR__ . '/../views/dashboard/index.php';
    }
}
