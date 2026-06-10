<?php
/**
 * GroceryPOS - StockMovement Model
 * Immutable audit trail of every stock change.
 */

class StockMovement
{
    /**
     * Record a stock movement.
     *
     * @param  array $data  product_id, movement_type, qty_change, qty_before, qty_after,
     *                      reason, reference, created_by
     * @return int          New movement ID
     */
    public static function record(array $data): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO stock_movements
                (product_id, movement_type, qty_change, qty_before, qty_after,
                 reason, reference, created_by, created_at)
             VALUES
                (:product_id, :movement_type, :qty_change, :qty_before, :qty_after,
                 :reason, :reference, :created_by, NOW())'
        );
        $stmt->execute([
            ':product_id'    => (int) $data['product_id'],
            ':movement_type' => $data['movement_type'],
            ':qty_change'    => (int) $data['qty_change'],
            ':qty_before'    => (int) $data['qty_before'],
            ':qty_after'     => (int) $data['qty_after'],
            ':reason'        => $data['reason']     ?? null,
            ':reference'     => $data['reference']  ?? null,
            ':created_by'    => $data['created_by'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Get all movements for a specific product.
     *
     * @param  int $productId
     * @return array
     */
    public static function findByProduct(int $productId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT sm.*, u.full_name AS created_by_name
               FROM stock_movements sm
          LEFT JOIN users u ON u.id = sm.created_by
              WHERE sm.product_id = :pid
              ORDER BY sm.created_at DESC'
        );
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Get movements with optional filters.
     *
     * @param  array $filters  Keys: product_id, movement_type, date_from, date_to, limit, offset
     * @return array
     */
    public static function findAll(array $filters = []): array
    {
        $pdo    = Database::getInstance();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['product_id'])) {
            $where[]             = 'sm.product_id = :pid';
            $params[':pid']      = (int) $filters['product_id'];
        }

        if (!empty($filters['movement_type'])) {
            $where[]             = 'sm.movement_type = :mt';
            $params[':mt']       = $filters['movement_type'];
        }

        if (!empty($filters['date_from'])) {
            $where[]             = 'DATE(sm.created_at) >= :df';
            $params[':df']       = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[]             = 'DATE(sm.created_at) <= :dt';
            $params[':dt']       = $filters['date_to'];
        }

        $limit  = (int) ($filters['limit']  ?? 100);
        $offset = (int) ($filters['offset'] ?? 0);

        $sql = 'SELECT sm.*, p.name AS product_name, u.full_name AS created_by_name
                  FROM stock_movements sm
             LEFT JOIN products p ON p.id = sm.product_id
             LEFT JOIN users u ON u.id = sm.created_by
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY sm.created_at DESC
                 LIMIT :lim OFFSET :off';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Alias for findAll — used as an audit log view.
     *
     * @param  array $filters
     * @return array
     */
    public static function getAuditLogs(array $filters = []): array
    {
        return self::findAll($filters);
    }
}
