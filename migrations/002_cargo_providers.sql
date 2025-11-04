-- Kargo Firmaları (Admin tarafından yönetilen kargo şirketleri)
CREATE TABLE IF NOT EXISTS cargo_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Kargo firma adı (Aras Kargo, MNG, vb)',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique identifier (aras, mng, yurtici)',
    logo VARCHAR(255) DEFAULT NULL COMMENT 'Logo dosya yolu',
    description TEXT DEFAULT NULL COMMENT 'Kargo firması hakkında açıklama',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Admin tarafından aktif edildi mi',
    api_endpoint VARCHAR(255) DEFAULT NULL COMMENT 'Base API URL',
    webhook_url VARCHAR(255) DEFAULT NULL COMMENT 'Webhook callback URL pattern',
    config_fields JSON DEFAULT NULL COMMENT 'Required config fields: [{name, label, type, required, placeholder}]',
    api_documentation_url VARCHAR(255) DEFAULT NULL COMMENT 'API dokümantasyon linki',
    test_mode_available TINYINT(1) DEFAULT 1 COMMENT 'Test modu destekliyor mu',
    priority INT DEFAULT 0 COMMENT 'Sıralama önceliği',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kargo firmaları master listesi';

-- Kullanıcı Kargo Entegrasyonları (Her user kendi credentials'ını girer)
CREATE TABLE IF NOT EXISTS cargo_provider_configs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Kullanıcı ID',
    provider_id INT UNSIGNED NOT NULL COMMENT 'Kargo firması ID',
    credentials JSON NOT NULL COMMENT 'API credentials: {api_key, secret_key, customer_code, etc}',
    webhook_secret VARCHAR(255) DEFAULT NULL COMMENT 'Webhook doğrulama için secret key',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Kullanıcı bu kargo firmasını aktif etti mi',
    is_test_mode TINYINT(1) DEFAULT 1 COMMENT 'Test modu aktif mi',
    test_connection_status VARCHAR(50) DEFAULT NULL COMMENT 'success, failed, pending',
    test_connection_message TEXT DEFAULT NULL COMMENT 'Test sonuç mesajı',
    last_test_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Son test zamanı',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES cargo_providers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_provider (user_id, provider_id),
    INDEX idx_user_id (user_id),
    INDEX idx_provider_id (provider_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kullanıcıların kargo entegrasyonları';

-- Seed Data: Popüler Kargo Firmaları
INSERT INTO cargo_providers (name, code, description, is_active, api_endpoint, config_fields, priority) VALUES
('Aras Kargo', 'aras', 'Türkiye\'nin en büyük kargo şirketlerinden biri', 1, 'https://api.araskargo.com.tr/v1', 
'[
    {"name": "customer_code", "label": "Müşteri Kodu", "type": "text", "required": true, "placeholder": "Aras\'tan aldığınız müşteri kodu"},
    {"name": "api_key", "label": "API Anahtarı", "type": "text", "required": true, "placeholder": "API Key"},
    {"name": "api_secret", "label": "API Secret", "type": "password", "required": true, "placeholder": "API Secret Key"},
    {"name": "username", "label": "Kullanıcı Adı", "type": "text", "required": true, "placeholder": "Web servis kullanıcı adı"}
]', 10),

('MNG Kargo', 'mng', 'Hızlı ve güvenilir kargo hizmeti', 1, 'https://service.mngkargo.com.tr/mngapi', 
'[
    {"name": "api_key", "label": "API Key", "type": "text", "required": true, "placeholder": "MNG API Key"},
    {"name": "customer_number", "label": "Müşteri Numarası", "type": "text", "required": true, "placeholder": "Müşteri numaranız"},
    {"name": "password", "label": "Şifre", "type": "password", "required": true, "placeholder": "API Şifreniz"}
]', 9),

('Yurtiçi Kargo', 'yurtici', 'Yurtiçi Kargo web servisleri', 1, 'https://ws.yurticikargo.com/shipmentapi', 
'[
    {"name": "user_code", "label": "Kullanıcı Kodu", "type": "text", "required": true, "placeholder": "Yurtiçi kullanıcı kodu"},
    {"name": "password", "label": "Şifre", "type": "password", "required": true, "placeholder": "Web servis şifresi"},
    {"name": "customer_code", "label": "Müşteri Kodu", "type": "text", "required": true, "placeholder": "Müşteri kodu"}
]', 8),

('Sürat Kargo', 'surat', 'Sürat Kargo entegrasyonu', 1, 'https://api.suratkargo.com.tr', 
'[
    {"name": "customer_id", "label": "Müşteri ID", "type": "text", "required": true, "placeholder": "Müşteri ID"},
    {"name": "api_key", "label": "API Key", "type": "text", "required": true, "placeholder": "API anahtarı"},
    {"name": "api_secret", "label": "Secret Key", "type": "password", "required": true, "placeholder": "Secret key"}
]', 7),

('PTT Kargo', 'ptt', 'PTT Kargo entegrasyonu', 1, 'https://kargotakip.ptt.gov.tr/ws', 
'[
    {"name": "username", "label": "Kullanıcı Adı", "type": "text", "required": true, "placeholder": "PTT kullanıcı adı"},
    {"name": "password", "label": "Şifre", "type": "password", "required": true, "placeholder": "PTT şifre"},
    {"name": "customer_code", "label": "Müşteri Kodu", "type": "text", "required": true, "placeholder": "Müşteri kodu"}
]', 6),

('UPS Kargo', 'ups', 'UPS uluslararası kargo', 0, 'https://onlinetools.ups.com/api', 
'[
    {"name": "access_key", "label": "Access Key", "type": "text", "required": true, "placeholder": "UPS Access Key"},
    {"name": "username", "label": "Username", "type": "text", "required": true, "placeholder": "UPS Username"},
    {"name": "password", "label": "Password", "type": "password", "required": true, "placeholder": "UPS Password"},
    {"name": "account_number", "label": "Account Number", "type": "text", "required": true, "placeholder": "Shipper Account Number"}
]', 5),

('DHL Express', 'dhl', 'DHL Express uluslararası kargo', 0, 'https://api.dhl.com/express/v1', 
'[
    {"name": "api_key", "label": "API Key", "type": "text", "required": true, "placeholder": "DHL API Key"},
    {"name": "api_secret", "label": "API Secret", "type": "password", "required": true, "placeholder": "DHL API Secret"},
    {"name": "account_number", "label": "Account Number", "type": "text", "required": true, "placeholder": "DHL Account Number"}
]', 4);
