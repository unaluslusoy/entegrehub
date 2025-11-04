-- ============================================
-- EntegreHub Kargo SaaS System
-- Database Migration
-- Created: 2025-10-31
-- ============================================

-- Set charset
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(180) NOT NULL,
  `roles` JSON NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_2fa_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `two_factor_secret` VARCHAR(255) NULL,
  `last_login_at` DATETIME NULL,
  `locale` VARCHAR(10) NOT NULL DEFAULT 'tr',
  `preferences` JSON NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SUBSCRIPTION PLANS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `monthly_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `yearly_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_orders` INT NOT NULL DEFAULT 50,
  `max_shops` INT NOT NULL DEFAULT 1,
  `max_users` INT NOT NULL DEFAULT 1,
  `max_sms_per_month` INT NULL,
  `max_email_per_month` INT NULL,
  `has_api_access` TINYINT(1) NOT NULL DEFAULT 0,
  `has_advanced_reports` TINYINT(1) NOT NULL DEFAULT 0,
  `has_barcode_scanner` TINYINT(1) NOT NULL DEFAULT 0,
  `has_ai_features` TINYINT(1) NOT NULL DEFAULT 0,
  `has_white_label` TINYINT(1) NOT NULL DEFAULT 0,
  `has_priority_support` TINYINT(1) NOT NULL DEFAULT 0,
  `has_custom_domain` TINYINT(1) NOT NULL DEFAULT 0,
  `features` JSON NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_94AE4AAA77153098` (`code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER SUBSCRIPTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `billing_period` VARCHAR(20) NOT NULL DEFAULT 'monthly',
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `next_billing_date` DATETIME NULL,
  `cancelled_at` DATETIME NULL,
  `suspended_at` DATETIME NULL,
  `cancellation_reason` TEXT NULL,
  `current_month_orders` INT NOT NULL DEFAULT 0,
  `current_month_sms` INT NOT NULL DEFAULT 0,
  `current_month_emails` INT NOT NULL DEFAULT 0,
  `payment_method` VARCHAR(100) NULL,
  `payment_gateway_id` VARCHAR(255) NULL,
  `subscription_gateway_id` VARCHAR(255) NULL,
  `auto_renew` TINYINT(1) NOT NULL DEFAULT 1,
  `last_payment_date` DATETIME NULL,
  `last_payment_amount` DECIMAL(10,2) NULL,
  `is_trial_period` TINYINT(1) NOT NULL DEFAULT 0,
  `trial_start_date` DATETIME NULL,
  `trial_end_date` DATETIME NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_end_date` (`end_date`),
  CONSTRAINT `FK_552B0EA9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_552B0EA9E899029B` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SHOPS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `shops` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `shop_domain` VARCHAR(255) NOT NULL,
  `shop_name` VARCHAR(255) NOT NULL,
  `access_token` TEXT NOT NULL,
  `shopify_id` VARCHAR(100) NOT NULL,
  `scopes` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_sync` TINYINT(1) NOT NULL DEFAULT 1,
  `webhooks` JSON NULL,
  `settings` JSON NULL,
  `last_sync_at` DATETIME NULL,
  `installed_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_788D7ABAE77C4C0E` (`shop_domain`),
  KEY `idx_user` (`user_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `FK_788D7ABAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shop_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(100) NOT NULL,
  `shopify_order_id` VARCHAR(100) NULL,
  `shopify_order_number` VARCHAR(100) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'online',
  `payment_status` VARCHAR(50) NULL DEFAULT 'pending',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `shipping_amount` DECIMAL(10,2) NULL,
  `tax_amount` DECIMAL(10,2) NULL,
  `discount_amount` DECIMAL(10,2) NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_email` VARCHAR(255) NULL,
  `customer_phone` VARCHAR(20) NULL,
  `customer_note` TEXT NULL,
  `internal_note` TEXT NULL,
  `tags` JSON NULL,
  `total_weight` DECIMAL(10,2) NULL,
  `item_count` INT NULL,
  `is_gift` TINYINT(1) NOT NULL DEFAULT 0,
  `requires_invoice` TINYINT(1) NOT NULL DEFAULT 0,
  `raw_data` JSON NULL,
  `order_date` DATETIME NULL,
  `processed_at` DATETIME NULL,
  `shipped_at` DATETIME NULL,
  `delivered_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_E52FFDEE551F0F81` (`order_number`),
  KEY `idx_shop` (`shop_id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_order_date` (`order_date`),
  KEY `idx_customer_email` (`customer_email`),
  CONSTRAINT `FK_E52FFDEE4D16C4DD` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDER ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `sku` VARCHAR(100) NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `variant_name` VARCHAR(255) NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `weight` DECIMAL(10,2) NULL,
  `image_url` VARCHAR(255) NULL,
  `barcode` VARCHAR(100) NULL,
  `properties` JSON NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_sku` (`sku`),
  KEY `idx_barcode` (`barcode`),
  CONSTRAINT `FK_62809DB08D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ADDRESSES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NULL,
  `order_billing_id` INT UNSIGNED NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'shipping',
  `full_name` VARCHAR(255) NOT NULL,
  `company` VARCHAR(255) NULL,
  `address1` TEXT NOT NULL,
  `address2` TEXT NULL,
  `city` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NULL,
  `province` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NULL,
  `country` VARCHAR(2) NOT NULL DEFAULT 'TR',
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(255) NULL,
  `identity_number` VARCHAR(20) NULL,
  `tax_number` VARCHAR(20) NULL,
  `tax_office` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6FCA75168D9F6D38` (`order_id`),
  UNIQUE KEY `UNIQ_6FCA7516C8AF2861` (`order_billing_id`),
  KEY `idx_city` (`city`),
  CONSTRAINT `FK_6FCA75168D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6FCA7516C8AF2861` FOREIGN KEY (`order_billing_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CARGO COMPANIES TABLE (System-wide)
-- ============================================
CREATE TABLE IF NOT EXISTS `cargo_companies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `logo` VARCHAR(255) NULL,
  `api_url` TEXT NULL,
  `tracking_url` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `credentials` JSON NULL,
  `settings` JSON NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `base_cost` DECIMAL(10,2) NULL,
  `cost_per_kg` DECIMAL(5,2) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D4E5E9F777153098` (`code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER CARGO COMPANIES TABLE (User-specific settings)
-- ============================================
CREATE TABLE IF NOT EXISTS `user_cargo_companies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `cargo_company_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `priority` INT NOT NULL DEFAULT 0,
  `api_username` TEXT NOT NULL COMMENT 'Encrypted',
  `api_password` TEXT NOT NULL COMMENT 'Encrypted',
  `customer_id` TEXT NULL COMMENT 'Encrypted',
  `additional_credentials` JSON NULL COMMENT 'Encrypted',
  `service_settings` JSON NULL,
  `negotiated_base_cost` DECIMAL(10,2) NULL,
  `negotiated_cost_per_kg` DECIMAL(5,2) NULL,
  `contract_number` TEXT NULL,
  `contract_start_date` DATE NULL,
  `contract_end_date` DATE NULL,
  `notes` TEXT NULL,
  `last_tested_at` DATETIME NULL,
  `is_test_successful` TINYINT(1) NOT NULL DEFAULT 0,
  `last_test_error` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_cargo` (`cargo_company_id`),
  KEY `idx_active` (`is_active`),
  UNIQUE KEY `uniq_user_cargo` (`user_id`, `cargo_company_id`),
  CONSTRAINT `FK_UCC_USER` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_UCC_CARGO` FOREIGN KEY (`cargo_company_id`) REFERENCES `cargo_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SHIPMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `shipments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `cargo_company_id` INT UNSIGNED NOT NULL,
  `tracking_number` VARCHAR(100) NOT NULL,
  `cargo_key` VARCHAR(100) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'created',
  `estimated_cost` DECIMAL(10,2) NULL,
  `actual_cost` DECIMAL(10,2) NULL,
  `weight` DECIMAL(10,2) NULL,
  `desi` DECIMAL(10,2) NULL,
  `package_count` INT NULL DEFAULT 1,
  `service_type` VARCHAR(50) NULL DEFAULT 'standard',
  `requires_signature` TINYINT(1) NOT NULL DEFAULT 0,
  `is_cod` TINYINT(1) NOT NULL DEFAULT 0,
  `cod_amount` DECIMAL(10,2) NULL,
  `label_url` TEXT NULL,
  `barcode_url` TEXT NULL,
  `tracking_history` JSON NULL,
  `api_response` JSON NULL,
  `notes` TEXT NULL,
  `cancel_reason` TEXT NULL,
  `estimated_delivery_date` DATETIME NULL,
  `picked_up_at` DATETIME NULL,
  `delivered_at` DATETIME NULL,
  `cancelled_at` DATETIME NULL,
  `last_tracked_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2CB403A7C7EDF2D4` (`tracking_number`),
  KEY `idx_order` (`order_id`),
  KEY `idx_cargo` (`cargo_company_id`),
  KEY `idx_tracking_number` (`tracking_number`),
  KEY `idx_shipment_status` (`status`),
  CONSTRAINT `FK_2CB403A78D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2CB403A7D32479E7` FOREIGN KEY (`cargo_company_id`) REFERENCES `cargo_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER NOTIFICATION SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `user_notification_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `channel` VARCHAR(50) NOT NULL DEFAULT 'sms',
  `provider` VARCHAR(50) NOT NULL DEFAULT 'netgsm',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `api_username` TEXT NULL COMMENT 'Encrypted',
  `api_password` TEXT NULL COMMENT 'Encrypted',
  `api_key` TEXT NULL COMMENT 'Encrypted',
  `sms_header` VARCHAR(20) NULL,
  `smtp_host` TEXT NULL,
  `smtp_port` INT NULL,
  `smtp_username` TEXT NULL COMMENT 'Encrypted',
  `smtp_password` TEXT NULL COMMENT 'Encrypted',
  `smtp_encryption` VARCHAR(10) NULL DEFAULT 'tls',
  `from_email` VARCHAR(255) NULL,
  `from_name` VARCHAR(255) NULL,
  `whatsapp_business_id` TEXT NULL COMMENT 'Encrypted',
  `whatsapp_access_token` TEXT NULL COMMENT 'Encrypted',
  `whatsapp_phone_number` VARCHAR(20) NULL,
  `notification_preferences` JSON NOT NULL,
  `send_to_customer` TINYINT(1) NOT NULL DEFAULT 1,
  `send_to_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `message_templates` JSON NULL,
  `monthly_quota` INT NULL,
  `monthly_usage` INT NOT NULL DEFAULT 0,
  `cost_per_message` DECIMAL(10,4) NULL,
  `notes` TEXT NULL,
  `last_tested_at` DATETIME NULL,
  `is_test_successful` TINYINT(1) NOT NULL DEFAULT 0,
  `last_test_error` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_channel` (`channel`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `FK_UNS_USER` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- END OF MIGRATION
-- ============================================
