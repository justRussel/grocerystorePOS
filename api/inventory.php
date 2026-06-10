<?php
/**
 * GroceryPOS - Inventory API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/Database.php';
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
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'movements':
                $filters = [];
                if (!empty($_GET['product_id']))    $filters['product_id']    = (int) $_GET['product_id'];
                if (!empty($_GET['movement_type'])) $filters['movement_type'] = $_GET['movement_type'];
                if (!empty($_GET['date_from']))      $filters['date_from']     = $_GET['date_from'];
                if (!empty($_GET['date_to']))        $filters['date_to']       = $_GET['date_to'];
                $data = StockMovement::findAll($filters);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'low_stock':
                $data = Product::getLowStock();
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'expiring':
                $days = (int) ($_GET['days'] ?? EXPIRY_WARNING_DAYS);
                $data = Product::getExpiringSoon($days);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'valuation':
                $pdo  = Database::getInstance();
                $stmt = $pdo->query(
                    'SELECT
                        COALESCE(SUM(cost_price * stock_qty), 0)    AS total_cost_value,
                        COALESCE(SUM(selling_price * stock_qty), 0) AS total_selling_value,
                        COUNT(*)                                     AS product_count
                       FROM products
                      WHERE is_active = 1'
                );
                echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Unknown GET action.']);
        }
    } elseif ($method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $body['action'] ?? $action;

        $productId = (int) ($body['product_id'] ?? 0);
        $qty       = (int) ($body['quantity']   ?? 0);
        $reason    = trim($body['reason']       ?? '');
        $reference = trim($body['reference']    ?? '');
        $userId    = $_SESSION['user_id']        ?? null;

        if (!$productId || $qty <= 0) {
            echo json_encode(['success' => false, 'error' => 'product_id and quantity are required.']);
            exit;
        }

        $result = processStockMovement($productId, $action, $qty, $reason, $reference, $userId);
        echo json_encode($result);
    }
} catch (Exception $e) {
    error_log('inventory api: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}

/**
 * Process a stock movement inside a DB transaction.
 */
function processStockMovement(int $productId, string $movementType, int $qty, string $reason, string $reference, ?int $userId): array
{
    $product = Product::findById($productId);
    if (!$product) {
        return ['success' => false, 'error' => 'Product not found.'];
    }

    $pdo       = Database::getInstance();
    $qtyBefore = (int) $product['stock_qty'];

    switch ($movementType) {
        case 'stock_in':
            $qtyAfter = $qtyBefore + $qty;
            break;
        case 'stock_out':
            if ($qtyBefore < $qty) {
                return ['success' => false, 'error' => 'Cannot go below zero stock.'];
            }
            $qtyAfter = $qtyBefore - $qty;
            break;
        case 'adjustment':
            // qty here is the target absolute value
            if ($qty < 0) {
                return ['success' => false, 'error' => 'Adjustment cannot result in negative stock.'];
            }
            $qtyAfter = $qty;
            $qty      = $qty - $qtyBefore; // delta for movement record
            break;
        default:
            return ['success' => false, 'error' => 'Invalid movement type.'];
    }

    try {
        $pdo->beginTransaction();

        Product::updateStock($productId, $qtyAfter);
        StockMovement::record([
            'product_id'    => $productId,
            'movement_type' => $movementType === 'adjustment' ? 'adjustment' : $movementType,
            'qty_change'    => $qty,
            'qty_before'    => $qtyBefore,
            'qty_after'     => $qtyAfter,
            'reason'        => $reason,
            'reference'     => $reference,
            'created_by'    => $userId,
        ]);

        $pdo->commit();
        return ['success' => true, 'new_qty' => $qtyAfter];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Database error.'];
    }
}
