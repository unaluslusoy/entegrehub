-- Shopify Mağaza Bağlantıları
CREATE TABLE IF NOT EXISTS shopify_stores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Kullanıcı ID',
    shop_domain VARCHAR(255) NOT NULL COMMENT 'mystore.myshopify.com',
    shop_name VARCHAR(255) DEFAULT NULL COMMENT 'Mağaza adı',
    access_token TEXT NOT NULL COMMENT 'Shopify OAuth access token (encrypted)',
    api_key VARCHAR(255) DEFAULT NULL COMMENT 'Shopify API Key',
    api_secret_key VARCHAR(255) DEFAULT NULL COMMENT 'Shopify API Secret (encrypted)',
    webhook_address VARCHAR(255) DEFAULT NULL COMMENT 'Webhook callback URL',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Bağlantı aktif mi',
    sync_status VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, syncing, completed, failed',
    sync_progress INT DEFAULT 0 COMMENT 'Sync progress %',
    last_sync_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Son senkronizasyon zamanı',
    last_order_sync_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Son sipariş sync zamanı',
    total_orders_synced INT DEFAULT 0 COMMENT 'Toplam senkronize edilen sipariş sayısı',
    shop_email VARCHAR(255) DEFAULT NULL COMMENT 'Mağaza email',
    shop_currency VARCHAR(10) DEFAULT NULL COMMENT 'Mağaza para birimi',
    shop_timezone VARCHAR(100) DEFAULT NULL COMMENT 'Mağaza saat dilimi',
    shop_plan VARCHAR(100) DEFAULT NULL COMMENT 'Shopify plan (basic, shopify, advanced, plus)',
    scopes TEXT DEFAULT NULL COMMENT 'Granted OAuth scopes',
    metadata JSON DEFAULT NULL COMMENT 'Additional shop info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_shop (user_id, shop_domain),
    INDEX idx_user_id (user_id),
    INDEX idx_shop_domain (shop_domain),
    INDEX idx_is_active (is_active),
    INDEX idx_sync_status (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Shopify mağaza bağlantıları';

-- Shopify Webhooks
CREATE TABLE IF NOT EXISTS shopify_webhooks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id INT UNSIGNED NOT NULL COMMENT 'Shopify store ID',
    topic VARCHAR(100) NOT NULL COMMENT 'orders/create, orders/updated, etc',
    webhook_id VARCHAR(100) NOT NULL COMMENT 'Shopify webhook ID',
    address VARCHAR(500) NOT NULL COMMENT 'Webhook callback URL',
    format VARCHAR(20) DEFAULT 'json' COMMENT 'json or xml',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Webhook aktif mi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES shopify_stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_topic (store_id, topic),
    INDEX idx_store_id (store_id),
    INDEX idx_topic (topic),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Shopify webhook kayıtları';

-- Shopify Sync Logs
CREATE TABLE IF NOT EXISTS shopify_sync_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id INT UNSIGNED NOT NULL COMMENT 'Shopify store ID',
    sync_type VARCHAR(50) NOT NULL COMMENT 'orders, products, customers',
    status VARCHAR(50) NOT NULL COMMENT 'started, in_progress, completed, failed',
    records_total INT DEFAULT 0 COMMENT 'Toplam kayıt sayısı',
    records_synced INT DEFAULT 0 COMMENT 'Senkronize edilen kayıt',
    records_failed INT DEFAULT 0 COMMENT 'Başarısız kayıt',
    error_message TEXT DEFAULT NULL COMMENT 'Hata mesajı',
    started_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Başlangıç zamanı',
    completed_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Bitiş zamanı',
    metadata JSON DEFAULT NULL COMMENT 'Sync details',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES shopify_stores(id) ON DELETE CASCADE,
    INDEX idx_store_id (store_id),
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Shopify senkronizasyon logları';

-- Shopify Order Mapping (Shopify order ID -> Internal order ID)
CREATE TABLE IF NOT EXISTS shopify_order_mappings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id INT UNSIGNED NOT NULL COMMENT 'Shopify store ID',
    shopify_order_id VARCHAR(100) NOT NULL COMMENT 'Shopify order ID',
    shopify_order_number VARCHAR(100) NOT NULL COMMENT 'Shopify order number (#1001)',
    internal_order_id INT UNSIGNED DEFAULT NULL COMMENT 'Internal order ID',
    sync_status VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, synced, failed',
    last_sync_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Son sync zamanı',
    shopify_data JSON DEFAULT NULL COMMENT 'Shopify order raw data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES shopify_stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_shopify_order (store_id, shopify_order_id),
    INDEX idx_store_id (store_id),
    INDEX idx_shopify_order_id (shopify_order_id),
    INDEX idx_internal_order_id (internal_order_id),
    INDEX idx_sync_status (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Shopify sipariş eşleştirmeleri';
