<?php
/**
 * GroceryPOS - Supplier Model
 */

class Supplier
{
    /**
     * Return all suppliers ordered by company name.
     *
     * @return array
     */
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query(
            'SELECT * FROM suppliers ORDER BY company_name ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Find a single supplier by primary key.
     *
     * @param  int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Create a new supplier.
     *
     * @param  array $data
     * @return int
     */
    public static function create(array $data): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO suppliers (company_name, contact_person, phone, email, address, created_at, updated_at)
             VALUES (:company_name, :contact_person, :phone, :email, :address, NOW(), NOW())'
        );
        $stmt->execute([
            ':company_name'   => trim($data['company_name']   ?? ''),
            ':contact_person' => trim($data['contact_person'] ?? ''),
            ':phone'          => trim($data['phone']          ?? ''),
            ':email'          => trim($data['email']          ?? ''),
            ':address'        => trim($data['address']        ?? ''),
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing supplier.
     *
     * @param  int   $id
     * @param  array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'UPDATE suppliers
                SET company_name   = :company_name,
                    contact_person = :contact_person,
                    phone          = :phone,
                    email          = :email,
                    address        = :address,
                    updated_at     = NOW()
              WHERE id = :id'
        );
        return $stmt->execute([
            ':company_name'   => trim($data['company_name']   ?? ''),
            ':contact_person' => trim($data['contact_person'] ?? ''),
            ':phone'          => trim($data['phone']          ?? ''),
            ':email'          => trim($data['email']          ?? ''),
            ':address'        => trim($data['address']        ?? ''),
            ':id'             => $id,
        ]);
    }

    /**
     * Delete a supplier by ID.
     *
     * @param  int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Count the number of products linked to a supplier.
     *
     * @param  int $supplierId
     * @return int
     */
    public static function getProductCount(int $supplierId): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM supplier_products WHERE supplier_id = :sid'
        );
        $stmt->execute([':sid' => $supplierId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get the date of the last purchase order for a supplier.
     *
     * @param  int $supplierId
     * @return string|null
     */
    public static function getLastOrderDate(int $supplierId): ?string
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT MAX(ordered_at) FROM purchase_orders WHERE supplier_id = :sid'
        );
        $stmt->execute([':sid' => $supplierId]);
        $val = $stmt->fetchColumn();
        return $val ?: null;
    }
}
