-- ============================================
-- Cargo Companies Seed Data
-- ============================================

SET NAMES utf8mb4;

-- Yurtiçi Kargo
INSERT INTO `cargo_companies` (`code`, `name`, `logo`, `api_url`, `tracking_url`, `is_active`, `priority`, `base_cost`, `cost_per_kg`, `settings`, `notes`, `created_at`, `updated_at`) VALUES
('yurtici', 'Yurtiçi Kargo', '/assets/media/cargo/yurtici.png', 'https://api.yurticikargo.com', 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code={tracking_number}', 1, 1, 25.00, 3.50, 
'{"max_weight": 30, "services": ["standard", "express"], "support_cod": true, "support_signature": true}',
'En yaygın kullanılan kargo şirketi. API dokümantasyonu: https://developer.yurticikargo.com',
NOW(), NOW());

-- MNG Kargo
INSERT INTO `cargo_companies` (`code`, `name`, `logo`, `api_url`, `tracking_url`, `is_active`, `priority`, `base_cost`, `cost_per_kg`, `settings`, `notes`, `created_at`, `updated_at`) VALUES
('mng', 'MNG Kargo', '/assets/media/cargo/mng.png', 'https://api.mngkargo.com.tr', 'https://www.mngkargo.com.tr/kargo-sorgulama?code={tracking_number}', 1, 2, 23.00, 3.20,
'{"max_weight": 50, "services": ["standard", "express", "economy"], "support_cod": true, "support_signature": true}',
'Uygun fiyatlı, geniş hizmet ağı. API dokümantasyonu: https://developer.mngkargo.com.tr',
NOW(), NOW());

-- PTT Kargo
INSERT INTO `cargo_companies` (`code`, `name`, `logo`, `api_url`, `tracking_url`, `is_active`, `priority`, `base_cost`, `cost_per_kg`, `settings`, `notes`, `created_at`, `updated_at`) VALUES
('ptt', 'PTT Kargo', '/assets/media/cargo/ptt.png', 'https://api.ptt.gov.tr', 'https://gonderitakip.ptt.gov.tr/Track/Verify?q={tracking_number}', 1, 3, 20.00, 2.80,
'{"max_weight": 30, "services": ["standard", "acele"], "support_cod": true, "support_signature": true}',
'Devlet güvencesi, en uygun fiyat. API dokümantasyonu: https://api.ptt.gov.tr/docs',
NOW(), NOW());

-- Aras Kargo
INSERT INTO `cargo_companies` (`code`, `name`, `logo`, `api_url`, `tracking_url`, `is_active`, `priority`, `base_cost`, `cost_per_kg`, `settings`, `notes`, `created_at`, `updated_at`) VALUES
('aras', 'Aras Kargo', '/assets/media/cargo/aras.png', 'https://api.araskargo.com.tr', 'https://www.araskargo.com.tr/kargo-takip?code={tracking_number}', 1, 4, 24.00, 3.30,
'{"max_weight": 30, "services": ["standard", "express"], "support_cod": true, "support_signature": true}',
'Hızlı teslimat, kaliteli hizmet. API dokümantasyonu: https://developer.araskargo.com.tr',
NOW(), NOW());

-- Sürat Kargo
INSERT INTO `cargo_companies` (`code`, `name`, `logo`, `api_url`, `tracking_url`, `is_active`, `priority`, `base_cost`, `cost_per_kg`, `settings`, `notes`, `created_at`, `updated_at`) VALUES
('surat', 'Sürat Kargo', '/assets/media/cargo/surat.png', 'https://api.suratkargo.com.tr', 'https://www.suratkargo.com.tr/kargo-takip?code={tracking_number}', 1, 5, 22.00, 3.10,
'{"max_weight": 30, "services": ["standard", "express"], "support_cod": true, "support_signature": true}',
'Geniş şube ağı. API dokümantasyonu: https://developer.suratkargo.com.tr',
NOW(), NOW());

-- UPS
INSERT INTO `cargo_companies` (`code`, `name`, `logo`, `api_url`, `tracking_url`, `is_active`, `priority`, `base_cost`, `cost_per_kg`, `settings`, `notes`, `created_at`, `updated_at`) VALUES
('ups', 'UPS', '/assets/media/cargo/ups.png', 'https://api.ups.com', 'https://www.ups.com/track?tracknum={tracking_number}', 1, 6, 50.00, 8.50,
'{"max_weight": 70, "services": ["standard", "express", "worldwide_express"], "support_cod": false, "support_signature": true, "international": true}',
'Uluslararası gönderi için ideal. API dokümantasyonu: https://developer.ups.com',
NOW(), NOW());

-- DHL
INSERT INTO `cargo_companies` (`code`, `name`, `logo`, `api_url`, `tracking_url`, `is_active`, `priority`, `base_cost`, `cost_per_kg`, `settings`, `notes`, `created_at`, `updated_at`) VALUES
('dhl', 'DHL', '/assets/media/cargo/dhl.png', 'https://api.dhl.com', 'https://www.dhl.com/tr-tr/home/tracking.html?tracking-id={tracking_number}', 1, 7, 55.00, 9.00,
'{"max_weight": 70, "services": ["economy", "express", "worldwide_express"], "support_cod": false, "support_signature": true, "international": true}',
'Uluslararası hızlı teslimat. API dokümantasyonu: https://developer.dhl.com',
NOW(), NOW());

-- FedEx
INSERT INTO `cargo_companies` (`code`, `name`, `logo`, `api_url`, `tracking_url`, `is_active`, `priority`, `base_cost`, `cost_per_kg`, `settings`, `notes`, `created_at`, `updated_at`) VALUES
('fedex', 'FedEx', '/assets/media/cargo/fedex.png', 'https://api.fedex.com', 'https://www.fedex.com/fedextrack/?trknbr={tracking_number}', 1, 8, 58.00, 9.50,
'{"max_weight": 68, "services": ["economy", "priority", "international_priority"], "support_cod": false, "support_signature": true, "international": true}',
'Premium uluslararası kargo. API dokümantasyonu: https://developer.fedex.com',
NOW(), NOW());

-- ============================================
-- END OF SEED
-- ============================================
