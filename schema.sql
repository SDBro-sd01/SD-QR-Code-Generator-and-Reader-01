-- =====================================================================
--  QR Studio — MySQL Schema
--  Database: MYQRCODEBASE_01
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';

CREATE DATABASE IF NOT EXISTS `MYQRCODEBASE_01`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `MYQRCODEBASE_01`;

-- ---------------------------------------------------------------------
--  FIELD DEFINITIONS
--  Stores every custom input field created via the Field Builder.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_field_definitions` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `field_key`     VARCHAR(64)  NOT NULL UNIQUE,
  `label`         VARCHAR(150) NOT NULL,
  `field_type`    ENUM('text','textarea','number','email','tel','date','select','locked')
                  NOT NULL DEFAULT 'text',
  `placeholder`   VARCHAR(200) DEFAULT NULL,
  `options_json`  TEXT         DEFAULT NULL,
  `auto_format`   VARCHAR(200) DEFAULT NULL,
  `is_required`   TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`    INT          NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  RECORDS
--  Every generated QR record. `record_data` holds a JSON map of
--  {label: value} used as the QR payload.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_records` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `record_code`   VARCHAR(120) DEFAULT NULL,
  `full_name`     VARCHAR(220) NOT NULL,
  `record_data`   LONGTEXT     NOT NULL,
  `qr_image`      VARCHAR(255) DEFAULT NULL,
  `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_full_name` (`full_name`),
  INDEX `idx_created`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  SEED — default fields
-- ---------------------------------------------------------------------
INSERT INTO `qr_field_definitions`
  (`field_key`, `label`, `field_type`, `placeholder`, `is_required`, `sort_order`)
VALUES
  ('full_name',          'Full Name',           'text',     'e.g. Kasun Perera Silva', 1, 1),
  ('name_with_initials', 'Name with Initials', 'text',     'e.g. K. P. Silva',        1, 2),
  ('age',                'Age',                 'number',   'e.g. 25',                 1, 3),
  ('address',            'Address',             'textarea', 'e.g. No. 24, Colombo 05', 1, 4)
ON DUPLICATE KEY UPDATE `field_key` = `field_key`;