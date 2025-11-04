<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102133524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA75168D9F6D38 FOREIGN KEY (order_id) REFERENCES `orders` (id)');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA751688D8A1D2 FOREIGN KEY (order_billing_id) REFERENCES `orders` (id)');
        $this->addSql('ALTER TABLE addresses RENAME INDEX uniq_6fca7516c8af2861 TO UNIQ_6FCA751688D8A1D2');
        $this->addSql('ALTER TABLE cargo_companies CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE api_url api_url LONGTEXT DEFAULT NULL, CHANGE tracking_url tracking_url LONGTEXT DEFAULT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL, CHANGE priority priority INT NOT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE cargo_companies RENAME INDEX uniq_d4e5e9f777153098 TO UNIQ_FC5E0FC577153098');
        $this->addSql('ALTER TABLE customer_transactions CHANGE customer_id customer_id INT UNSIGNED NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_transactions ADD CONSTRAINT FK_E470D9559395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('CREATE INDEX IDX_E470D9559395C3F3 ON customer_transactions (customer_id)');
        $this->addSql('ALTER TABLE customers ADD CONSTRAINT FK_62534E214294871E FOREIGN KEY (current_plan_id) REFERENCES `subscription_plans` (id)');
        $this->addSql('ALTER TABLE customers ADD CONSTRAINT FK_62534E212B18554A FOREIGN KEY (owner_user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE invoices ADD customer_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F959395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F959A1887DC FOREIGN KEY (subscription_id) REFERENCES `user_subscriptions` (id)');
        $this->addSql('CREATE INDEX IDX_6A2F2F959395C3F3 ON invoices (customer_id)');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('DROP INDEX idx_barcode ON order_items');
        $this->addSql('DROP INDEX idx_sku ON order_items');
        $this->addSql('ALTER TABLE order_items CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE order_id order_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES `orders` (id)');
        $this->addSql('ALTER TABLE order_items RENAME INDEX idx_order TO IDX_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4D16C4DD');
        $this->addSql('DROP INDEX idx_order_date ON orders');
        $this->addSql('DROP INDEX idx_customer_email ON orders');
        $this->addSql('ALTER TABLE orders CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE shop_id shop_id INT NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE payment_method payment_method VARCHAR(50) NOT NULL, CHANGE payment_status payment_status VARCHAR(50) DEFAULT NULL, CHANGE currency currency VARCHAR(3) NOT NULL, CHANGE customer_note customer_note LONGTEXT DEFAULT NULL, CHANGE internal_note internal_note LONGTEXT DEFAULT NULL, CHANGE is_gift is_gift TINYINT(1) NOT NULL, CHANGE requires_invoice requires_invoice TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4D16C4DD FOREIGN KEY (shop_id) REFERENCES `shops` (id)');
        $this->addSql('ALTER TABLE orders RENAME INDEX idx_shop TO IDX_E52FFDEE4D16C4DD');
        $this->addSql('DROP INDEX idx_slug ON permissions');
        $this->addSql('DROP INDEX idx_module ON permissions');
        $this->addSql('ALTER TABLE permissions CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE permissions RENAME INDEX name TO UNIQ_2DEDCC6F5E237E06');
        $this->addSql('ALTER TABLE permissions RENAME INDEX slug TO UNIQ_2DEDCC6F989D9B62');
        $this->addSql('DROP INDEX idx_slug ON roles');
        $this->addSql('DROP INDEX idx_level ON roles');
        $this->addSql('ALTER TABLE roles CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE level level INT NOT NULL, CHANGE is_system is_system TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE roles RENAME INDEX name TO UNIQ_B63E2EC75E237E06');
        $this->addSql('ALTER TABLE roles RENAME INDEX slug TO UNIQ_B63E2EC7989D9B62');
        $this->addSql('ALTER TABLE role_permissions CHANGE role_id role_id INT NOT NULL, CHANGE permission_id permission_id INT NOT NULL');
        $this->addSql('ALTER TABLE role_permissions RENAME INDEX idx_role_id TO IDX_1FBA94E6D60322AC');
        $this->addSql('ALTER TABLE role_permissions RENAME INDEX idx_permission_id TO IDX_1FBA94E6FED90CCA');
        $this->addSql('ALTER TABLE shipments DROP FOREIGN KEY FK_2CB403A78D9F6D38');
        $this->addSql('ALTER TABLE shipments CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE order_id order_id INT NOT NULL, CHANGE cargo_company_id cargo_company_id INT NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE package_count package_count INT DEFAULT NULL, CHANGE service_type service_type VARCHAR(50) DEFAULT NULL, CHANGE requires_signature requires_signature TINYINT(1) NOT NULL, CHANGE is_cod is_cod TINYINT(1) NOT NULL, CHANGE label_url label_url LONGTEXT DEFAULT NULL, CHANGE barcode_url barcode_url LONGTEXT DEFAULT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL, CHANGE cancel_reason cancel_reason LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE shipments ADD CONSTRAINT FK_94699AD48D9F6D38 FOREIGN KEY (order_id) REFERENCES `orders` (id)');
        $this->addSql('ALTER TABLE shipments RENAME INDEX uniq_2cb403a7c7edf2d4 TO UNIQ_94699AD43E1C9C18');
        $this->addSql('ALTER TABLE shipments RENAME INDEX idx_order TO IDX_94699AD48D9F6D38');
        $this->addSql('ALTER TABLE shipments RENAME INDEX idx_cargo TO IDX_94699AD4BAE7EFFE');
        $this->addSql('ALTER TABLE shops DROP FOREIGN KEY FK_788D7ABAA76ED395');
        $this->addSql('DROP INDEX idx_active ON shops');
        $this->addSql('ALTER TABLE shops CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE access_token access_token LONGTEXT NOT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL, CHANGE auto_sync auto_sync TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE shops ADD CONSTRAINT FK_237A6783A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE shops RENAME INDEX uniq_788d7abae77c4c0e TO UNIQ_237A6783220B3889');
        $this->addSql('ALTER TABLE shops RENAME INDEX idx_user TO IDX_237A6783A76ED395');
        $this->addSql('DROP INDEX idx_priority ON subscription_plans');
        $this->addSql('DROP INDEX idx_active ON subscription_plans');
        $this->addSql('ALTER TABLE subscription_plans CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE monthly_price monthly_price NUMERIC(10, 2) NOT NULL, CHANGE yearly_price yearly_price NUMERIC(10, 2) NOT NULL, CHANGE max_orders max_orders INT NOT NULL, CHANGE max_shops max_shops INT NOT NULL, CHANGE max_users max_users INT NOT NULL, CHANGE has_api_access has_api_access TINYINT(1) NOT NULL, CHANGE has_advanced_reports has_advanced_reports TINYINT(1) NOT NULL, CHANGE has_barcode_scanner has_barcode_scanner TINYINT(1) NOT NULL, CHANGE has_ai_features has_ai_features TINYINT(1) NOT NULL, CHANGE has_white_label has_white_label TINYINT(1) NOT NULL, CHANGE has_priority_support has_priority_support TINYINT(1) NOT NULL, CHANGE has_custom_domain has_custom_domain TINYINT(1) NOT NULL, CHANGE priority priority INT NOT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL, CHANGE is_popular is_popular TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE subscription_plans RENAME INDEX uniq_94ae4aaa77153098 TO UNIQ_CF5F99A277153098');
        $this->addSql('ALTER TABLE user_cargo_companies DROP FOREIGN KEY FK_UCC_CARGO');
        $this->addSql('ALTER TABLE user_cargo_companies DROP FOREIGN KEY FK_UCC_USER');
        $this->addSql('DROP INDEX idx_active ON user_cargo_companies');
        $this->addSql('DROP INDEX uniq_user_cargo ON user_cargo_companies');
        $this->addSql('ALTER TABLE user_cargo_companies CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE cargo_company_id cargo_company_id INT NOT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL, CHANGE is_default is_default TINYINT(1) NOT NULL, CHANGE priority priority INT NOT NULL, CHANGE api_username api_username LONGTEXT NOT NULL, CHANGE api_password api_password LONGTEXT NOT NULL, CHANGE customer_id customer_id LONGTEXT DEFAULT NULL, CHANGE additional_credentials additional_credentials JSON DEFAULT NULL, CHANGE contract_number contract_number LONGTEXT DEFAULT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL, CHANGE is_test_successful is_test_successful TINYINT(1) NOT NULL, CHANGE last_test_error last_test_error LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_cargo_companies ADD CONSTRAINT FK_EE82634AA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE user_cargo_companies ADD CONSTRAINT FK_EE82634ABAE7EFFE FOREIGN KEY (cargo_company_id) REFERENCES `cargo_companies` (id)');
        $this->addSql('ALTER TABLE user_cargo_companies RENAME INDEX idx_user TO IDX_EE82634AA76ED395');
        $this->addSql('ALTER TABLE user_cargo_companies RENAME INDEX idx_cargo TO IDX_EE82634ABAE7EFFE');
        $this->addSql('ALTER TABLE user_notification_settings DROP FOREIGN KEY FK_UNS_USER');
        $this->addSql('DROP INDEX idx_channel ON user_notification_settings');
        $this->addSql('DROP INDEX idx_active ON user_notification_settings');
        $this->addSql('ALTER TABLE user_notification_settings CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE channel channel VARCHAR(50) NOT NULL, CHANGE provider provider VARCHAR(50) NOT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL, CHANGE is_default is_default TINYINT(1) NOT NULL, CHANGE api_username api_username LONGTEXT DEFAULT NULL, CHANGE api_password api_password LONGTEXT DEFAULT NULL, CHANGE api_key api_key LONGTEXT DEFAULT NULL, CHANGE smtp_host smtp_host LONGTEXT DEFAULT NULL, CHANGE smtp_username smtp_username LONGTEXT DEFAULT NULL, CHANGE smtp_password smtp_password LONGTEXT DEFAULT NULL, CHANGE smtp_encryption smtp_encryption VARCHAR(10) DEFAULT NULL, CHANGE whatsapp_business_id whatsapp_business_id LONGTEXT DEFAULT NULL, CHANGE whatsapp_access_token whatsapp_access_token LONGTEXT DEFAULT NULL, CHANGE send_to_customer send_to_customer TINYINT(1) NOT NULL, CHANGE send_to_admin send_to_admin TINYINT(1) NOT NULL, CHANGE monthly_usage monthly_usage INT NOT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL, CHANGE is_test_successful is_test_successful TINYINT(1) NOT NULL, CHANGE last_test_error last_test_error LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_notification_settings ADD CONSTRAINT FK_7051D51EA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE user_notification_settings RENAME INDEX idx_user TO IDX_7051D51EA76ED395');
        $this->addSql('ALTER TABLE user_subscriptions DROP FOREIGN KEY FK_552B0EA9A76ED395');
        $this->addSql('DROP INDEX idx_status ON user_subscriptions');
        $this->addSql('DROP INDEX idx_end_date ON user_subscriptions');
        $this->addSql('ALTER TABLE user_subscriptions CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE plan_id plan_id INT NOT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE billing_period billing_period VARCHAR(20) NOT NULL, CHANGE cancellation_reason cancellation_reason LONGTEXT DEFAULT NULL, CHANGE current_month_orders current_month_orders INT NOT NULL, CHANGE current_month_sms current_month_sms INT NOT NULL, CHANGE current_month_emails current_month_emails INT NOT NULL, CHANGE auto_renew auto_renew TINYINT(1) NOT NULL, CHANGE is_trial_period is_trial_period TINYINT(1) NOT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_subscriptions ADD CONSTRAINT FK_EAF92751A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE user_subscriptions RENAME INDEX idx_user TO IDX_EAF92751A76ED395');
        $this->addSql('ALTER TABLE user_subscriptions RENAME INDEX fk_552b0ea9e899029b TO IDX_EAF92751E899029B');
        $this->addSql('DROP INDEX idx_is_active ON users');
        $this->addSql('DROP INDEX idx_created_at ON users');
        $this->addSql('ALTER TABLE users CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL, CHANGE is_2fa_enabled is_2fa_enabled TINYINT(1) NOT NULL, CHANGE locale locale VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE user_roles CHANGE user_id user_id INT NOT NULL, CHANGE role_id role_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_roles RENAME INDEX idx_user_id TO IDX_54FCD59FA76ED395');
        $this->addSql('ALTER TABLE user_roles RENAME INDEX idx_role_id TO IDX_54FCD59FD60322AC');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `addresses` DROP FOREIGN KEY FK_6FCA75168D9F6D38');
        $this->addSql('ALTER TABLE `addresses` DROP FOREIGN KEY FK_6FCA751688D8A1D2');
        $this->addSql('ALTER TABLE `addresses` RENAME INDEX uniq_6fca751688d8a1d2 TO UNIQ_6FCA7516C8AF2861');
        $this->addSql('ALTER TABLE `cargo_companies` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE api_url api_url TEXT DEFAULT NULL, CHANGE tracking_url tracking_url TEXT DEFAULT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE priority priority INT DEFAULT 0 NOT NULL, CHANGE notes notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `cargo_companies` RENAME INDEX uniq_fc5e0fc577153098 TO UNIQ_D4E5E9F777153098');
        $this->addSql('ALTER TABLE customer_transactions DROP FOREIGN KEY FK_E470D9559395C3F3');
        $this->addSql('DROP INDEX IDX_E470D9559395C3F3 ON customer_transactions');
        $this->addSql('ALTER TABLE customer_transactions CHANGE customer_id customer_id INT NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE customers DROP FOREIGN KEY FK_62534E214294871E');
        $this->addSql('ALTER TABLE customers DROP FOREIGN KEY FK_62534E212B18554A');
        $this->addSql('ALTER TABLE `invoices` DROP FOREIGN KEY FK_6A2F2F95A76ED395');
        $this->addSql('ALTER TABLE `invoices` DROP FOREIGN KEY FK_6A2F2F959395C3F3');
        $this->addSql('ALTER TABLE `invoices` DROP FOREIGN KEY FK_6A2F2F959A1887DC');
        $this->addSql('DROP INDEX IDX_6A2F2F959395C3F3 ON `invoices`');
        $this->addSql('ALTER TABLE `invoices` DROP customer_id');
        $this->addSql('ALTER TABLE `order_items` DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE `order_items` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE order_id order_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE `order_items` ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_barcode ON `order_items` (barcode)');
        $this->addSql('CREATE INDEX idx_sku ON `order_items` (sku)');
        $this->addSql('ALTER TABLE `order_items` RENAME INDEX idx_62809db08d9f6d38 TO idx_order');
        $this->addSql('ALTER TABLE `orders` DROP FOREIGN KEY FK_E52FFDEE4D16C4DD');
        $this->addSql('ALTER TABLE `orders` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE shop_id shop_id INT UNSIGNED NOT NULL, CHANGE status status VARCHAR(50) DEFAULT \'pending\' NOT NULL, CHANGE payment_method payment_method VARCHAR(50) DEFAULT \'online\' NOT NULL, CHANGE payment_status payment_status VARCHAR(50) DEFAULT \'pending\', CHANGE currency currency VARCHAR(3) DEFAULT \'TRY\' NOT NULL, CHANGE customer_note customer_note TEXT DEFAULT NULL, CHANGE internal_note internal_note TEXT DEFAULT NULL, CHANGE is_gift is_gift TINYINT(1) DEFAULT 0 NOT NULL, CHANGE requires_invoice requires_invoice TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE `orders` ADD CONSTRAINT FK_E52FFDEE4D16C4DD FOREIGN KEY (shop_id) REFERENCES shops (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_order_date ON `orders` (order_date)');
        $this->addSql('CREATE INDEX idx_customer_email ON `orders` (customer_email)');
        $this->addSql('ALTER TABLE `orders` RENAME INDEX idx_e52ffdee4d16c4dd TO idx_shop');
        $this->addSql('ALTER TABLE permissions CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_slug ON permissions (slug)');
        $this->addSql('CREATE INDEX idx_module ON permissions (module)');
        $this->addSql('ALTER TABLE permissions RENAME INDEX uniq_2dedcc6f989d9b62 TO slug');
        $this->addSql('ALTER TABLE permissions RENAME INDEX uniq_2dedcc6f5e237e06 TO name');
        $this->addSql('ALTER TABLE role_permissions CHANGE role_id role_id INT UNSIGNED NOT NULL, CHANGE permission_id permission_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE role_permissions RENAME INDEX idx_1fba94e6d60322ac TO idx_role_id');
        $this->addSql('ALTER TABLE role_permissions RENAME INDEX idx_1fba94e6fed90cca TO idx_permission_id');
        $this->addSql('ALTER TABLE roles CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE level level INT DEFAULT 0 NOT NULL, CHANGE is_system is_system TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_slug ON roles (slug)');
        $this->addSql('CREATE INDEX idx_level ON roles (level)');
        $this->addSql('ALTER TABLE roles RENAME INDEX uniq_b63e2ec7989d9b62 TO slug');
        $this->addSql('ALTER TABLE roles RENAME INDEX uniq_b63e2ec75e237e06 TO name');
        $this->addSql('ALTER TABLE `shipments` DROP FOREIGN KEY FK_94699AD48D9F6D38');
        $this->addSql('ALTER TABLE `shipments` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE order_id order_id INT UNSIGNED NOT NULL, CHANGE cargo_company_id cargo_company_id INT UNSIGNED NOT NULL, CHANGE status status VARCHAR(50) DEFAULT \'created\' NOT NULL, CHANGE package_count package_count INT DEFAULT 1, CHANGE service_type service_type VARCHAR(50) DEFAULT \'standard\', CHANGE requires_signature requires_signature TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_cod is_cod TINYINT(1) DEFAULT 0 NOT NULL, CHANGE label_url label_url TEXT DEFAULT NULL, CHANGE barcode_url barcode_url TEXT DEFAULT NULL, CHANGE notes notes TEXT DEFAULT NULL, CHANGE cancel_reason cancel_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `shipments` ADD CONSTRAINT FK_2CB403A78D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `shipments` RENAME INDEX uniq_94699ad43e1c9c18 TO UNIQ_2CB403A7C7EDF2D4');
        $this->addSql('ALTER TABLE `shipments` RENAME INDEX idx_94699ad48d9f6d38 TO idx_order');
        $this->addSql('ALTER TABLE `shipments` RENAME INDEX idx_94699ad4bae7effe TO idx_cargo');
        $this->addSql('ALTER TABLE `shops` DROP FOREIGN KEY FK_237A6783A76ED395');
        $this->addSql('ALTER TABLE `shops` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL, CHANGE access_token access_token TEXT NOT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE auto_sync auto_sync TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE `shops` ADD CONSTRAINT FK_788D7ABAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_active ON `shops` (is_active)');
        $this->addSql('ALTER TABLE `shops` RENAME INDEX uniq_237a6783220b3889 TO UNIQ_788D7ABAE77C4C0E');
        $this->addSql('ALTER TABLE `shops` RENAME INDEX idx_237a6783a76ed395 TO idx_user');
        $this->addSql('ALTER TABLE `subscription_plans` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE monthly_price monthly_price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE yearly_price yearly_price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE max_orders max_orders INT DEFAULT 50 NOT NULL, CHANGE max_shops max_shops INT DEFAULT 1 NOT NULL, CHANGE max_users max_users INT DEFAULT 1 NOT NULL, CHANGE has_api_access has_api_access TINYINT(1) DEFAULT 0 NOT NULL, CHANGE has_advanced_reports has_advanced_reports TINYINT(1) DEFAULT 0 NOT NULL, CHANGE has_barcode_scanner has_barcode_scanner TINYINT(1) DEFAULT 0 NOT NULL, CHANGE has_ai_features has_ai_features TINYINT(1) DEFAULT 0 NOT NULL, CHANGE has_white_label has_white_label TINYINT(1) DEFAULT 0 NOT NULL, CHANGE has_priority_support has_priority_support TINYINT(1) DEFAULT 0 NOT NULL, CHANGE has_custom_domain has_custom_domain TINYINT(1) DEFAULT 0 NOT NULL, CHANGE priority priority INT DEFAULT 0 NOT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_popular is_popular TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_priority ON `subscription_plans` (priority)');
        $this->addSql('CREATE INDEX idx_active ON `subscription_plans` (is_active)');
        $this->addSql('ALTER TABLE `subscription_plans` RENAME INDEX uniq_cf5f99a277153098 TO UNIQ_94AE4AAA77153098');
        $this->addSql('ALTER TABLE `user_cargo_companies` DROP FOREIGN KEY FK_EE82634AA76ED395');
        $this->addSql('ALTER TABLE `user_cargo_companies` DROP FOREIGN KEY FK_EE82634ABAE7EFFE');
        $this->addSql('ALTER TABLE `user_cargo_companies` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL, CHANGE cargo_company_id cargo_company_id INT UNSIGNED NOT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_default is_default TINYINT(1) DEFAULT 0 NOT NULL, CHANGE priority priority INT DEFAULT 0 NOT NULL, CHANGE api_username api_username TEXT NOT NULL COMMENT \'Encrypted\', CHANGE api_password api_password TEXT NOT NULL COMMENT \'Encrypted\', CHANGE customer_id customer_id TEXT DEFAULT NULL COMMENT \'Encrypted\', CHANGE additional_credentials additional_credentials JSON DEFAULT NULL COMMENT \'Encrypted\', CHANGE contract_number contract_number TEXT DEFAULT NULL, CHANGE notes notes TEXT DEFAULT NULL, CHANGE is_test_successful is_test_successful TINYINT(1) DEFAULT 0 NOT NULL, CHANGE last_test_error last_test_error TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user_cargo_companies` ADD CONSTRAINT FK_UCC_CARGO FOREIGN KEY (cargo_company_id) REFERENCES cargo_companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user_cargo_companies` ADD CONSTRAINT FK_UCC_USER FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_active ON `user_cargo_companies` (is_active)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_cargo ON `user_cargo_companies` (user_id, cargo_company_id)');
        $this->addSql('ALTER TABLE `user_cargo_companies` RENAME INDEX idx_ee82634abae7effe TO idx_cargo');
        $this->addSql('ALTER TABLE `user_cargo_companies` RENAME INDEX idx_ee82634aa76ed395 TO idx_user');
        $this->addSql('ALTER TABLE `user_notification_settings` DROP FOREIGN KEY FK_7051D51EA76ED395');
        $this->addSql('ALTER TABLE `user_notification_settings` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL, CHANGE channel channel VARCHAR(50) DEFAULT \'sms\' NOT NULL, CHANGE provider provider VARCHAR(50) DEFAULT \'netgsm\' NOT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_default is_default TINYINT(1) DEFAULT 0 NOT NULL, CHANGE api_username api_username TEXT DEFAULT NULL COMMENT \'Encrypted\', CHANGE api_password api_password TEXT DEFAULT NULL COMMENT \'Encrypted\', CHANGE api_key api_key TEXT DEFAULT NULL COMMENT \'Encrypted\', CHANGE smtp_host smtp_host TEXT DEFAULT NULL, CHANGE smtp_username smtp_username TEXT DEFAULT NULL COMMENT \'Encrypted\', CHANGE smtp_password smtp_password TEXT DEFAULT NULL COMMENT \'Encrypted\', CHANGE smtp_encryption smtp_encryption VARCHAR(10) DEFAULT \'tls\', CHANGE whatsapp_business_id whatsapp_business_id TEXT DEFAULT NULL COMMENT \'Encrypted\', CHANGE whatsapp_access_token whatsapp_access_token TEXT DEFAULT NULL COMMENT \'Encrypted\', CHANGE send_to_customer send_to_customer TINYINT(1) DEFAULT 1 NOT NULL, CHANGE send_to_admin send_to_admin TINYINT(1) DEFAULT 0 NOT NULL, CHANGE monthly_usage monthly_usage INT DEFAULT 0 NOT NULL, CHANGE notes notes TEXT DEFAULT NULL, CHANGE is_test_successful is_test_successful TINYINT(1) DEFAULT 0 NOT NULL, CHANGE last_test_error last_test_error TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user_notification_settings` ADD CONSTRAINT FK_UNS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_channel ON `user_notification_settings` (channel)');
        $this->addSql('CREATE INDEX idx_active ON `user_notification_settings` (is_active)');
        $this->addSql('ALTER TABLE `user_notification_settings` RENAME INDEX idx_7051d51ea76ed395 TO idx_user');
        $this->addSql('ALTER TABLE user_roles CHANGE user_id user_id INT UNSIGNED NOT NULL, CHANGE role_id role_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE user_roles RENAME INDEX idx_54fcd59fa76ed395 TO idx_user_id');
        $this->addSql('ALTER TABLE user_roles RENAME INDEX idx_54fcd59fd60322ac TO idx_role_id');
        $this->addSql('ALTER TABLE `user_subscriptions` DROP FOREIGN KEY FK_EAF92751A76ED395');
        $this->addSql('ALTER TABLE `user_subscriptions` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL, CHANGE plan_id plan_id INT UNSIGNED NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'active\' NOT NULL, CHANGE billing_period billing_period VARCHAR(20) DEFAULT \'monthly\' NOT NULL, CHANGE cancellation_reason cancellation_reason TEXT DEFAULT NULL, CHANGE current_month_orders current_month_orders INT DEFAULT 0 NOT NULL, CHANGE current_month_sms current_month_sms INT DEFAULT 0 NOT NULL, CHANGE current_month_emails current_month_emails INT DEFAULT 0 NOT NULL, CHANGE auto_renew auto_renew TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_trial_period is_trial_period TINYINT(1) DEFAULT 0 NOT NULL, CHANGE notes notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user_subscriptions` ADD CONSTRAINT FK_552B0EA9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_status ON `user_subscriptions` (status)');
        $this->addSql('CREATE INDEX idx_end_date ON `user_subscriptions` (end_date)');
        $this->addSql('ALTER TABLE `user_subscriptions` RENAME INDEX idx_eaf92751a76ed395 TO idx_user');
        $this->addSql('ALTER TABLE `user_subscriptions` RENAME INDEX idx_eaf92751e899029b TO FK_552B0EA9E899029B');
        $this->addSql('ALTER TABLE `users` CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE is_2fa_enabled is_2fa_enabled TINYINT(1) DEFAULT 0 NOT NULL, CHANGE locale locale VARCHAR(10) DEFAULT \'tr\' NOT NULL');
        $this->addSql('CREATE INDEX idx_is_active ON `users` (is_active)');
        $this->addSql('CREATE INDEX idx_created_at ON `users` (created_at)');
    }
}
