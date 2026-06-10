<?php
/**
 * GroceryPOS - TransactionItem Model
 */

class TransactionItem
{
    /**
     * Get all items for a given transaction.
     *
     * @param  int $transactionId
     * @return array
     */
    public static function findByTransaction(int $transactionId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT ti.*, p.image
               FROM transaction_items ti
          LEFT JOIN products p ON p.id = ti.product_id
              WHERE ti.transaction_id = :tid
              ORDER BY ti.id ASC'
        );
        $stmt->execute([':tid' => $transactionId]);
        return $stmt->fetchAll();
    }

    /**
     * Bulk insert transaction line items.
     *
     * @param  int   $transactionId
     * @param  array $items  Each: product_id, product_name_snapshot, cost_price_snapshot,
     *                       selling_price_snapshot, quantity, line_total
     */
    public static function bulkInsert(int $transactionId, array $items): void
    {
        if (empty($items)) return;

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO transaction_items
                (transaction_id, product_id, product_name_snapshot, cost_price_snapshot,
                 selling_price_snapshot, quantity, line_total)
             VALUES
                (:tid, :pid, :name, :cost, :sell, :qty, :total)'
        );

        foreach ($items as $item) {
            $stmt->execute([
                ':tid'   => $transactionId,
                ':pid'   => (int) $item['product_id'],
                ':name'  => $item['product_name_snapshot'],
                ':cost'  => (float) $item['cost_price_snapshot'],
                ':sell'  => (float) $item['selling_price_snapshot'],
                ':qty'   => (int) $item['quantity'],
                ':total' => (float) $item['line_total'],
            ]);
        }
    }
}
