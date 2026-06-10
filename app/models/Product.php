<?php
/**
 * GroceryPOS - Product Model
 */

class Product
{
    /**
     * Return all active products with optional filters.
     *
     * @param  array $filters  Keys: category_id, status, search, is_active
     * @return array
     */
    public static function findAll(array $filters = []): array
    {
        $pdo = Database::getInstance();

        $where  = ['p.is_active = :is_active'];
        $params = [':is_active' => $filters['is_active'] ?? 1];

        if (!empty($filters['category_id'])) {
            $where[]                = 'p.category_id = :category_id';
            $params[':category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $where[]         = 'p.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[]          = '(p.name LIKE :search OR p.barcode LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = 'SELECT p.*, c.name AS category_name
                  FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY p.name ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find a single product by primary key.
     *
     * @param  int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT p.*, c.name AS category_name
               FROM products p
          LEFT JOIN categories c ON c.id = p.category_id
              WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Find a product by barcode.
     *
     * @param  string $barcode
     * @return array|null
     */
    public static function findByBarcode(string $barcode): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT p.*, c.name AS category_name
               FROM products p
          LEFT JOIN categories c ON c.id = p.category_id
              WHERE p.barcode = :barcode AND p.is_active = 1 LIMIT 1'
        );
        $stmt->execute([':barcode' => $barcode]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Search products by keyword and optional category.
     *
     * @param  string   $keyword
     * @param  int|null $categoryId
     * @return array
     */
    public static function search(string $keyword, ?int $categoryId = null): array
    {
        $pdo    = Database::getInstance();
        $where  = ['p.is_active = 1', '(p.name LIKE :kw OR p.barcode LIKE :kw)'];
        $params = [':kw' => '%' . $keyword . '%'];

        if ($categoryId) {
            $where[]                = 'p.category_id = :cat';
            $params[':cat']         = $categoryId;
        }

        $sql = 'SELECT p.*, c.name AS category_name
                  FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY p.name ASC
                 LIMIT 50';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create a new product and return its ID.
     *
     * @param  array $data
     * @return int
     */
    public static function create(array $data): int
    {
        $pdo = Database::getInstance();

        $status = self::computeStatus($data);

        $stmt = $pdo->prepare(
            'INSERT INTO products
                (barcode, name, category_id, cost_price, selling_price,
                 stock_qty, low_stock_threshold, expiry_date, image, status, is_active,
                 created_at, updated_at)
             VALUES
                (:barcode, :name, :category_id, :cost_price, :selling_price,
                 :stock_qty, :low_stock_threshold, :expiry_date, :image, :status, 1,
                 NOW(), NOW())'
        );
        $stmt->execute([
            ':barcode'             => $data['barcode']             ?? null,
            ':name'                => $data['name']                ?? '',
            ':category_id'         => (int) ($data['category_id'] ?? 0),
            ':cost_price'          => (float) ($data['cost_price'] ?? 0),
            ':selling_price'       => (float) ($data['selling_price'] ?? 0),
            ':stock_qty'           => (int) ($data['stock_qty'] ?? 0),
            ':low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? LOW_STOCK_DEFAULT),
            ':expiry_date'         => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            ':image'               => $data['image']               ?? null,
            ':status'              => $status,
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing product.
     *
     * @param  int   $id
     * @param  array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getInstance();

        // Merge existing data for status computation if partial update
        $existing = self::findById($id) ?? [];
        $merged   = array_merge($existing, $data);
        $status   = self::computeStatus($merged);

        $stmt = $pdo->prepare(
            'UPDATE products
                SET barcode             = :barcode,
                    name                = :name,
                    category_id         = :category_id,
                    cost_price          = :cost_price,
                    selling_price       = :selling_price,
                    stock_qty           = :stock_qty,
                    low_stock_threshold = :low_stock_threshold,
                    expiry_date         = :expiry_date,
                    image               = :image,
                    status              = :status,
                    updated_at          = NOW()
              WHERE id = :id'
        );
        return $stmt->execute([
            ':barcode'             => $data['barcode']             ?? $existing['barcode']             ?? null,
            ':name'                => $data['name']                ?? $existing['name']                ?? '',
            ':category_id'         => (int) ($data['category_id'] ?? $existing['category_id']         ?? 0),
            ':cost_price'          => (float) ($data['cost_price'] ?? $existing['cost_price']         ?? 0),
            ':selling_price'       => (float) ($data['selling_price'] ?? $existing['selling_price']   ?? 0),
            ':stock_qty'           => (int) ($data['stock_qty']    ?? $existing['stock_qty']          ?? 0),
            ':low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? $existing['low_stock_threshold'] ?? LOW_STOCK_DEFAULT),
            ':expiry_date'         => !empty($data['expiry_date'])
                                        ? $data['expiry_date']
                                        : ($existing['expiry_date'] ?? null),
            ':image'               => $data['image']               ?? $existing['image']               ?? null,
            ':status'              => $status,
            ':id'                  => $id,
        ]);
    }

    /**
     * Soft-delete a product (set is_active = 0).
     *
     * @param  int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Update a product's stock quantity and recompute status.
     *
     * @param  int $id
     * @param  int $newQty
     * @return bool
     */
    public static function updateStock(int $id, int $newQty): bool
    {
        $pdo     = Database::getInstance();
        $product = self::findById($id);
        if (!$product) return false;

        $merged = array_merge($product, ['stock_qty' => $newQty]);
        $status = self::computeStatus($merged);

        $stmt = $pdo->prepare(
            'UPDATE products SET stock_qty = :qty, status = :status, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([':qty' => $newQty, ':status' => $status, ':id' => $id]);
    }

    /**
     * Get all products at or below their low-stock threshold.
     *
     * @return array
     */
    public static function getLowStock(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT p.*, c.name AS category_name
               FROM products p
          LEFT JOIN categories c ON c.id = p.category_id
              WHERE p.is_active = 1
                AND p.stock_qty <= p.low_stock_threshold
                AND p.stock_qty > 0
              ORDER BY p.stock_qty ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get products expiring within N days.
     *
     * @param  int $daysAhead
     * @return array
     */
    public static function getExpiringSoon(int $daysAhead = 30): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT p.*, c.name AS category_name
               FROM products p
          LEFT JOIN categories c ON c.id = p.category_id
              WHERE p.is_active = 1
                AND p.expiry_date IS NOT NULL
                AND p.expiry_date >= CURDATE()
                AND p.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
              ORDER BY p.expiry_date ASC'
        );
        $stmt->execute([':days' => $daysAhead]);
        return $stmt->fetchAll();
    }

    /**
     * Compute the status string for a product based on its data.
     *
     * @param  array $product  Must have: stock_qty, low_stock_threshold, expiry_date
     * @return string
     */
    public static function computeStatus(array $product): string
    {
        $qty       = (int) ($product['stock_qty'] ?? 0);
        $threshold = (int) ($product['low_stock_threshold'] ?? LOW_STOCK_DEFAULT);
        $expiry    = $product['expiry_date'] ?? null;

        if ($qty === 0) {
            return 'out_of_stock';
        }

        if ($expiry && $expiry !== '0000-00-00') {
            $today      = new DateTime('today');
            $expiryDate = new DateTime($expiry);
            $diff       = (int) $today->diff($expiryDate)->format('%r%a');
            if ($diff >= 0 && $diff <= EXPIRY_WARNING_DAYS) {
                return 'expiring_soon';
            }
        }

        if ($qty <= $threshold) {
            return 'low_stock';
        }

        return 'active';
    }

    /**
     * Import products from a CSV file.
     * Expected columns: barcode, name, category_id, cost_price, selling_price, stock_qty,
     *                   low_stock_threshold, expiry_date
     *
     * @param  string $filePath
     * @return array  ['imported' => int, 'errors' => array]
     */
    public static function importFromCSV(string $filePath): array
    {
        $imported = 0;
        $errors   = [];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['imported' => 0, 'errors' => ['File not found or not readable.']];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['imported' => 0, 'errors' => ['Could not open file.']];
        }

        // Skip header row
        $headers = fgetcsv($handle);
        $line    = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count($row) < 5) {
                $errors[] = "Line {$line}: insufficient columns.";
                continue;
            }

            $data = [
                'barcode'             => trim($row[0] ?? ''),
                'name'                => trim($row[1] ?? ''),
                'category_id'         => (int) ($row[2] ?? 0),
                'cost_price'          => (float) ($row[3] ?? 0),
                'selling_price'       => (float) ($row[4] ?? 0),
                'stock_qty'           => (int) ($row[5] ?? 0),
                'low_stock_threshold' => (int) ($row[6] ?? LOW_STOCK_DEFAULT),
                'expiry_date'         => trim($row[7] ?? ''),
            ];

            if (empty($data['name'])) {
                $errors[] = "Line {$line}: product name is required.";
                continue;
            }

            try {
                self::create($data);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Line {$line}: " . $e->getMessage();
            }
        }

        fclose($handle);
        return ['imported' => $imported, 'errors' => $errors];
    }
}
