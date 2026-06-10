<?php
/**
 * GroceryPOS - Front Controller / Router
 * Dispatches requests to the appropriate controller based on ?module= and ?action= query params.
 */

require_once __DIR__ . '/config.php';

// ─── Session bootstrap ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ─── Sanitize routing inputs ──────────────────────────────────────────────────
$module = isset($_GET['module']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['module'])) : 'dashboard';
$action = isset($_GET['action']) ? preg_replace('/[^a-zA-Z_]/', '', $_GET['action'])          : 'index';

// ─── Module → Controller mapping ─────────────────────────────────────────────
$controllerMap = [
    'dashboard'    => 'DashboardController',
    'pos'          => 'POSController',
    'products'     => 'ProductController',
    'inventory'    => 'InventoryController',
    'sales'        => 'SalesController',
    'suppliers'    => 'SupplierController',
    'transactions' => 'TransactionController',
    'account'      => 'AccountController',
    'auth'         => 'AuthController',
];

// ─── Default to dashboard for unknown modules ─────────────────────────────────
if (!array_key_exists($module, $controllerMap)) {
    $module = 'dashboard';
    $action = 'index';
}

// ─── Authentication guard ─────────────────────────────────────────────────────
// The auth module (login/logout) is always accessible.
// All other modules require an active session.
if ($module !== 'auth' && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?module=auth&action=login');
    exit;
}

$controllerName = $controllerMap[$module];
$controllerFile = __DIR__ . '/app/controllers/' . $controllerName . '.php';

// ─── Load and dispatch controller ────────────────────────────────────────────
if (file_exists($controllerFile)) {
    require_once $controllerFile;

    if (class_exists($controllerName)) {
        $controller = new $controllerName();

        // Ensure the action method exists and is public; fall back to index()
        if (method_exists($controller, $action) && is_callable([$controller, $action])) {
            $controller->$action();
        } elseif (method_exists($controller, 'index')) {
            $controller->index();
        } else {
            http_response_code(404);
            echo '<h1>404 – Action Not Found</h1>';
        }
    } else {
        http_response_code(500);
        echo '<h1>500 – Controller class not found: ' . htmlspecialchars($controllerName) . '</h1>';
    }
} else {
    // Controller file missing — show a placeholder during development
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Not Found</title></head><body>';
    echo '<h1>404 – Module Not Implemented</h1>';
    echo '<p>Controller file not found: <code>' . htmlspecialchars($controllerFile) . '</code></p>';
    echo '</body></html>';
}
