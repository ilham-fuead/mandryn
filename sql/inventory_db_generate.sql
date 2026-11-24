-- ============================================================================
-- inventory_db : generation script
-- Part of the mandryn examples (mandryn/mandryn)
--
-- Creates the database and the three tables used by the examples in
-- examples/inventory/. Safe to re-run (IF NOT EXISTS everywhere).
--
-- Usage:
--   mysql -h 127.0.0.1 -u root < sql/inventory_db_generate.sql
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `inventory_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `inventory_db`;

-- ----------------------------------------------------------------------------
-- categories
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id`   int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_code` varchar(20)  NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at`    timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- warehouses
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouses` (
  `warehouse_id`   int(10) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_code` varchar(20)  NOT NULL,
  `warehouse_name` varchar(100) NOT NULL,
  `city`           varchar(100) NOT NULL,
  `country`        varchar(100) NOT NULL,
  `created_at`     timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`warehouse_id`),
  UNIQUE KEY `warehouse_code` (`warehouse_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- inventory_items
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `item_id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku`               varchar(32)  NOT NULL,
  `item_name`         varchar(150) NOT NULL,
  `category_id`       int(10) unsigned NOT NULL,
  `warehouse_id`      int(10) unsigned NOT NULL,
  `quantity_on_hand`  int(11) NOT NULL DEFAULT 0,
  `reorder_level`     int(11) NOT NULL DEFAULT 10,
  `unit_cost`         decimal(10,2) NOT NULL,
  `unit_price`        decimal(10,2) NOT NULL,
  `status`            enum('ACTIVE','LOW_STOCK','OUT_OF_STOCK','DISCONTINUED') NOT NULL DEFAULT 'ACTIVE',
  `last_restocked_at` datetime DEFAULT NULL,
  `updated_at`        timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_items_status` (`status`),
  KEY `idx_items_category` (`category_id`),
  KEY `idx_items_warehouse` (`warehouse_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_items_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
