<?php
/**
 * GroceryPOS - POSController
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/TransactionItem.php';
require_once __DIR__ . '/../models/StockMovement.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/SalesService.php';

class POSController
{
    public function index(): void
    {
        $module     = 'pos';
        $pageTitle  = 'Point of Sale';
        $categories = Category::findAll();
        $extraScripts = '<script src="' . BASE_URL . 'assets/js/pos.js"></script>';
        require_once __DIR__ . '/../views/pos/index.php';
    }

    public function checkout(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required.']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) {
            // Try form data
            $body = $_POST;
        }

        $cartItems   = $body['cart']          ?? [];
        $paymentInfo = [
            'payment_method' => $body['payment_method'] ?? 'cash',
            'cash_tendered'  => (float) ($body['cash_tendered'] ?? 0),
            'discount_value' => (float) ($body['discount_value'] ?? 0),
            'discount_type'  => $body['discount_type'] ?? 'fixed',
        ];

        $result = SalesService::processCheckout($cartItems, $paymentInfo);

        if ($result['success']) {
            $result['receipt'] = SalesService::buildReceiptData($result['transaction_id']);
        }

        echo json_encode($result);
        exit;
    }
}
