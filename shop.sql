CREATE DATABASE IF NOT EXISTS `shop`;

USE `shop`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET time_zone = "+00:00";

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `parent_id` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `ads` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `categoryid` INT(11) NOT NULL,
    `photo` VARCHAR(255) NOT NULL,
    `linkaddress` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`categoryid`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20),
    `address` TEXT,
    `city` VARCHAR(100),
    `country` VARCHAR(100),
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `reset_token` VARCHAR(255) DEFAULT NULL,
    `reset_token_expiry` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE,
    `brand` VARCHAR(255),
    `description` TEXT,
    `tags` TEXT,
    `price` DECIMAL(10, 2) NOT NULL,
    `sale_price` DECIMAL(10, 2),
    `discount_percent` INT DEFAULT 0,
    `quantity` INT DEFAULT 0,
    `stock_status` ENUM(
        'in_stock',
        'out_of_stock',
        'pre_order'
    ) DEFAULT 'in_stock',
    `is_new` TINYINT(1) DEFAULT 0,
    `on_sale` TINYINT(1) DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `rating` DECIMAL(2, 1) DEFAULT 0.0,
    `views` INT DEFAULT 0,
    `image` VARCHAR(255),
    `barcode` VARCHAR(50),
    `expiry_date` DATE,
    `category_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `userid` INT(11) NOT NULL,
    `productid` INT(11) NOT NULL,
    `qty` INT(11) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`productid`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL,
    `discount_value` DECIMAL(10, 2) NOT NULL,
    `max_uses` INT DEFAULT 1,
    `expires_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ✅ الجدول الجديد لخصومات الكوبونات
CREATE TABLE IF NOT EXISTS `discount_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `coupon_code` VARCHAR(50) NOT NULL,
    `discount_amount` DECIMAL(10, 2) NOT NULL,
    `final_price` DECIMAL(10, 2) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `phoneone` VARCHAR(20) NOT NULL,
    `phonetwo` VARCHAR(20),
    `city` VARCHAR(100),
    `address` TEXT NOT NULL,
    `html_tag` TEXT,
    `orderstate` VARCHAR(50) DEFAULT 'inprogress',
    `date` DATE,
    `numberofproducts` INT(11),
    `finaltotalprice` DECIMAL(10, 2),
    `coupon_id` INT(11) DEFAULT NULL,
    `coupon_code` VARCHAR(50) DEFAULT NULL,
    `discount_type` ENUM('percentage', 'fixed') DEFAULT NULL,
    `discount_value` DECIMAL(10, 2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `qty` INT(11) NOT NULL DEFAULT 1,
    `price` DECIMAL(10, 2) NOT NULL,
    `total_price` DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `usersadmin` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO
    `usersadmin` (
        `id`,
        `name`,
        `email`,
        `password`
    )
VALUES (
        1,
        'Ammar',
        'ammar132004@gmail.com',
        '$2y$10$dJ.XyZXD9pJZCKKsmYBC..udbBAmp/9NHM1NNUDBM1vQZkoovTp1K'
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

CREATE TABLE IF NOT EXISTS `site_visits` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(50) NOT NULL,
    `country` VARCHAR(100),
    `visit_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `order_status_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `note` TEXT,
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

COMMIT;