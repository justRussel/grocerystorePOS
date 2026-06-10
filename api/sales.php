<?php
/**
 * GroceryPOS - Sales & Analytics API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Transaction.php';
require_once __DIR__ . '/../app/models/TransactionItem.php';
require_once __DIR__ . '/../app/services/AnalyticsService.php';

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

try {
    switch ($action) {
        case 'daily':
            $date = $_GET['date'] ?? date('Y-m-d');
            echo json_encode(['success' => true, 'data' => AnalyticsService::getDailySummary($date)]);
            break;

        case 'weekly':
            $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
            echo json_encode(['success' => true, 'data' => AnalyticsService::getWeeklySummary($weekStart)]);
            break;

        case 'monthly':
            $year  = (int) ($_GET['year']  ?? date('Y'));
            $month = (int) ($_GET['month'] ?? date('n'));
            echo json_encode(['success' => true, 'data' => AnalyticsService::getMonthlySummary($year, $month)]);
            break;

        case 'yearly':
            $year = (int) ($_GET['year'] ?? date('Y'));
            echo json_encode(['success' => true, 'data' => AnalyticsService::getYearlySummary($year)]);
            break;

        case 'trend':
            $year = (int) ($_GET['year'] ?? date('Y'));
            echo json_encode(['success' => true, 'data' => AnalyticsService::getRevenueTrend($year)]);
            break;

        case 'product_performance':
            $dateFrom   = $_GET['date_from']   ?? date('Y-m-01');
            $dateTo     = $_GET['date_to']      ?? date('Y-m-d');
            $categoryId = !empty($_GET['category_id']) ? (int) $_GET['category_id'] : null;
            echo json_encode(['success' => true, 'data' => AnalyticsService::getProductPerformance($dateFrom, $dateTo, $categoryId)]);
            break;

        case 'month_comparison':
            $year  = (int) ($_GET['year']  ?? date('Y'));
            $month = (int) ($_GET['month'] ?? date('n'));
            echo json_encode(['success' => true, 'data' => AnalyticsService::getMonthComparison($year, $month)]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    }
} catch (Exception $e) {
    error_log('sales api: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
