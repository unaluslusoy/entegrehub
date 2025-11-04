-- User Custom Cargo Providers
-- Allows users to add their own cargo companies with custom API configurations

CREATE TABLE IF NOT EXISTS `user_cargo_providers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT 'Cargo company name',
    `code` VARCHAR(50) NOT NULL COMMENT 'Unique code for this provider',
    `logo_path` VARCHAR(255) NULL COMMENT 'Logo file path',
    `api_endpoint` VARCHAR(255) NULL COMMENT 'API base URL',
    `credentials` JSON NULL COMMENT 'API credentials (keys, secrets, customer codes)',
    `config_fields` JSON NULL COMMENT 'Dynamic form field definitions',
    `webhook_url` VARCHAR(255) NULL COMMENT 'Callback URL for status updates',
    `documentation_url` VARCHAR(255) NULL COMMENT 'API documentation link',
    `support_email` VARCHAR(100) NULL COMMENT 'Support contact email',
    `support_phone` VARCHAR(20) NULL COMMENT 'Support contact phone',
    `is_test_mode` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Test mode enabled',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Provider is active',
    `last_test_at` DATETIME NULL COMMENT 'Last API test time',
    `last_test_status` VARCHAR(20) NULL COMMENT 'success, failed',
    `last_test_message` TEXT NULL COMMENT 'Test result message',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_code_unique` (`user_id`, `code`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_is_active` (`is_active`),
    CONSTRAINT `fk_user_cargo_providers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User custom cargo provider configurations';

-- User Notification Configurations
-- SMS, WhatsApp, and Email settings for each user

CREATE TABLE IF NOT EXISTS `user_notification_configs` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL,
    
    -- SMS Configuration
    `sms_provider` VARCHAR(50) NULL COMMENT 'netgsm, iletimerkezi, twilio',
    `sms_credentials` JSON NULL COMMENT 'SMS API credentials',
    `sms_header` VARCHAR(20) NULL COMMENT 'Corporate SMS sender name',
    `sms_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `sms_test_mode` TINYINT(1) NOT NULL DEFAULT 0,
    
    -- WhatsApp Configuration
    `whatsapp_provider` VARCHAR(50) NULL COMMENT 'whatsapp_business_api, twilio, custom',
    `whatsapp_credentials` JSON NULL COMMENT 'WhatsApp API credentials',
    `whatsapp_number` VARCHAR(20) NULL COMMENT 'Business phone number',
    `whatsapp_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `whatsapp_test_mode` TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Email Configuration
    `email_credentials` JSON NULL COMMENT 'Custom SMTP settings',
    `use_custom_email` TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Notification Preferences
    `notification_settings` JSON NULL COMMENT 'Event notification preferences',
    
    `last_test_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id_unique` (`user_id`),
    CONSTRAINT `fk_user_notification_configs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User notification channel configurations';

-- User Notification Templates
-- Customizable message templates for different events and channels

CREATE TABLE IF NOT EXISTS `user_notification_templates` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `event_type` VARCHAR(50) NOT NULL COMMENT 'order_created, shipment_delivered, etc.',
    `channel` VARCHAR(50) NOT NULL COMMENT 'sms, whatsapp, email',
    `subject` VARCHAR(200) NULL COMMENT 'Email subject line',
    `body` TEXT NOT NULL COMMENT 'Message template with {{variables}}',
    `available_variables` JSON NULL COMMENT 'List of usable variables',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'System default template',
    `description` TEXT NULL COMMENT 'Template description',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_event_channel_unique` (`user_id`, `event_type`, `channel`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_channel` (`channel`),
    KEY `idx_is_active` (`is_active`),
    CONSTRAINT `fk_user_notification_templates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customizable notification message templates';

-- Insert default notification templates
INSERT INTO `user_notification_templates` (`user_id`, `event_type`, `channel`, `subject`, `body`, `is_default`, `description`) VALUES
(1, 'order_created', 'sms', NULL, 'Merhaba {{customer_name}}, {{order_number}} numaralı siparişiniz oluşturuldu. Toplam: {{order_total}} TL', 1, 'Sipariş oluşturulduğunda müşteriye SMS'),
(1, 'order_created', 'email', 'Siparişiniz Alındı - {{order_number}}', 'Sayın {{customer_name}},\n\n{{order_number}} numaralı siparişiniz başarıyla alındı.\n\nSipariş Tutarı: {{order_total}} TL\nÜrün Sayısı: {{order_items_count}}\n\nTeşekkür ederiz,\n{{company_name}}', 1, 'Sipariş oluşturulduğunda müşteriye e-posta'),
(1, 'shipment_created', 'sms', NULL, 'Merhaba {{customer_name}}, kargonuz {{cargo_company}} ile gönderildi. Takip No: {{tracking_number}}', 1, 'Kargo oluşturulduğunda müşteriye SMS'),
(1, 'shipment_delivered', 'sms', NULL, 'Merhaba {{customer_name}}, {{tracking_number}} takip numaralı kargonuz teslim edildi. Bizi tercih ettiğiniz için teşekkürler!', 1, 'Kargo teslim edildiğinde müşteriye SMS'),
(1, 'shipment_delivered', 'whatsapp', NULL, '🎉 Harika haber! {{tracking_number}} takip numaralı kargonuz {{delivered_date}} tarihinde teslim edildi.\n\n📦 Teslim Alan: {{receiver_name}}\n🚚 Kargo Firması: {{cargo_company}}\n\nBizi tercih ettiğiniz için teşekkür ederiz! 💚', 1, 'Kargo teslim edildiğinde müşteriye WhatsApp');
