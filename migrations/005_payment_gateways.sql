-- Ödeme Gateway'leri Master Tablosu (Iyzico, PayTR, Stripe, vb)
CREATE TABLE IF NOT EXISTS payment_gateways (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Gateway adı (Iyzico, PayTR, Stripe)',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique code (iyzico, paytr, stripe)',
    logo VARCHAR(255) DEFAULT NULL COMMENT 'Logo dosya yolu',
    description TEXT DEFAULT NULL COMMENT 'Gateway açıklaması',
    provider_type VARCHAR(50) NOT NULL COMMENT 'domestic, international',
    supported_currencies JSON DEFAULT NULL COMMENT '["TRY", "USD", "EUR"]',
    api_base_url VARCHAR(255) DEFAULT NULL COMMENT 'Base API URL',
    api_documentation_url VARCHAR(255) DEFAULT NULL COMMENT 'API dokümantasyon',
    config_fields JSON DEFAULT NULL COMMENT 'Config fields: [{name, label, type, required}]',
    features JSON DEFAULT NULL COMMENT 'Features: {3d_secure, installment, recurring}',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Admin tarafından aktif edildi mi',
    supports_test_mode TINYINT(1) DEFAULT 1 COMMENT 'Test modu destekliyor mu',
    supports_installment TINYINT(1) DEFAULT 0 COMMENT 'Taksit destekliyor mu',
    supports_3d_secure TINYINT(1) DEFAULT 1 COMMENT '3D Secure destekliyor mu',
    commission_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Varsayılan komisyon oranı %',
    priority INT DEFAULT 0 COMMENT 'Sıralama',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    INDEX idx_provider_type (provider_type),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ödeme gateway master tablosu';

-- Kullanıcı Payment Gateway Yapılandırmaları
CREATE TABLE IF NOT EXISTS payment_gateway_configs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Kullanıcı ID',
    gateway_id INT UNSIGNED NOT NULL COMMENT 'Gateway ID',
    credentials JSON NOT NULL COMMENT 'API credentials',
    webhook_secret VARCHAR(255) DEFAULT NULL COMMENT 'Webhook secret key',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Kullanıcı için aktif mi',
    is_test_mode TINYINT(1) DEFAULT 1 COMMENT 'Test modu aktif mi',
    is_default TINYINT(1) DEFAULT 0 COMMENT 'Varsayılan ödeme yöntemi mi',
    custom_commission_rate DECIMAL(5,2) DEFAULT NULL COMMENT 'Özel komisyon oranı (null=varsayılan)',
    installment_enabled TINYINT(1) DEFAULT 0 COMMENT 'Taksit aktif mi',
    max_installment INT DEFAULT 1 COMMENT 'Max taksit sayısı',
    min_installment_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Min taksit tutarı',
    enable_3d_secure TINYINT(1) DEFAULT 1 COMMENT '3D Secure zorunlu mu',
    test_connection_status VARCHAR(50) DEFAULT NULL COMMENT 'success, failed, pending',
    test_connection_message TEXT DEFAULT NULL COMMENT 'Test mesajı',
    last_test_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Son test',
    total_transactions INT DEFAULT 0 COMMENT 'Toplam işlem sayısı',
    total_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Toplam işlem tutarı',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_gateway (user_id, gateway_id),
    INDEX idx_user_id (user_id),
    INDEX idx_gateway_id (gateway_id),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kullanıcı payment gateway configs';

-- Payment Transactions (Ödeme işlemleri)
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Kullanıcı ID',
    gateway_config_id INT UNSIGNED NOT NULL COMMENT 'Gateway config ID',
    invoice_id INT UNSIGNED DEFAULT NULL COMMENT 'İlişkili fatura ID',
    transaction_id VARCHAR(255) NOT NULL COMMENT 'Gateway transaction ID',
    payment_id VARCHAR(255) DEFAULT NULL COMMENT 'Gateway payment ID',
    conversation_id VARCHAR(255) DEFAULT NULL COMMENT 'Conversation/basket ID',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Tutar',
    currency VARCHAR(10) DEFAULT 'TRY' COMMENT 'Para birimi',
    status VARCHAR(50) NOT NULL COMMENT 'pending, success, failed, refunded',
    payment_method VARCHAR(50) DEFAULT NULL COMMENT 'credit_card, debit_card, bank_transfer',
    card_last_4 VARCHAR(4) DEFAULT NULL COMMENT 'Kart son 4 hanesi',
    card_brand VARCHAR(50) DEFAULT NULL COMMENT 'Visa, Mastercard, etc',
    installment INT DEFAULT 1 COMMENT 'Taksit sayısı',
    installment_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Taksit tutarı',
    commission_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Komisyon tutarı',
    net_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Net tutar',
    is_3d_secure TINYINT(1) DEFAULT 0 COMMENT '3D Secure ile mi ödendi',
    error_code VARCHAR(100) DEFAULT NULL COMMENT 'Hata kodu',
    error_message TEXT DEFAULT NULL COMMENT 'Hata mesajı',
    gateway_response JSON DEFAULT NULL COMMENT 'Gateway raw response',
    ip_address VARCHAR(50) DEFAULT NULL COMMENT 'IP adresi',
    user_agent TEXT DEFAULT NULL COMMENT 'User agent',
    paid_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Ödeme zamanı',
    refunded_at TIMESTAMP NULL DEFAULT NULL COMMENT 'İade zamanı',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (gateway_config_id) REFERENCES payment_gateway_configs(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_gateway_config_id (gateway_config_id),
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_status (status),
    INDEX idx_paid_at (paid_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ödeme işlemleri';

-- Seed Data: Popüler Payment Gateway'ler
INSERT INTO payment_gateways (name, code, description, provider_type, supported_currencies, api_base_url, config_fields, features, is_active, supports_installment, priority) VALUES
('Iyzico', 'iyzico', 'Türkiye\'nin lider ödeme altyapısı', 'domestic', '["TRY", "USD", "EUR", "GBP"]', 'https://api.iyzipay.com',
'[
    {"name": "api_key", "label": "API Key", "type": "text", "required": true, "placeholder": "Iyzico API Key"},
    {"name": "secret_key", "label": "Secret Key", "type": "password", "required": true, "placeholder": "Secret Key"}
]',
'{"3d_secure": true, "installment": true, "recurring": true, "refund": true}',
1, 1, 10),

('PayTR', 'paytr', 'PayTR ödeme sistemi', 'domestic', '["TRY"]', 'https://www.paytr.com/odeme/api',
'[
    {"name": "merchant_id", "label": "Merchant ID", "type": "text", "required": true, "placeholder": "Merchant ID"},
    {"name": "merchant_key", "label": "Merchant Key", "type": "password", "required": true, "placeholder": "Merchant Key"},
    {"name": "merchant_salt", "label": "Merchant Salt", "type": "password", "required": true, "placeholder": "Salt"}
]',
'{"3d_secure": true, "installment": true, "recurring": false, "refund": true}',
1, 1, 9),

('Stripe', 'stripe', 'Global ödeme altyapısı', 'international', '["USD", "EUR", "GBP", "TRY"]', 'https://api.stripe.com',
'[
    {"name": "publishable_key", "label": "Publishable Key", "type": "text", "required": true, "placeholder": "pk_live_..."},
    {"name": "secret_key", "label": "Secret Key", "type": "password", "required": true, "placeholder": "sk_live_..."},
    {"name": "webhook_secret", "label": "Webhook Secret", "type": "password", "required": false, "placeholder": "whsec_..."}
]',
'{"3d_secure": true, "installment": false, "recurring": true, "refund": true}',
1, 0, 8),

('PayU', 'payu', 'PayU Türkiye', 'domestic', '["TRY"]', 'https://secure.payu.com.tr',
'[
    {"name": "merchant", "label": "Merchant", "type": "text", "required": true, "placeholder": "Merchant Code"},
    {"name": "secret_key", "label": "Secret Key", "type": "password", "required": true, "placeholder": "Secret Key"}
]',
'{"3d_secure": true, "installment": true, "recurring": false, "refund": true}',
1, 1, 7);
