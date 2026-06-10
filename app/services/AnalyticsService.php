<?php
/**
 * GroceryPOS - AnalyticsService
 * Aggregates sales data for the analytics module.
 */

class AnalyticsService
{
    /**
     * Daily summary for a given date.
     *
     * @param  string $date  Y-m-d
     * @return array
     */
    public static function getDailySummary(string $date): array
    {
        return self::aggregate($date, $date);
    }

    /**
     * Weekly summary from the Monday of the given week start date.
     *
     * @param  string $weekStart  Y-m-d (Monday)
     * @return array
     */
    public static function getWeeklySummary(string $weekStart): array
    {
        $start = date('Y-m-d', strtotime($weekStart));
        $end   = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        return self::aggregate($start, $end);
    }

    /**
     * Monthly summary.
     *
     * @param  int $year
     * @param  int $month
     * @return array
     */
    public static function getMonthlySummary(int $year, int $month): array
    {
        $start = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
        $end   = date('Y-m-t',  mktime(0, 0, 0, $month, 1, $year));
        return self::aggregate($start, $end);
    }

    /**
     * Yearly summary.
     *
     * @param  int $year
     * @return array
     */
    public static function getYearlySummary(int $year): array
    {
        return self::aggregate("{$year}-01-01", "{$year}-12-31");
    }

    /**
     * Product performance for a date range.
     *
     * @param  string   $dateFrom
     * @param  string   $dateTo
     * @param  int|null $categoryId
     * @return array
     */
    public static function getProductPerformance(string $dateFrom, string $dateTo, ?int $categoryId = null): array
    {
        $pdo    = Database::getInstance();
        $where  = ['DATE(t.created_at) BETWEEN :df AND :dt'];
        $params = [':df' => $dateFrom, ':dt' => $dateTo];

        if ($categoryId) {
            $where[]        = 'p.category_id = :cat';
            $params[':cat'] = $categoryId;
        }

        $sql = 'SELECT
                    ti.product_id,
                    ti.product_name_snapshot AS name,
                    SUM(ti.quantity)                                                   AS qty_sold,
                    SUM(ti.line_total)                                                 AS revenue,
                    SUM((ti.selling_price_snapshot - ti.cost_price_snapshot) * ti.quantity) AS profit
                  FROM transaction_items ti
                  JOIN transactions t ON t.id = ti.transaction_id
             LEFT JOIN products p ON p.id = ti.product_id
                 WHERE ' . implode(' AND ', $where) . '
              GROUP BY ti.product_id, ti.product_name_snapshot
              ORDER BY qty_sold DESC
                 LIMIT 50';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Compare current month vs previous month.
     *
     * @param  int $year
     * @param  int $month
     * @return array  {current, previous}
     */
    public static function getMonthComparison(int $year, int $month): array
    {
        $current  = self::getMonthlySummary($year, $month);

        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear  = $month === 1 ? $year - 1 : $year;
        $previous  = self::getMonthlySummary($prevYear, $prevMonth);

        return ['current' => $current, 'previous' => $previous];
    }

    /**
     * Revenue trend for a full year (12 months).
     *
     * @param  int $year
     * @return array  12 rows of {month, revenue, profit, transactions}
     */
    public static function getRevenueTrend(int $year): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT
                MONTH(t.created_at)                                                        AS month,
                COALESCE(SUM(t.total_amount), 0)                                           AS revenue,
                COALESCE(SUM((ti.selling_price_snapshot - ti.cost_price_snapshot) * ti.quantity), 0) AS profit,
                COUNT(DISTINCT t.id)                                                       AS transactions
               FROM transactions t
          LEFT JOIN transaction_items ti ON ti.transaction_id = t.id
              WHERE YEAR(t.created_at) = :year
           GROUP BY MONTH(t.created_at)
           ORDER BY MONTH(t.created_at)'
        );
        $stmt->execute([':year' => $year]);
        $rows = $stmt->fetchAll();

        // Fill all 12 months with zeros
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['month']] = $row;
        }

        $trend = [];
        for ($m = 1; $m <= 12; $m++) {
            $trend[] = $indexed[$m] ?? [
                'month'        => $m,
                'revenue'      => 0,
                'profit'       => 0,
                'transactions' => 0,
            ];
        }

        return $trend;
    }

    /**
     * Internal helper: aggregate revenue/profit/transactions/items for a date range.
     */
    private static function aggregate(string $dateFrom, string $dateTo): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(SUM(t.total_amount), 0)                                               AS total_revenue,
                COALESCE(SUM((ti.selling_price_snapshot - ti.cost_price_snapshot) * ti.quantity), 0) AS total_profit,
                COUNT(DISTINCT t.id)                                                           AS transaction_count,
                COALESCE(SUM(ti.quantity), 0)                                                  AS items_sold
               FROM transactions t
          LEFT JOIN transaction_items ti ON ti.transaction_id = t.id
              WHERE DATE(t.created_at) BETWEEN :df AND :dt'
        );
        $stmt->execute([':df' => $dateFrom, ':dt' => $dateTo]);
        $row = $stmt->fetch();

        return $row ?: [
            'total_revenue'     => 0,
            'total_profit'      => 0,
            'transaction_count' => 0,
            'items_sold'        => 0,
        ];
    }
}
