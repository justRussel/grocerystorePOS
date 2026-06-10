<?php
/**
 * GroceryPOS - User Model
 * Handles all database operations for the users table using PDO prepared statements.
 */

class User
{
    /**
     * Find a user by their email address.
     *
     * @param  string $email
     * @return array|null  Associative row or null if not found
     */
    public static function findByEmail(string $email): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, phone, password_hash,
                    store_name, store_address, tax_id, photo,
                    created_at, updated_at
               FROM users
              WHERE email = :email
              LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Find a user by their primary key.
     *
     * @param  int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, phone, password_hash,
                    store_name, store_address, tax_id, photo,
                    created_at, updated_at
               FROM users
              WHERE id = :id
              LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Update a user's profile information (name, email, phone, store_name, store_address, tax_id).
     *
     * @param  int   $id
     * @param  array $data  Keys: full_name, email, phone, store_name, store_address, tax_id
     * @return bool
     */
    public static function updateProfile(int $id, array $data): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'UPDATE users
                SET full_name     = :full_name,
                    email         = :email,
                    phone         = :phone,
                    store_name    = :store_name,
                    store_address = :store_address,
                    tax_id        = :tax_id,
                    updated_at    = NOW()
              WHERE id = :id'
        );

        return $stmt->execute([
            ':full_name'     => $data['full_name']     ?? '',
            ':email'         => $data['email']         ?? '',
            ':phone'         => $data['phone']         ?? '',
            ':store_name'    => $data['store_name']    ?? '',
            ':store_address' => $data['store_address'] ?? '',
            ':tax_id'        => $data['tax_id']        ?? '',
            ':id'            => $id,
        ]);
    }

    /**
     * Update a user's password.
     * Hashes the plain-text password using BCRYPT before storing.
     *
     * @param  int    $id
     * @param  string $newPassword  Plain-text password
     * @return bool
     */
    public static function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'UPDATE users
                SET password_hash = :hash,
                    updated_at    = NOW()
              WHERE id = :id'
        );

        return $stmt->execute([
            ':hash' => $hash,
            ':id'   => $id,
        ]);
    }

    /**
     * Create a new user account.
     * Hashes the plain-text password with BCRYPT before storing.
     *
     * @param  array $data  Keys: full_name, email, phone (optional), password, store_name (optional)
     * @return int          New user ID
     * @throws PDOException if the email already exists (UNIQUE constraint)
     */
    public static function create(array $data): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, store_name, created_at, updated_at)
             VALUES (:full_name, :email, :phone, :password_hash, :store_name, NOW(), NOW())'
        );
        $stmt->execute([
            ':full_name'     => trim($data['full_name'] ?? ''),
            ':email'         => strtolower(trim($data['email'] ?? '')),
            ':phone'         => trim($data['phone'] ?? ''),
            ':password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':store_name'    => trim($data['store_name'] ?? ''),
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Check whether an email address is already registered.
     *
     * @param  string $email
     * @return bool
     */
    public static function emailExists(string $email): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => strtolower(trim($email))]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Update a user's profile photo filename.
     *
     * @param  int    $id
     * @param  string $filename  Relative filename stored under uploads/
     * @return bool
     */
    public static function updatePhoto(int $id, string $filename): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'UPDATE users
                SET photo      = :photo,
                    updated_at = NOW()
              WHERE id = :id'
        );

        return $stmt->execute([
            ':photo' => $filename,
            ':id'    => $id,
        ]);
    }
}
