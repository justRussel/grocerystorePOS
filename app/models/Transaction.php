<?php
/**
 * GroceryPOS - Transaction Model
 */

class Transaction
{
    /**
     * Generate a unique receipt number in format RCP-YYYYMMDD-XXXX.
     *
     * @return string
     */
    public static function generateReceiptNo(): string
    {
        $pdo    = Database::getInstance();
        $prefix = 'RCP-' . date('Ymd') . '-';

        $stmt = $pdo->prepare(
            "SELECT receipt_no FROM transactions
              WHERE receipt_no LIKE :prefix
              ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a transaction header and its line items within a DB transaction.
     *
     * @param  array $header  subtotal, discount_amount, total_amount, payment_method,
     *                        cash_tendered, change_amount, cashier_id, receipt_no
     * @param  array $items   Each: product_id, product_name_snapshot, cost_price_snapshot,
     *                        selling_price_snapshot, quantity, line_total
     * @return int            New transaction ID
     */
    public static function create(array $header, array $items): int
    {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare(
            'INSERT INTO transactions
                (receipt_no, subtotal, discount_amount, total_amount,
                 payment_method, cash_tendered, change_amount, cashier_id, created_at)
             VALUES
                (:receipt_no, :subtotal, :discount_amount, :total_amount,
                 :payment_method, :cash_tendered, :change_amount, :cashier_id, NOW())'
        );
        $stmt->execute([
            ':receipt_no'      => $header['receipt_no'],
            ':subtotal'        => $header['subtotal'],
            ':discount_amount' => $header['discount_amount'] ?? 0,
            ':total_amount'    => $header['total_amount'],
            ':payment_method'  => $header['payment_method']  ?? 'cash',
            ':cash_tendered'   => $header['cash_tendered']   ?? null,
            ':change_amount'   => $header['change_amount']   ?? 0,
            ':cashier_id'      => $header['cashier_id']      ?? null,
        ]);

        $transactionId = (int) $pdo->lastInsertId();
        TransactionItem::bulkInsert($transactionId, $items);

        return $transactionId;
    }

    /**
     * Find a transaction by primary key (includes items).
     *
     * @param  int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT t.*, u.full_name AS cashier_name
               FROM transactions t
          LEFT JOIN users u ON u.id = t.cashier_id
              WHERE t.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['items'] = TransactionItem::findByTransaction($id);
        return $row;
    }

    /**
     * Find a transaction by receipt number.
     *
     * @param  string $receiptNo
     * @return array|null
     */
    public static function findByReceiptNo(string $receiptNo): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT t.*, u.full_name AS cashier_name
               FROM transactions t
          LEFT JOIN users u ON u.id = t.cashier_id
              WHERE t.receipt_no = :rno LIMIT 1'
        );
        $stmt->execute([':rno' => $receiptNo]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['items'] = TransactionItem::findByTransaction($row['id']);
        return $row;
    }

    /**
     * Find all transactions with optional filters and pagination.
     *
     * @param  array $filters  Keys: receipt_no, date_from, date_to, payment_method, limit, offset
     * @return array
     */
    public static function findAll(array $filters = []): array
    {
        $pdo    = Database::getInstance();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['receipt_no'])) {
            $where[]              = 't.receipt_no LIKE :rno';
            $params[':rno']       = '%' . $filters['receipt_no'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[]              = 'DATE(t.created_at) >= :df';
            $params[':df']        = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[]              = 'DATE(t.created_at) <= :dt';
            $params[':dt']        = $filters['date_to'];
        }

        if (!empty($filters['payment_method'])) {
            $where[]              = 't.payment_method = :pm';
            $params[':pm']        = $filters['payment_method'];
        }

        $limit  = (int) ($filters['limit']  ?? 50);
        $offset = (int) ($filters['offset'] ?? 0);

        $sql = 'SELECT t.*, u.full_name AS cashier_name
                  FROM transactions t
             LEFT JOIN users u ON u.id = t.cashier_id
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY t.created_at DESC
                 LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get aggregated summary for a date range.
     *
     * @param  string $dateFrom  Y-m-d
     * @param  string $dateTo    Y-m-d
     * @return array
     */
    public static function getSummary(string $dateFrom, string $dateTo): array
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
        return $stmt->fetch() ?: [];
    }

    /**
     * Get payment method split for a date range.
     *
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public static function getPaymentSplit(string $dateFrom, string $dateTo): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT payment_method,
                    COUNT(*) AS count,
                    SUM(total_amount) AS total
               FROM transactions
              WHERE DATE(created_at) BETWEEN :df AND :dt
           GROUP BY payment_method'
        );
        $stmt->execute([':df' => $dateFrom, ':dt' => $dateTo]);
        return $stmt->fetchAll();
    }

    /**
     * Count total transactions matching filters (for pagination).
     *
     * @param  array $filters
     * @return int
     */
    public static function countAll(array $filters = []): int
    {
        $pdo    = Database::getInstance();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['receipt_no'])) {
            $where[]        = 'receipt_no LIKE :rno';
            $params[':rno'] = '%' . $filters['receipt_no'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[]       = 'DATE(created_at) >= :df';
            $params[':df'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]       = 'DATE(created_at) <= :dt';
            $params[':dt'] = $filters['date_to'];
        }
        if (!empty($filters['payment_method'])) {
            $where[]       = 'payment_method = :pm';
            $params[':pm'] = $filters['payment_method'];
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE ' . implode(' AND ', $where));
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
