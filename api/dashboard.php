<?php
/**
 * GroceryPOS - Dashboard API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/Database.php';

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

$action = $_GET['action'] ?? '';
$pdo    = Database::getInstance();

try {
    switch ($action) {

        case 'metrics':
            // Today's revenue, profit, transactions
            $stmt = $pdo->prepare(
                'SELECT
                    COALESCE(SUM(t.total_amount), 0) AS revenue,
                    COUNT(DISTINCT t.id)              AS transactions,
                    COALESCE(SUM((ti.selling_price_snapshot - ti.cost_price_snapshot) * ti.quantity), 0) AS profit
                   FROM transactions t
              LEFT JOIN transaction_items ti ON ti.transaction_id = t.id
                  WHERE DATE(t.created_at) = CURDATE()'
            );
            $stmt->execute();
            $metrics = $stmt->fetch();

            // Items sold today
            $stmtI = $pdo->query(
                'SELECT COALESCE(SUM(ti.quantity), 0) AS items_sold
                   FROM transaction_items ti
                   JOIN transactions t ON t.id = ti.transaction_id
                  WHERE DATE(t.created_at) = CURDATE()'
            );
            $metrics['items_sold'] = (int) $stmtI->fetchColumn();

            echo json_encode(['success' => true, 'data' => $metrics]);
            break;

        case 'alerts':
            $stmtLow = $pdo->query(
                'SELECT COUNT(*) FROM products
                  WHERE is_active = 1
                    AND stock_qty <= low_stock_threshold
                    AND stock_qty > 0'
            );
            $lowStock = (int) $stmtLow->fetchColumn();

            $stmtExp = $pdo->prepare(
                'SELECT COUNT(*) FROM products
                  WHERE is_active = 1
                    AND expiry_date IS NOT NULL
                    AND expiry_date >= CURDATE()
                    AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)'
            );
            $stmtExp->execute([':days' => EXPIRY_WARNING_DAYS]);
            $expiring = (int) $stmtExp->fetchColumn();

            echo json_encode(['success' => true, 'data' => [
                'low_stock' => $lowStock,
                'expiring'  => $expiring,
            ]]);
            break;

        case 'best_sellers':
            $stmt = $pdo->prepare(
                'SELECT
                    ti.product_id,
                    ti.product_name_snapshot AS name,
                    SUM(ti.quantity)         AS qty_sold,
                    SUM(ti.line_total)       AS revenue
                   FROM transaction_items ti
                   JOIN transactions t ON t.id = ti.transaction_id
                  WHERE DATE(t.created_at) = CURDATE()
               GROUP BY ti.product_id, ti.product_name_snapshot
               ORDER BY qty_sold DESC
                  LIMIT 5'
            );
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'recent_transactions':
            $stmt = $pdo->query(
                'SELECT t.id, t.receipt_no, t.total_amount, t.payment_method, t.created_at,
                        u.full_name AS cashier_name
                   FROM transactions t
              LEFT JOIN users u ON u.id = t.cashier_id
                  ORDER BY t.created_at DESC
                  LIMIT 10'
            );
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'monthly_chart':
            $year = (int) ($_GET['year'] ?? date('Y'));
            $stmt = $pdo->prepare(
                'SELECT
                    MONTH(t.created_at) AS month,
                    COALESCE(SUM(t.total_amount), 0) AS revenue,
                    COALESCE(SUM((ti.selling_price_snapshot - ti.cost_price_snapshot) * ti.quantity), 0) AS profit
                   FROM transactions t
              LEFT JOIN transaction_items ti ON ti.transaction_id = t.id
                  WHERE YEAR(t.created_at) = :year
               GROUP BY MONTH(t.created_at)
               ORDER BY MONTH(t.created_at)'
            );
            $stmt->execute([':year' => $year]);
            $rows = $stmt->fetchAll();

            // Fill all 12 months
            $indexed = [];
            foreach ($rows as $r) {
                $indexed[(int) $r['month']] = $r;
            }
            $chart = [];
            for ($m = 1; $m <= 12; $m++) {
                $chart[] = $indexed[$m] ?? ['month' => $m, 'revenue' => 0, 'profit' => 0];
            }

            echo json_encode(['success' => true, 'data' => $chart]);
            break;

        case 'inventory_status':
            $stmt = $pdo->query(
                'SELECT status, COUNT(*) AS cnt FROM products WHERE is_active = 1 GROUP BY status'
            );
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    }
} catch (Exception $e) {
    error_log('dashboard api: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
