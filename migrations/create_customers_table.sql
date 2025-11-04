-- Customers table creation
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `current_plan_id` INT DEFAULT NULL,
  `owner_user_id` INT DEFAULT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `tax_office` VARCHAR(255) DEFAULT NULL,
  `tax_number` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `district` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(10) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'TR',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `subscription_start_date` DATETIME DEFAULT NULL,
  `subscription_end_date` DATETIME DEFAULT NULL,
  `settings` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  UNIQUE INDEX `UNIQ_62534E21E7927C74` (`email`),
  INDEX `IDX_62534E214294871E` (`current_plan_id`),
  UNIQUE INDEX `UNIQ_62534E212B18554A` (`owner_user_id`),
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign keys (if tables exist)
-- ALTER TABLE `customers` ADD CONSTRAINT `FK_62534E214294871E` FOREIGN KEY (`current_plan_id`) REFERENCES `subscription_plans` (`id`);
-- ALTER TABLE `customers` ADD CONSTRAINT `FK_62534E212B18554A` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`);
