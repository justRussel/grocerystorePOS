<?php
/**
 * GroceryPOS - PurchaseOrder Model
 */

class PurchaseOrder
{
    /**
     * Generate a unique PO number in format PO-YYYYMMDD-XXXX.
     *
     * @return string
     */
    public static function generatePONumber(): string
    {
        $pdo    = Database::getInstance();
        $prefix = 'PO-' . date('Ymd') . '-';

        $stmt = $pdo->prepare(
            "SELECT po_number FROM purchase_orders
              WHERE po_number LIKE :prefix
              ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a purchase order with its line items.
     *
     * @param  array $header  supplier_id, total_amount, created_by
     * @param  array $items   Each: product_id, quantity, unit_cost, line_total
     * @return int            New PO ID
     */
    public static function create(array $header, array $items): int
    {
        $pdo      = Database::getInstance();
        $poNumber = self::generatePONumber();

        $stmt = $pdo->prepare(
            'INSERT INTO purchase_orders
                (po_number, supplier_id, total_amount, status, created_by, ordered_at)
             VALUES
                (:po_number, :supplier_id, :total_amount, :status, :created_by, NOW())'
        );
        $stmt->execute([
            ':po_number'    => $poNumber,
            ':supplier_id'  => (int) $header['supplier_id'],
            ':total_amount' => (float) ($header['total_amount'] ?? 0),
            ':status'       => 'pending',
            ':created_by'   => $header['created_by'] ?? null,
        ]);

        $poId = (int) $pdo->lastInsertId();
        PurchaseOrderItem::bulkInsert($poId, $items);

        return $poId;
    }

    /**
     * Find all POs with optional filters.
     *
     * @param  array $filters  Keys: supplier_id, status
     * @return array
     */
    public static function findAll(array $filters = []): array
    {
        $pdo    = Database::getInstance();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['supplier_id'])) {
            $where[]            = 'po.supplier_id = :sid';
            $params[':sid']     = (int) $filters['supplier_id'];
        }

        if (!empty($filters['status'])) {
            $where[]            = 'po.status = :st';
            $params[':st']      = $filters['status'];
        }

        $sql = 'SELECT po.*, s.company_name AS supplier_name, u.full_name AS created_by_name
                  FROM purchase_orders po
             LEFT JOIN suppliers s ON s.id = po.supplier_id
             LEFT JOIN users u     ON u.id = po.created_by
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY po.ordered_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find a single PO by primary key (includes items).
     *
     * @param  int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT po.*, s.company_name AS supplier_name, u.full_name AS created_by_name
               FROM purchase_orders po
          LEFT JOIN suppliers s ON s.id = po.supplier_id
          LEFT JOIN users u     ON u.id = po.created_by
              WHERE po.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['items'] = PurchaseOrderItem::findByPO($id);
        return $row;
    }

    /**
     * Mark a PO as received; triggers a stock_in for each item.
     *
     * @param  int $id
     * @return bool
     */
    public static function markReceived(int $id): bool
    {
        $pdo = Database::getInstance();
        $po  = self::findById($id);

        if (!$po || $po['status'] !== 'pending') return false;

        // Stock-in each item
        foreach ($po['items'] as $item) {
            $product = Product::findById((int) $item['product_id']);
            if (!$product) continue;

            $qtyBefore = (int) $product['stock_qty'];
            $qtyAfter  = $qtyBefore + (int) $item['quantity'];

            Product::updateStock((int) $item['product_id'], $qtyAfter);
            StockMovement::record([
                'product_id'    => $item['product_id'],
                'movement_type' => 'stock_in',
                'qty_change'    => (int) $item['quantity'],
                'qty_before'    => $qtyBefore,
                'qty_after'     => $qtyAfter,
                'reason'        => 'Purchase Order Received',
                'reference'     => $po['po_number'],
                'created_by'    => $_SESSION['user_id'] ?? null,
            ]);
        }

        $stmt = $pdo->prepare(
            'UPDATE purchase_orders SET status = :st, received_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([':st' => 'received', ':id' => $id]);
    }

    /**
     * Cancel a pending purchase order.
     *
     * @param  int $id
     * @return bool
     */
    public static function cancel(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "UPDATE purchase_orders SET status = 'cancelled' WHERE id = :id AND status = 'pending'"
        );
        return $stmt->execute([':id' => $id]);
    }
}
