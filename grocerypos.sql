-- ============================================================
--  GroceryPOS Database Schema
--  Run this file once to initialise the database.
--  Order: users → categories → products → transactions →
--         transaction_items → stock_movements → suppliers →
--         supplier_products → purchase_orders →
--         purchase_order_items
-- ============================================================

CREATE DATABASE IF NOT EXISTS `grocerypos`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `grocerypos`;

-- ─── Users / Account ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `full_name`     VARCHAR(150) NOT NULL,
    `email`         VARCHAR(150) NOT NULL UNIQUE,
    `phone`         VARCHAR(30),
    `password_hash` VARCHAR(255) NOT NULL,
    `store_name`    VARCHAR(150),
    `store_address` TEXT,
    `tax_id`        VARCHAR(50),
    `photo`         VARCHAR(255),
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Product Categories ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL,
    `slug`       VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Products ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `products` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `barcode`             VARCHAR(50) UNIQUE,
    `name`                VARCHAR(200) NOT NULL,
    `category_id`         INT NOT NULL,
    `cost_price`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `selling_price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock_qty`           INT NOT NULL DEFAULT 0,
    `low_stock_threshold` INT NOT NULL DEFAULT 10,
    `expiry_date`         DATE,
    `image`               VARCHAR(255),
    `status`              ENUM('active','low_stock','out_of_stock','expiring_soon') DEFAULT 'active',
    `is_active`           TINYINT(1) DEFAULT 1,
    `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Sales Transactions ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transactions` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_no`      VARCHAR(30) NOT NULL UNIQUE,
    `subtotal`        DECIMAL(10,2) NOT NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount`    DECIMAL(10,2) NOT NULL,
    `payment_method`  ENUM('cash','digital') NOT NULL DEFAULT 'cash',
    `cash_tendered`   DECIMAL(10,2),
    `change_amount`   DECIMAL(10,2),
    `cashier_id`      INT,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Transaction Line Items ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transaction_items` (
    `id`                     INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id`         INT NOT NULL,
    `product_id`             INT NOT NULL,
    `product_name_snapshot`  VARCHAR(200) NOT NULL,
    `cost_price_snapshot`    DECIMAL(10,2) NOT NULL,
    `selling_price_snapshot` DECIMAL(10,2) NOT NULL,
    `quantity`               INT NOT NULL,
    `line_total`             DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`)     REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Inventory Stock Movements ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `product_id`    INT NOT NULL,
    `movement_type` ENUM('stock_in','stock_out','adjustment','sale') NOT NULL,
    `qty_change`    INT NOT NULL,
    `qty_before`    INT NOT NULL,
    `qty_after`     INT NOT NULL,
    `reason`        VARCHAR(255),
    `reference`     VARCHAR(100),
    `created_by`    INT,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Suppliers ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `company_name`   VARCHAR(200) NOT NULL,
    `contact_person` VARCHAR(150),
    `phone`          VARCHAR(30),
    `email`          VARCHAR(150),
    `address`        TEXT,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Supplier ↔ Product Mapping ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `supplier_products` (
    `supplier_id` INT NOT NULL,
    `product_id`  INT NOT NULL,
    PRIMARY KEY (`supplier_id`, `product_id`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    FOREIGN KEY (`product_id`)  REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Purchase Orders ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `po_number`    VARCHAR(30) NOT NULL UNIQUE,
    `supplier_id`  INT NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status`       ENUM('pending','received','cancelled') NOT NULL DEFAULT 'pending',
    `created_by`   INT,
    `ordered_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `received_at`  TIMESTAMP NULL,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Purchase Order Line Items ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `po_id`      INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity`   INT NOT NULL,
    `unit_cost`  DECIMAL(10,2) NOT NULL,
    `line_total` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`po_id`)      REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
