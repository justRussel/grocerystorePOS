<?php
/**
 * GroceryPOS - Transactions API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Transaction.php';
require_once __DIR__ . '/../app/models/TransactionItem.php';
require_once __DIR__ . '/../app/models/User.php';

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

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $filters = [];
            if (!empty($_GET['receipt_no']))     $filters['receipt_no']     = $_GET['receipt_no'];
            if (!empty($_GET['date_from']))       $filters['date_from']      = $_GET['date_from'];
            if (!empty($_GET['date_to']))         $filters['date_to']        = $_GET['date_to'];
            if (!empty($_GET['payment_method'])) $filters['payment_method'] = $_GET['payment_method'];

            $filters['limit']  = (int) ($_GET['limit']  ?? 50);
            $filters['offset'] = (int) ($_GET['offset'] ?? 0);

            $transactions = Transaction::findAll($filters);
            $total        = Transaction::countAll($filters);

            echo json_encode(['success' => true, 'data' => $transactions, 'total' => $total]);
            break;

        case 'get':
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'id required.']);
                exit;
            }
            $transaction = Transaction::findById($id);
            echo json_encode(['success' => true, 'data' => $transaction]);
            break;

        case 'receipt':
            $id          = (int) ($_GET['id'] ?? 0);
            $transaction = Transaction::findById($id);
            if (!$transaction) {
                echo json_encode(['success' => false, 'error' => 'Not found.']);
                exit;
            }
            // Return HTML for receipt modal
            header('Content-Type: text/html');
            $storeName    = $_SESSION['store_name']    ?? APP_NAME;
            $storeAddress = $_SESSION['store_address'] ?? '';
            echo buildReceiptHtml($transaction, $storeName, $storeAddress);
            break;

        case 'summary':
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d');
            $dateTo   = $_GET['date_to']   ?? date('Y-m-d');
            echo json_encode(['success' => true, 'data' => Transaction::getSummary($dateFrom, $dateTo)]);
            break;

        case 'export':
            $filters = [];
            if (!empty($_GET['date_from']))       $filters['date_from']      = $_GET['date_from'];
            if (!empty($_GET['date_to']))         $filters['date_to']        = $_GET['date_to'];
            if (!empty($_GET['payment_method'])) $filters['payment_method'] = $_GET['payment_method'];
            $filters['limit'] = 5000;

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="transactions_export_' . date('Ymd') . '.csv"');

            $transactions = Transaction::findAll($filters);
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Receipt No', 'Total Amount', 'Discount', 'Subtotal', 'Payment Method', 'Cashier', 'Date']);
            foreach ($transactions as $t) {
                fputcsv($out, [
                    $t['receipt_no'],
                    $t['total_amount'],
                    $t['discount_amount'],
                    $t['subtotal'],
                    $t['payment_method'],
                    $t['cashier_name'] ?? '',
                    $t['created_at'],
                ]);
            }
            fclose($out);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    }
} catch (Exception $e) {
    error_log('transactions api: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}

/**
 * Build receipt HTML for modal display.
 */
function buildReceiptHtml(array $t, string $storeName, string $storeAddress): string
{
    $items   = $t['items'] ?? [];
    $html    = '<div class="receipt-container">';
    $html   .= '<div class="receipt-header">';
    $html   .= '<h4>' . htmlspecialchars($storeName) . '</h4>';
    if ($storeAddress) {
        $html .= '<p>' . htmlspecialchars($storeAddress) . '</p>';
    }
    $html .= '<p>Receipt #: <strong>' . htmlspecialchars($t['receipt_no']) . '</strong></p>';
    $html .= '<p>' . htmlspecialchars($t['created_at']) . '</p>';
    $html .= '<p>Cashier: ' . htmlspecialchars($t['cashier_name'] ?? 'N/A') . '</p>';
    $html .= '</div>';

    $html .= '<table class="w-100"><thead><tr>';
    $html .= '<th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($items as $item) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['product_name_snapshot']) . '</td>';
        $html .= '<td class="text-end">' . (int) $item['quantity'] . '</td>';
        $html .= '<td class="text-end">' . CURRENCY_SYMBOL . number_format($item['selling_price_snapshot'], 2) . '</td>';
        $html .= '<td class="text-end">' . CURRENCY_SYMBOL . number_format($item['line_total'], 2) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '<div class="receipt-totals">';
    $html .= '<table class="w-100">';
    $html .= '<tr><td>Subtotal</td><td class="text-end">' . CURRENCY_SYMBOL . number_format($t['subtotal'], 2) . '</td></tr>';
    if ($t['discount_amount'] > 0) {
        $html .= '<tr><td>Discount</td><td class="text-end">- ' . CURRENCY_SYMBOL . number_format($t['discount_amount'], 2) . '</td></tr>';
    }
    $html .= '<tr class="grand-total"><td><strong>TOTAL</strong></td><td class="text-end"><strong>' . CURRENCY_SYMBOL . number_format($t['total_amount'], 2) . '</strong></td></tr>';
    if (!empty($t['cash_tendered'])) {
        $html .= '<tr><td>Cash Tendered</td><td class="text-end">' . CURRENCY_SYMBOL . number_format($t['cash_tendered'], 2) . '</td></tr>';
        $html .= '<tr><td>Change</td><td class="text-end">' . CURRENCY_SYMBOL . number_format($t['change_amount'], 2) . '</td></tr>';
    }
    $html .= '</table></div>';
    $html .= '<div class="receipt-footer"><p>Thank you for shopping!</p></div>';
    $html .= '</div>';
    return $html;
}
