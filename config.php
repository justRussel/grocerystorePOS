<?php
/**
 * GroceryPOS - Application Configuration
 * Adjust DB credentials and paths as needed for your environment.
 */

// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'u907280979_pos');
define('DB_USER',     'u907280979_russel');
define('DB_PASS',     'jr_aquinO123');
define('DB_CHARSET',  'utf8mb4');

// ─── Application ─────────────────────────────────────────────────────────────
define('APP_NAME',    'GroceryPOS');
define('BASE_URL',    'https://pos-grocerystore.online/');

// ─── File Paths ───────────────────────────────────────────────────────────────
define('ROOT_PATH',    __DIR__ . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH',  ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR);
define('EXPORT_PATH',  ROOT_PATH . 'exports' . DIRECTORY_SEPARATOR);

// ─── Upload URL helpers ───────────────────────────────────────────────────────
define('UPLOAD_URL',   BASE_URL . 'uploads/products/');
define('EXPORT_URL',   BASE_URL . 'exports/');

// ─── Session ──────────────────────────────────────────────────────────────────
define('SESSION_NAME', 'grocerypos_session');

// ─── Currency ────────────────────────────────────────────────────────────────
define('CURRENCY_SYMBOL', '₱');

// ─── Inventory ───────────────────────────────────────────────────────────────
define('LOW_STOCK_DEFAULT',     10);   // default low-stock threshold
define('EXPIRY_WARNING_DAYS',   30);   // days ahead to flag expiring products

// ─── Error Reporting (disable on production) ─────────────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─── Timezone ────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');
