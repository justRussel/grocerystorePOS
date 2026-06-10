<?php
/**
 * GroceryPOS - Products API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Category.php';

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
$action = $_GET['action'] ?? ($method === 'GET' ? 'list' : '');

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'list':
                $filters = [];
                if (!empty($_GET['category_id'])) $filters['category_id'] = (int) $_GET['category_id'];
                if (!empty($_GET['status']))       $filters['status']      = $_GET['status'];
                if (!empty($_GET['search']))        $filters['search']      = $_GET['search'];

                $products = Product::findAll($filters);
                echo json_encode(['success' => true, 'data' => $products]);
                break;

            case 'search':
                $keyword    = $_GET['keyword'] ?? $_GET['q'] ?? '';
                $categoryId = !empty($_GET['category_id']) ? (int) $_GET['category_id'] : null;
                $products   = Product::search($keyword, $categoryId);
                echo json_encode(['success' => true, 'data' => $products]);
                break;

            case 'get':
                if (!empty($_GET['barcode'])) {
                    $product = Product::findByBarcode($_GET['barcode']);
                } elseif (!empty($_GET['id'])) {
                    $product = Product::findById((int) $_GET['id']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'id or barcode required.']);
                    exit;
                }
                echo json_encode(['success' => true, 'data' => $product]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        }
    } elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) $body = $_POST;
        $postAction = $body['action'] ?? $action;

        switch ($postAction) {
            case 'create':
                $id = Product::create($body);
                echo json_encode(['success' => true, 'id' => $id]);
                break;

            case 'update':
                $id = (int) ($body['id'] ?? 0);
                Product::update($id, $body);
                echo json_encode(['success' => true]);
                break;

            case 'delete':
                $id = (int) ($body['id'] ?? 0);
                Product::delete($id);
                echo json_encode(['success' => true]);
                break;

            case 'import':
                if (empty($_FILES['csv']['tmp_name'])) {
                    echo json_encode(['success' => false, 'error' => 'No CSV file uploaded.']);
                    exit;
                }
                $result = Product::importFromCSV($_FILES['csv']['tmp_name']);
                echo json_encode([
                    'success' => true,
                    'count'   => $result['imported'],
                    'errors'  => $result['errors'],
                ]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Unknown POST action.']);
        }
    }
} catch (Exception $e) {
    error_log('products api: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
