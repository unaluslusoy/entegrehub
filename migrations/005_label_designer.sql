-- Migration: Label Designer Feature
-- Create user_label_templates table for custom label design system

CREATE TABLE IF NOT EXISTS `user_label_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `design_config` JSON NOT NULL COMMENT 'JSON configuration for label design elements',
    `width` DECIMAL(6,2) NOT NULL DEFAULT 100.00 COMMENT 'Template width in millimeters',
    `height` DECIMAL(6,2) NOT NULL DEFAULT 150.00 COMMENT 'Template height in millimeters',
    `orientation` VARCHAR(20) NOT NULL DEFAULT 'portrait' COMMENT 'portrait or landscape',
    `preview_image` VARCHAR(255) NULL COMMENT 'Path to preview image',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `is_default` BOOLEAN NOT NULL DEFAULT FALSE,
    `category` VARCHAR(50) NULL DEFAULT 'custom' COMMENT 'Template category: custom, thermal, a4, etc',
    `usage_count` INT NOT NULL DEFAULT 0 COMMENT 'Number of times template has been used',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_used_at` DATETIME NULL,

    CONSTRAINT `fk_label_template_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,

    INDEX `idx_user_active` (`user_id`, `is_active`),
    INDEX `idx_user_default` (`user_id`, `is_default`),
    INDEX `idx_category` (`category`),
    INDEX `idx_usage_count` (`usage_count` DESC),
    INDEX `idx_created_at` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data: Create a default template for testing
-- Note: This will only insert if there's a user with id=1
INSERT INTO `user_label_templates`
    (`user_id`, `name`, `description`, `design_config`, `width`, `height`, `orientation`, `category`, `is_default`)
SELECT
    1,
    'Varsayılan Etiket',
    'Sistem tarafından oluşturulan örnek etiket şablonu',
    JSON_OBJECT(
        'elements', JSON_ARRAY(
            JSON_OBJECT(
                'type', 'text',
                'x', 10,
                'y', 10,
                'width', 200,
                'height', 30,
                'content', 'Takip No',
                'fieldKey', 'tracking',
                'fontSize', 18,
                'fontFamily', 'Arial',
                'fontWeight', 'bold',
                'textAlign', 'left',
                'color', '#000000'
            ),
            JSON_OBJECT(
                'type', 'qrcode',
                'x', 150,
                'y', 50,
                'width', 100,
                'height', 100
            ),
            JSON_OBJECT(
                'type', 'text',
                'x', 10,
                'y', 50,
                'width', 130,
                'height', 80,
                'content', 'Alıcı',
                'fieldKey', 'receiver_name',
                'fontSize', 12,
                'fontFamily', 'Arial',
                'fontWeight', 'normal',
                'textAlign', 'left',
                'color', '#000000'
            )
        ),
        'settings', JSON_OBJECT(
            'backgroundColor', '#ffffff',
            'gridSize', 5,
            'showGrid', true
        )
    ),
    100,
    150,
    'portrait',
    'custom',
    TRUE
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `users` WHERE `id` = 1)
LIMIT 1;

-- Add comments for better documentation
ALTER TABLE `user_label_templates`
    COMMENT = 'Stores user-created custom label templates for cargo shipping labels';
