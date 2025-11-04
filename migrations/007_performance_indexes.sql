-- Performance Optimization Indexes for Shopify Integration
-- Run this to ensure optimal database performance

-- ShopifyStore indexes
CREATE INDEX IF NOT EXISTS idx_shopify_store_domain_active ON shopify_stores(shop_domain, is_active);
CREATE INDEX IF NOT EXISTS idx_shopify_store_user_active ON shopify_stores(user_id, is_active);
CREATE INDEX IF NOT EXISTS idx_shopify_store_last_sync ON shopify_stores(last_sync_at DESC);
CREATE INDEX IF NOT EXISTS idx_shopify_store_sync_status ON shopify_stores(sync_status, is_active);

-- Composite index for common queries
CREATE INDEX IF NOT EXISTS idx_shopify_store_composite ON shopify_stores(user_id, is_active, shop_domain);

-- Analyze tables for query optimization
ANALYZE TABLE shopify_stores;
ANALYZE TABLE users;

-- Optimize tables
OPTIMIZE TABLE shopify_stores;
OPTIMIZE TABLE users;
