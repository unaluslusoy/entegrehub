-- ============================================
-- Subscription Plans Seed Data
-- ============================================

SET NAMES utf8mb4;

-- Free Plan
INSERT INTO `subscription_plans` (
  `code`, `name`, `description`, 
  `monthly_price`, `yearly_price`, 
  `max_orders`, `max_shops`, `max_users`, 
  `max_sms_per_month`, `max_email_per_month`, 
  `has_api_access`, `has_advanced_reports`, `has_barcode_scanner`, 
  `has_ai_features`, `has_white_label`, `has_priority_support`, `has_custom_domain`,
  `features`, `priority`, `is_active`, `is_popular`, 
  `created_at`, `updated_at`
) VALUES (
  'free', 'Ücretsiz', 'Küçük işletmeler için başlangıç paketi',
  0.00, 0.00,
  50, 1, 1,
  50, 100,
  0, 0, 0,
  0, 0, 0, 0,
  '["shopify_entegrasyonu", "temel_raporlar", "50_adet_aylik_siparis", "1_magaza", "email_destek"]',
  1, 1, 0,
  NOW(), NOW()
);

-- Starter Plan
INSERT INTO `subscription_plans` (
  `code`, `name`, `description`, 
  `monthly_price`, `yearly_price`, 
  `max_orders`, `max_shops`, `max_users`, 
  `max_sms_per_month`, `max_email_per_month`, 
  `has_api_access`, `has_advanced_reports`, `has_barcode_scanner`, 
  `has_ai_features`, `has_white_label`, `has_priority_support`, `has_custom_domain`,
  `features`, `priority`, `is_active`, `is_popular`, 
  `created_at`, `updated_at`
) VALUES (
  'starter', 'Başlangıç', 'Büyüyen işletmeler için ideal',
  299.00, 2990.00,
  300, 2, 2,
  300, 1000,
  0, 1, 1,
  0, 0, 0, 0,
  '["shopify_entegrasyonu", "300_adet_aylik_siparis", "2_magaza", "2_kullanici", "detayli_raporlar", "barkod_okuyucu", "300_sms_200_email", "email_destek", "video_egitim"]',
  2, 1, 0,
  NOW(), NOW()
);

-- Growth Plan (Most Popular)
INSERT INTO `subscription_plans` (
  `code`, `name`, `description`, 
  `monthly_price`, `yearly_price`, 
  `max_orders`, `max_shops`, `max_users`, 
  `max_sms_per_month`, `max_email_per_month`, 
  `has_api_access`, `has_advanced_reports`, `has_barcode_scanner`, 
  `has_ai_features`, `has_white_label`, `has_priority_support`, `has_custom_domain`,
  `features`, `priority`, `is_active`, `is_popular`, 
  `created_at`, `updated_at`
) VALUES (
  'growth', 'Büyüme', 'Orta ölçekli işletmeler için en popüler paket',
  799.00, 7990.00,
  1000, 5, 5,
  1000, 5000,
  1, 1, 1,
  1, 0, 1, 0,
  '["shopify_entegrasyonu", "1000_adet_aylik_siparis", "5_magaza", "5_kullanici", "detayli_raporlar", "barkod_okuyucu", "1000_sms_5000_email", "api_erisimi", "yapay_zeka_asistan", "oncelikli_destek", "video_egitim", "coklu_kargo_entegrasyonu"]',
  3, 1, 1,
  NOW(), NOW()
);

-- Business Plan
INSERT INTO `subscription_plans` (
  `code`, `name`, `description`, 
  `monthly_price`, `yearly_price`, 
  `max_orders`, `max_shops`, `max_users`, 
  `max_sms_per_month`, `max_email_per_month`, 
  `has_api_access`, `has_advanced_reports`, `has_barcode_scanner`, 
  `has_ai_features`, `has_white_label`, `has_priority_support`, `has_custom_domain`,
  `features`, `priority`, `is_active`, `is_popular`, 
  `created_at`, `updated_at`
) VALUES (
  'business', 'İşletme', 'Büyük işletmeler için kapsamlı çözüm',
  1999.00, 19990.00,
  5000, 15, 15,
  5000, 20000,
  1, 1, 1,
  1, 0, 1, 1,
  '["shopify_entegrasyonu", "5000_adet_aylik_siparis", "15_magaza", "15_kullanici", "detayli_raporlar", "barkod_okuyucu", "5000_sms_20000_email", "api_erisimi", "yapay_zeka_asistan", "oncelikli_destek", "ozel_domain", "coklu_kargo_entegrasyonu", "whatsapp_bildirim", "ozel_egitim"]',
  4, 1, 0,
  NOW(), NOW()
);

-- Enterprise Plan
INSERT INTO `subscription_plans` (
  `code`, `name`, `description`, 
  `monthly_price`, `yearly_price`, 
  `max_orders`, `max_shops`, `max_users`, 
  `max_sms_per_month`, `max_email_per_month`, 
  `has_api_access`, `has_advanced_reports`, `has_barcode_scanner`, 
  `has_ai_features`, `has_white_label`, `has_priority_support`, `has_custom_domain`,
  `features`, `priority`, `is_active`, `is_popular`, 
  `created_at`, `updated_at`
) VALUES (
  'enterprise', 'Kurumsal', 'Sınırsız kullanım ve özel çözümler',
  0.00, 0.00,
  999999, 999, 999,
  NULL, NULL,
  1, 1, 1,
  1, 1, 1, 1,
  '["shopify_entegrasyonu", "sinir siz_siparis", "sinirsiz_magaza", "sinirsiz_kullanici", "detayli_raporlar", "barkod_okuyucu", "sinirsiz_bildirim", "api_erisimi", "yapay_zeka_asistan", "oncelikli_destek", "ozel_domain", "white_label", "coklu_kargo_entegrasyonu", "whatsapp_bildirim", "ozel_gelistirme", "yerinde_egitim", "7_24_teknik_destek"]',
  5, 1, 0,
  NOW(), NOW()
);

-- ============================================
-- END OF SEED
-- ============================================
