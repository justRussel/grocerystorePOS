<?php
/**
 * GroceryPOS - Suppliers API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Supplier.php';
require_once __DIR__ . '/../app/models/PurchaseOrder.php';
require_once __DIR__ . '/../app/models/PurchaseOrderItem.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/StockMovement.php';

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

$method = $_SERVER['REQUEST_METHOD'];
$body   = [];
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
}
$action = $_GET['action'] ?? ($body['action'] ?? '');

try {
    switch ($action) {
        case 'list':
            $suppliers = Supplier::findAll();
            foreach ($suppliers as &$s) {
                $s['product_count']    = Supplier::getProductCount($s['id']);
                $s['last_order_date']  = Supplier::getLastOrderDate($s['id']);
            }
            echo json_encode(['success' => true, 'data' => $suppliers]);
            break;

        case 'get':
            $id       = (int) ($_GET['id'] ?? $body['id'] ?? 0);
            $supplier = Supplier::findById($id);
            if ($supplier) {
                $supplier['product_count']   = Supplier::getProductCount($id);
                $supplier['last_order_date'] = Supplier::getLastOrderDate($id);
            }
            echo json_encode(['success' => true, 'data' => $supplier]);
            break;

        case 'create':
            $id = Supplier::create($body);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'update':
            $id = (int) ($body['id'] ?? 0);
            Supplier::update($id, $body);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = (int) ($body['id'] ?? 0);
            Supplier::delete($id);
            echo json_encode(['success' => true]);
            break;

        case 'po_create':
            $items     = $body['items'] ?? [];
            $total     = 0;
            foreach ($items as $item) {
                $total += (float) ($item['line_total'] ?? ($item['quantity'] * $item['unit_cost']));
            }
            $id = PurchaseOrder::create([
                'supplier_id'  => (int) ($body['supplier_id'] ?? 0),
                'total_amount' => $total,
                'created_by'   => $_SESSION['user_id'],
            ], $items);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'po_receive':
            $id     = (int) ($body['id'] ?? 0);
            $result = PurchaseOrder::markReceived($id);
            echo json_encode(['success' => $result]);
            break;

        case 'po_cancel':
            $id     = (int) ($body['id'] ?? 0);
            $result = PurchaseOrder::cancel($id);
            echo json_encode(['success' => $result]);
            break;

        case 'po_list':
            $filters = [];
            if (!empty($_GET['supplier_id'])) $filters['supplier_id'] = (int) $_GET['supplier_id'];
            if (!empty($_GET['status']))      $filters['status']      = $_GET['status'];
            $orders = PurchaseOrder::findAll($filters);
            echo json_encode(['success' => true, 'data' => $orders]);
            break;

        default:
            // Default to list
            $suppliers = Supplier::findAll();
            echo json_encode(['success' => true, 'data' => $suppliers]);
    }
} catch (Exception $e) {
    error_log('suppliers api: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
