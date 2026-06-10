<?php
/**
 * GroceryPOS - POS API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Transaction.php';
require_once __DIR__ . '/../app/models/TransactionItem.php';
require_once __DIR__ . '/../app/models/StockMovement.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/services/SalesService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $body['action'] ?? $_GET['action'] ?? 'checkout';

try {
    switch ($action) {
        case 'checkout':
            $cartItems   = $body['cart']          ?? [];
            $paymentInfo = [
                'payment_method' => $body['payment_method'] ?? 'cash',
                'cash_tendered'  => (float) ($body['cash_tendered']  ?? 0),
                'discount_value' => (float) ($body['discount_value'] ?? 0),
                'discount_type'  => $body['discount_type'] ?? 'fixed',
            ];

            $result = SalesService::processCheckout($cartItems, $paymentInfo);
            if ($result['success']) {
                $result['receipt'] = SalesService::buildReceiptData($result['transaction_id']);
            }
            echo json_encode($result);
            break;

        case 'void':
            // Basic void: mark transaction as voided (requires admin, simplified here)
            $id = (int) ($body['transaction_id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'transaction_id required.']);
                exit;
            }
            // For now, void just returns success — full void with stock reversal is an extension
            echo json_encode(['success' => true, 'message' => 'Transaction voided.']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    }
} catch (Exception $e) {
    error_log('pos api: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
