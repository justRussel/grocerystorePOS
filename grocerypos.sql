-- ============================================================
--  GroceryPOS Database Schema
--  Run this file once to initialise the database.
--  Order: users → categories → products → transactions →
--         transaction_items → stock_movements → suppliers →
--         supplier_products → purchase_orders →
--         purchase_order_items
-- ============================================================

-- Database is created via cPanel. Import this file into your existing database.

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

-- ─── Seed: Product Categories ─────────────────────────────────────────────────
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`) VALUES
(1,  'Beverages',    'beverages'),
(2,  'Snacks',       'snacks'),
(3,  'Fruits',       'fruits'),
(4,  'Vegetables',   'vegetables'),
(5,  'Dairy',        'dairy'),
(6,  'Bread & Bakery','bread-bakery'),
(7,  'Canned Goods', 'canned-goods'),
(8,  'Personal Care','personal-care');

-- ─── Seed: Products ───────────────────────────────────────────────────────────
INSERT IGNORE INTO `products`
    (`id`, `barcode`, `name`, `category_id`, `cost_price`, `selling_price`, `stock_qty`, `low_stock_threshold`, `status`, `is_active`)
VALUES
-- C2 Beverages (Beverages - category 1)
(1,  '4800888010011', 'C2 Apple Green Tea 230ml',     1,  12.00, 18.00, 100, 10, 'active', 1),
(2,  '4800888010012', 'C2 Apple Green Tea 500ml',     1,  20.00, 28.00, 80,  10, 'active', 1),
(3,  '4800888010021', 'C2 Lemon Green Tea 230ml',     1,  12.00, 18.00, 100, 10, 'active', 1),
(4,  '4800888010022', 'C2 Lemon Green Tea 500ml',     1,  20.00, 28.00, 80,  10, 'active', 1),
(5,  '4800888010031', 'C2 Original Green Tea 230ml',  1,  12.00, 18.00, 120, 10, 'active', 1),
(6,  '4800888010032', 'C2 Original Green Tea 500ml',  1,  20.00, 28.00, 80,  10, 'active', 1),
(7,  '4800888010041', 'C2 Classic (Yellow) 230ml',    1,  12.00, 18.00, 100, 10, 'active', 1),
(8,  '4800888010042', 'C2 Classic (Yellow) 500ml',    1,  20.00, 28.00, 80,  10, 'active', 1),

-- Piatos Snacks (Snacks - category 2)
(9,  '4800016010011', 'Piatos Cheese 115g',            2,  22.00, 30.00, 60,  10, 'active', 1),
(10, '4800016010012', 'Piatos BBQ 115g',               2,  22.00, 30.00, 60,  10, 'active', 1),
(11, '4800016010013', 'Piatos Sour Cream & Onion 115g',2,  22.00, 30.00, 60,  10, 'active', 1),
(12, '4800016010014', 'Piatos Chili & Cheese 115g',    2,  22.00, 30.00, 60,  10, 'active', 1),
(13, '4800016010015', 'Piatos Garlic 115g',            2,  22.00, 30.00, 60,  10, 'active', 1),
(14, '4800016010021', 'Piatos Cheese 55g',             2,  11.00, 15.00, 80,  15, 'active', 1),
(15, '4800016010022', 'Piatos BBQ 55g',                2,  11.00, 15.00, 80,  15, 'active', 1),

-- Fruits (Fruits - category 3)
(16, NULL, 'Banana (Lakatan) per kg',  3,  40.00,  60.00, 50, 5, 'active', 1),
(17, NULL, 'Banana (Saba) per kg',     3,  30.00,  45.00, 50, 5, 'active', 1),
(18, NULL, 'Apple (Red) per piece',    3,  25.00,  40.00, 80, 5, 'active', 1),
(19, NULL, 'Apple (Green) per piece',  3,  25.00,  40.00, 80, 5, 'active', 1),
(20, NULL, 'Mango (Carabao) per kg',   3,  60.00,  90.00, 40, 5, 'active', 1),
(21, NULL, 'Mango (Green) per kg',     3,  40.00,  65.00, 40, 5, 'active', 1),
(22, NULL, 'Pineapple per piece',      3,  45.00,  70.00, 30, 5, 'active', 1),
(23, NULL, 'Watermelon per kg',        3,  20.00,  35.00, 20, 3, 'active', 1),
(24, NULL, 'Grapes (Red) per 500g',    3,  80.00, 120.00, 30, 5, 'active', 1),
(25, NULL, 'Orange per piece',         3,  20.00,  35.00, 60, 5, 'active', 1),

-- Vegetables (Vegetables - category 4)
(26, NULL, 'Tomato per kg',            4,  30.00,  50.00, 40, 5, 'active', 1),
(27, NULL, 'Onion (Red) per kg',       4,  50.00,  80.00, 40, 5, 'active', 1),
(28, NULL, 'Onion (White) per kg',     4,  45.00,  70.00, 40, 5, 'active', 1),
(29, NULL, 'Cabbage per head',         4,  35.00,  55.00, 30, 5, 'active', 1),
(30, NULL, 'Carrots per kg',           4,  40.00,  65.00, 40, 5, 'active', 1),
(31, NULL, 'Potato per kg',            4,  45.00,  70.00, 40, 5, 'active', 1),
(32, NULL, 'Garlic per 100g',          4,  15.00,  25.00, 50, 5, 'active', 1),
(33, NULL, 'Ginger per 100g',          4,  12.00,  20.00, 50, 5, 'active', 1),
(34, NULL, 'Sitaw (String Beans) per bundle', 4, 20.00, 35.00, 30, 5, 'active', 1),
(35, NULL, 'Kangkong per bundle',      4,  10.00,  18.00, 30, 5, 'active', 1),
(36, NULL, 'Pechay per bundle',        4,  12.00,  20.00, 30, 5, 'active', 1),
(37, NULL, 'Ampalaya (Bitter Gourd) per piece', 4, 15.00, 25.00, 25, 5, 'active', 1);
