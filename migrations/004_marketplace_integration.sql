-- Pazar Yerleri Master Tablosu (Trendyol, Hepsiburada, N11, vb)
CREATE TABLE IF NOT EXISTS marketplaces (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Pazar yeri adı (Trendyol, Hepsiburada)',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique code (trendyol, hepsiburada)',
    logo VARCHAR(255) DEFAULT NULL COMMENT 'Logo dosya yolu',
    description TEXT DEFAULT NULL COMMENT 'Pazar yeri açıklaması',
    api_base_url VARCHAR(255) DEFAULT NULL COMMENT 'Base API URL',
    api_documentation_url VARCHAR(255) DEFAULT NULL COMMENT 'API dokümantasyon URL',
    config_fields JSON DEFAULT NULL COMMENT 'Required config: [{name, label, type, required}]',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Admin tarafından aktif edildi mi',
    supports_auto_sync TINYINT(1) DEFAULT 1 COMMENT 'Otomatik senkronizasyon destekliyor mu',
    supports_tracking_update TINYINT(1) DEFAULT 1 COMMENT 'Kargo takip numarası güncellemesi destekliyor mu',
    priority INT DEFAULT 0 COMMENT 'Sıralama önceliği',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pazar yerleri master listesi';

-- Kullanıcı Pazar Yeri Entegrasyonları
CREATE TABLE IF NOT EXISTS marketplace_integrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Kullanıcı ID',
    marketplace_id INT UNSIGNED NOT NULL COMMENT 'Pazar yeri ID',
    store_name VARCHAR(255) DEFAULT NULL COMMENT 'Mağaza adı',
    credentials JSON NOT NULL COMMENT 'API credentials: {api_key, secret_key, supplier_id}',
    webhook_secret VARCHAR(255) DEFAULT NULL COMMENT 'Webhook secret key',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Entegrasyon aktif mi',
    is_test_mode TINYINT(1) DEFAULT 1 COMMENT 'Test modu aktif mi',
    auto_sync_enabled TINYINT(1) DEFAULT 0 COMMENT 'Otomatik senkronizasyon aktif mi',
    sync_interval_minutes INT DEFAULT 30 COMMENT 'Sync interval (dakika)',
    last_sync_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Son senkronizasyon zamanı',
    test_connection_status VARCHAR(50) DEFAULT NULL COMMENT 'success, failed, pending',
    test_connection_message TEXT DEFAULT NULL COMMENT 'Test sonuç mesajı',
    last_test_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Son test zamanı',
    total_orders_synced INT DEFAULT 0 COMMENT 'Toplam senkronize edilen sipariş',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_marketplace (user_id, marketplace_id),
    INDEX idx_user_id (user_id),
    INDEX idx_marketplace_id (marketplace_id),
    INDEX idx_is_active (is_active),
    INDEX idx_auto_sync_enabled (auto_sync_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kullanıcı pazar yeri entegrasyonları';

-- Pazar Yeri Sipariş Eşleştirmeleri
CREATE TABLE IF NOT EXISTS marketplace_order_mappings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integration_id INT UNSIGNED NOT NULL COMMENT 'Integration ID',
    marketplace_order_id VARCHAR(100) NOT NULL COMMENT 'Pazar yeri sipariş ID',
    marketplace_order_number VARCHAR(100) NOT NULL COMMENT 'Pazar yeri sipariş numarası',
    internal_order_id INT UNSIGNED DEFAULT NULL COMMENT 'Internal order ID',
    sync_status VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, synced, failed',
    tracking_updated TINYINT(1) DEFAULT 0 COMMENT 'Kargo takip numarası güncellendi mi',
    tracking_update_count INT DEFAULT 0 COMMENT 'Tracking update deneme sayısı',
    last_sync_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Son sync zamanı',
    marketplace_data JSON DEFAULT NULL COMMENT 'Pazar yeri raw data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES marketplace_integrations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_marketplace_order (integration_id, marketplace_order_id),
    INDEX idx_integration_id (integration_id),
    INDEX idx_marketplace_order_id (marketplace_order_id),
    INDEX idx_internal_order_id (internal_order_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_tracking_updated (tracking_updated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pazar yeri sipariş eşleştirmeleri';

-- Pazar Yeri Sync Logları
CREATE TABLE IF NOT EXISTS marketplace_sync_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integration_id INT UNSIGNED NOT NULL COMMENT 'Integration ID',
    sync_type VARCHAR(50) NOT NULL COMMENT 'orders, products, tracking_update',
    status VARCHAR(50) NOT NULL COMMENT 'started, completed, failed',
    records_total INT DEFAULT 0 COMMENT 'Toplam kayıt',
    records_synced INT DEFAULT 0 COMMENT 'Başarılı kayıt',
    records_failed INT DEFAULT 0 COMMENT 'Başarısız kayıt',
    error_message TEXT DEFAULT NULL COMMENT 'Hata mesajı',
    started_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Başlangıç',
    completed_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Bitiş',
    metadata JSON DEFAULT NULL COMMENT 'Sync details',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES marketplace_integrations(id) ON DELETE CASCADE,
    INDEX idx_integration_id (integration_id),
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pazar yeri sync logları';

-- Seed Data: Popüler Pazar Yerleri
INSERT INTO marketplaces (name, code, description, api_base_url, config_fields, is_active, priority) VALUES
('Trendyol', 'trendyol', 'Türkiye\'nin önde gelen e-ticaret platformu', 'https://api.trendyol.com', 
'[
    {"name": "supplier_id", "label": "Supplier ID", "type": "text", "required": true, "placeholder": "Satıcı ID"},
    {"name": "api_key", "label": "API Key", "type": "text", "required": true, "placeholder": "API anahtarı"},
    {"name": "api_secret", "label": "API Secret", "type": "password", "required": true, "placeholder": "API Secret"}
]', 1, 10),

('Hepsiburada', 'hepsiburada', 'Hepsiburada Pazar Yeri entegrasyonu', 'https://mpop-sit.hepsiburada.com', 
'[
    {"name": "merchant_id", "label": "Merchant ID", "type": "text", "required": true, "placeholder": "Satıcı ID"},
    {"name": "username", "label": "Kullanıcı Adı", "type": "text", "required": true, "placeholder": "API kullanıcı adı"},
    {"name": "password", "label": "Şifre", "type": "password", "required": true, "placeholder": "API şifre"}
]', 1, 9),

('N11', 'n11', 'N11 Pazar Yeri entegrasyonu', 'https://api.n11.com', 
'[
    {"name": "api_key", "label": "API Key", "type": "text", "required": true, "placeholder": "N11 API Key"},
    {"name": "api_secret", "label": "API Secret", "type": "password", "required": true, "placeholder": "API Secret"}
]', 1, 8),

('GittiGidiyor', 'gittigidiyor', 'GittiGidiyor Pazar Yeri', 'https://dev.gittigidiyor.com', 
'[
    {"name": "api_key", "label": "API Key", "type": "text", "required": true, "placeholder": "API Key"},
    {"name": "secret_key", "label": "Secret Key", "type": "password", "required": true, "placeholder": "Secret Key"},
    {"name": "username", "label": "Kullanıcı Adı", "type": "text", "required": true, "placeholder": "GG kullanıcı adı"}
]', 1, 7),

('Çiçek Sepeti', 'ciceksepeti', 'Çiçek Sepeti Pazar Yeri', 'https://api.ciceksepeti.com', 
'[
    {"name": "api_key", "label": "API Key", "type": "text", "required": true, "placeholder": "API Key"},
    {"name": "supplier_code", "label": "Tedarikçi Kodu", "type": "text", "required": true, "placeholder": "Tedarikçi kodu"}
]', 1, 6),

('Amazon TR', 'amazon_tr', 'Amazon Türkiye Marketplace', 'https://sellingpartnerapi-eu.amazon.com', 
'[
    {"name": "seller_id", "label": "Seller ID", "type": "text", "required": true, "placeholder": "Amazon Seller ID"},
    {"name": "mws_auth_token", "label": "MWS Auth Token", "type": "password", "required": true, "placeholder": "MWS Token"},
    {"name": "marketplace_id", "label": "Marketplace ID", "type": "text", "required": true, "placeholder": "TR Marketplace ID"}
]', 0, 5);
