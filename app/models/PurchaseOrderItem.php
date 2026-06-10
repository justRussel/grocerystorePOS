<?php
/**
 * GroceryPOS - PurchaseOrderItem Model
 */

class PurchaseOrderItem
{
    /**
     * Get all items for a given purchase order.
     *
     * @param  int $poId
     * @return array
     */
    public static function findByPO(int $poId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT poi.*, p.name AS product_name
               FROM purchase_order_items poi
          LEFT JOIN products p ON p.id = poi.product_id
              WHERE poi.po_id = :po_id
              ORDER BY poi.id ASC'
        );
        $stmt->execute([':po_id' => $poId]);
        return $stmt->fetchAll();
    }

    /**
     * Bulk insert PO line items.
     *
     * @param  int   $poId
     * @param  array $items  Each: product_id, quantity, unit_cost, line_total
     */
    public static function bulkInsert(int $poId, array $items): void
    {
        if (empty($items)) return;

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO purchase_order_items (po_id, product_id, quantity, unit_cost, line_total)
             VALUES (:po_id, :product_id, :quantity, :unit_cost, :line_total)'
        );

        foreach ($items as $item) {
            $stmt->execute([
                ':po_id'      => $poId,
                ':product_id' => (int) $item['product_id'],
                ':quantity'   => (int) $item['quantity'],
                ':unit_cost'  => (float) $item['unit_cost'],
                ':line_total' => (float) $item['line_total'],
            ]);
        }
    }
}
