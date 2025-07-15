CREATE DATABASE IF NOT EXISTS `shop`;

USE `shop`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET time_zone = "+00:00";

START TRANSACTION;

-- ----------------------------
-- Table: catageories
-- ----------------------------
CREATE TABLE IF NOT EXISTS `catageories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: ads
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ads` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `categoryid` INT(11) NOT NULL,
    `photo` VARCHAR(255) NOT NULL,
    `linkaddress` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: cart
-- ----------------------------
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `userid` INT(11) NOT NULL,
    `prouductid` INT(11) NOT NULL,
    `qty` INT(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: products
-- ----------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10, 2),
    `discount` DECIMAL(5, 2),
    `total_final_price` DECIMAL(10, 2),
    `category_id` INT(11),
    `img` VARCHAR(255),
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: users
-- ----------------------------
-- Table: users
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
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: orders1
-- ----------------------------
CREATE TABLE IF NOT EXISTS `orders1` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `phoneone` VARCHAR(20) NOT NULL,
    `phonetwo` VARCHAR(20),
    `city` VARCHAR(100),
    `address` TEXT NOT NULL,
    `htmltage` TEXT,
    `orderstate` VARCHAR(50) DEFAULT 'inprogress',
    `data` DATE,
    `numberofproducts` INT(11),
    `finaltotalprice` DECIMAL(10, 2),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES users (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: order_items
-- ----------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `qty` INT(11) NOT NULL DEFAULT 1,
    `price` DECIMAL(10, 2) NOT NULL,
    `total_price` DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`order_id`) REFERENCES orders1 (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES products (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: usersadmin
-- ----------------------------
CREATE TABLE IF NOT EXISTS `usersadmin` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- حساب أدمن افتراضي
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
    );

-- ----------------------------
-- Table: site_visits
-- ----------------------------
CREATE TABLE IF NOT EXISTS `site_visits` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(50) NOT NULL,
    `country` VARCHAR(100),
    `visit_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: notifications
-- ----------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES users (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: coupons
-- ----------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL,
    `discount_value` DECIMAL(10, 2) NOT NULL,
    `max_uses` INT DEFAULT 1,
    `expires_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ----------------------------
-- Table: order_status_logs
-- ----------------------------
CREATE TABLE IF NOT EXISTS `order_status_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `note` TEXT,
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`order_id`) REFERENCES orders1 (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

COMMIT;