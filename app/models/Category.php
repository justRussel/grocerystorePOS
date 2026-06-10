<?php
/**
 * GroceryPOS - Category Model
 */

class Category
{
    /**
     * Return all categories ordered by name.
     *
     * @return array
     */
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query('SELECT id, name, slug, created_at FROM categories ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    /**
     * Find a single category by primary key.
     *
     * @param  int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, name, slug, created_at FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Create a new category.
     *
     * @param  array $data  Keys: name, slug
     * @return int          New category ID
     */
    public static function create(array $data): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO categories (name, slug, created_at) VALUES (:name, :slug, NOW())'
        );
        $stmt->execute([
            ':name' => trim($data['name'] ?? ''),
            ':slug' => trim($data['slug'] ?? self::toSlug($data['name'] ?? '')),
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing category.
     *
     * @param  int   $id
     * @param  array $data  Keys: name, slug
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'UPDATE categories SET name = :name, slug = :slug WHERE id = :id'
        );
        return $stmt->execute([
            ':name' => trim($data['name'] ?? ''),
            ':slug' => trim($data['slug'] ?? self::toSlug($data['name'] ?? '')),
            ':id'   => $id,
        ]);
    }

    /**
     * Delete a category by ID.
     *
     * @param  int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Convert a name to a URL-safe slug.
     *
     * @param  string $name
     * @return string
     */
    private static function toSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}
