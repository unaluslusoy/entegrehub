-- Create roles table
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `level` INT NOT NULL DEFAULT 0,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create permissions table
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create role_permissions pivot table
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    INDEX `idx_role_id` (`role_id`),
    INDEX `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_roles pivot table
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `roles` (`name`, `slug`, `description`, `level`, `is_system`, `created_at`, `updated_at`) VALUES
('Süper Admin', 'ROLE_SUPER_ADMIN', 'Tam yetki - tüm sistem ayarlarına ve özelliklere erişim', 100, 1, NOW(), NOW()),
('Yönetici', 'ROLE_ADMIN', 'Yönetici yetkisi - çoğu özelliğe erişim', 80, 1, NOW(), NOW()),
('Müdür', 'ROLE_MANAGER', 'Müdür yetkisi - raporlama ve onay süreçleri', 60, 0, NOW(), NOW()),
('Depo Sorumlusu', 'ROLE_WAREHOUSE', 'Depo operasyonları - gönderi yönetimi', 40, 0, NOW(), NOW()),
('Görüntüleyici', 'ROLE_VIEWER', 'Sadece görüntüleme yetkisi', 20, 0, NOW(), NOW());

-- Insert default permissions
INSERT INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
-- Dashboard
('Dashboard Görüntüle', 'dashboard.view', 'dashboard', 'Ana sayfayı görüntüleme', NOW(), NOW()),
('Dashboard İstatistikleri', 'dashboard.stats', 'dashboard', 'Detaylı istatistikleri görüntüleme', NOW(), NOW()),

-- Users
('Kullanıcıları Görüntüle', 'users.view', 'users', 'Kullanıcı listesini görüntüleme', NOW(), NOW()),
('Kullanıcı Oluştur', 'users.create', 'users', 'Yeni kullanıcı oluşturma', NOW(), NOW()),
('Kullanıcı Düzenle', 'users.edit', 'users', 'Kullanıcı bilgilerini düzenleme', NOW(), NOW()),
('Kullanıcı Sil', 'users.delete', 'users', 'Kullanıcı silme', NOW(), NOW()),
('Kullanıcı Rollerini Yönet', 'users.manage_roles', 'users', 'Kullanıcı rollerini atama/kaldırma', NOW(), NOW()),

-- Roles & Permissions
('Rolleri Görüntüle', 'roles.view', 'roles', 'Rol listesini görüntüleme', NOW(), NOW()),
('Rol Oluştur', 'roles.create', 'roles', 'Yeni rol oluşturma', NOW(), NOW()),
('Rol Düzenle', 'roles.edit', 'roles', 'Rol bilgilerini düzenleme', NOW(), NOW()),
('Rol Sil', 'roles.delete', 'roles', 'Rol silme', NOW(), NOW()),
('İzinleri Görüntüle', 'permissions.view', 'permissions', 'İzin listesini görüntüleme', NOW(), NOW()),
('İzinleri Yönet', 'permissions.manage', 'permissions', 'İzinleri atama/kaldırma', NOW(), NOW()),

-- Orders
('Siparişleri Görüntüle', 'orders.view', 'orders', 'Sipariş listesini görüntüleme', NOW(), NOW()),
('Sipariş Detayı', 'orders.detail', 'orders', 'Sipariş detaylarını görüntüleme', NOW(), NOW()),
('Sipariş Oluştur', 'orders.create', 'orders', 'Yeni sipariş oluşturma', NOW(), NOW()),
('Sipariş Düzenle', 'orders.edit', 'orders', 'Sipariş bilgilerini düzenleme', NOW(), NOW()),
('Sipariş Sil', 'orders.delete', 'orders', 'Sipariş silme', NOW(), NOW()),
('Sipariş Durumu Değiştir', 'orders.status', 'orders', 'Sipariş durumunu güncelleme', NOW(), NOW()),

-- Shipments
('Gönderileri Görüntüle', 'shipments.view', 'shipments', 'Gönderi listesini görüntüleme', NOW(), NOW()),
('Gönderi Detayı', 'shipments.detail', 'shipments', 'Gönderi detaylarını görüntüleme', NOW(), NOW()),
('Gönderi Oluştur', 'shipments.create', 'shipments', 'Yeni gönderi oluşturma', NOW(), NOW()),
('Gönderi Düzenle', 'shipments.edit', 'shipments', 'Gönderi bilgilerini düzenleme', NOW(), NOW()),
('Gönderi Sil', 'shipments.delete', 'shipments', 'Gönderi silme', NOW(), NOW()),
('Gönderi Takip', 'shipments.tracking', 'shipments', 'Gönderi takip bilgilerini görüntüleme', NOW(), NOW()),

-- Cargo Companies
('Kargo Firmalarını Görüntüle', 'cargo.view', 'cargo', 'Kargo firması listesini görüntüleme', NOW(), NOW()),
('Kargo Firması Detayı', 'cargo.detail', 'cargo', 'Kargo firması detaylarını görüntüleme', NOW(), NOW()),
('Kargo Firması Ekle', 'cargo.create', 'cargo', 'Yeni kargo firması ekleme', NOW(), NOW()),
('Kargo Firması Düzenle', 'cargo.edit', 'cargo', 'Kargo firması bilgilerini düzenleme', NOW(), NOW()),
('Kargo Firması Sil', 'cargo.delete', 'cargo', 'Kargo firması silme', NOW(), NOW()),

-- Shops
('Mağazaları Görüntüle', 'shops.view', 'shops', 'Mağaza listesini görüntüleme', NOW(), NOW()),
('Mağaza Detayı', 'shops.detail', 'shops', 'Mağaza detaylarını görüntüleme', NOW(), NOW()),
('Mağaza Ekle', 'shops.create', 'shops', 'Yeni mağaza ekleme', NOW(), NOW()),
('Mağaza Düzenle', 'shops.edit', 'shops', 'Mağaza bilgilerini düzenleme', NOW(), NOW()),
('Mağaza Sil', 'shops.delete', 'shops', 'Mağaza silme', NOW(), NOW()),

-- Reports
('Raporları Görüntüle', 'reports.view', 'reports', 'Rapor listesini görüntüleme', NOW(), NOW()),
('Sipariş Raporları', 'reports.orders', 'reports', 'Sipariş raporlarını görüntüleme', NOW(), NOW()),
('Gönderi Raporları', 'reports.shipments', 'reports', 'Gönderi raporlarını görüntüleme', NOW(), NOW()),
('Kargo Performans Raporları', 'reports.cargo_performance', 'reports', 'Kargo performans raporlarını görüntüleme', NOW(), NOW()),
('Finansal Raporlar', 'reports.financial', 'reports', 'Finansal raporları görüntüleme', NOW(), NOW()),
('Rapor Dışa Aktar', 'reports.export', 'reports', 'Raporları Excel/CSV/PDF olarak dışa aktarma', NOW(), NOW()),

-- System
('Sistem Ayarlarını Görüntüle', 'system.view', 'system', 'Sistem ayarlarını görüntüleme', NOW(), NOW()),
('Sistem Ayarlarını Düzenle', 'system.edit', 'system', 'Sistem ayarlarını düzenleme', NOW(), NOW()),
('Logları Görüntüle', 'system.logs', 'system', 'Sistem loglarını görüntüleme', NOW(), NOW());

-- Assign all permissions to ROLE_SUPER_ADMIN
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- Assign basic permissions to ROLE_ADMIN
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` 
WHERE `slug` IN (
    'dashboard.view', 'dashboard.stats',
    'users.view', 'users.create', 'users.edit',
    'orders.view', 'orders.detail', 'orders.create', 'orders.edit', 'orders.status',
    'shipments.view', 'shipments.detail', 'shipments.create', 'shipments.edit', 'shipments.tracking',
    'cargo.view', 'cargo.detail', 'cargo.create', 'cargo.edit',
    'shops.view', 'shops.detail', 'shops.create', 'shops.edit',
    'reports.view', 'reports.orders', 'reports.shipments', 'reports.cargo_performance', 'reports.financial', 'reports.export'
);

-- Assign manager permissions to ROLE_MANAGER
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, `id` FROM `permissions` 
WHERE `slug` IN (
    'dashboard.view', 'dashboard.stats',
    'orders.view', 'orders.detail', 'orders.status',
    'shipments.view', 'shipments.detail', 'shipments.tracking',
    'cargo.view', 'cargo.detail',
    'reports.view', 'reports.orders', 'reports.shipments', 'reports.cargo_performance', 'reports.financial', 'reports.export'
);

-- Assign warehouse permissions to ROLE_WAREHOUSE
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, `id` FROM `permissions` 
WHERE `slug` IN (
    'dashboard.view',
    'orders.view', 'orders.detail',
    'shipments.view', 'shipments.detail', 'shipments.create', 'shipments.edit', 'shipments.tracking',
    'cargo.view', 'cargo.detail'
);

-- Assign viewer permissions to ROLE_VIEWER
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, `id` FROM `permissions` 
WHERE `slug` IN (
    'dashboard.view',
    'orders.view', 'orders.detail',
    'shipments.view', 'shipments.detail',
    'cargo.view', 'cargo.detail',
    'reports.view', 'reports.orders', 'reports.shipments'
);
