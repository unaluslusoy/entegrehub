# KARGO YÖNETİM SİSTEMİ - SİSTEM MİMARİSİ VE YETKİLENDİRME RAPORU
**Tarih:** 2 Kasım 2025  
**Versiyon:** 1.0  
**Framework:** Symfony 7.1.5 + Metronic 8 Theme

---

## 📋 İÇİNDEKİLER
1. [Sistem Mimarisi Özeti](#sistem-mimarisi)
2. [URL Yapısı ve Route Organizasyonu](#url-yapısı)
3. [Kullanıcı Rolleri ve Yetkilendirme](#roller-ve-yetkiler)
4. [Admin Panel (Super Admin) - Özellikler](#admin-panel)
5. [User Panel (Normal Kullanıcı) - Özellikler](#user-panel)
6. [Ortak Modüller ve Entiteler](#ortak-moduller)
7. [Güvenlik ve Erişim Kontrolü](#guvenlik)
8. [Deployment ve Ortam Bilgileri](#deployment)

---

## 🏗️ SİSTEM MİMARİSİ {#sistem-mimarisi}

### Genel Yapı
```
KARGO YÖNETİM SİSTEMİ
│
├── SUPER ADMIN PANEL (/admin/*)
│   └── Sistem genelinde yönetim yetkisi
│       - Tüm kullanıcıları yönetme
│       - Sistem ayarları
│       - Kargo firmalarını tanımlama
│       - Abonelik paketleri yönetimi
│
├── USER PANEL (/user/*)
│   └── Kendi işletmesi için operasyonel işlemler
│       - Kendi mağazalarını yönetme
│       - Siparişleri görme ve yönetme
│       - Kargo gönderileri oluşturma
│       - Entegrasyonları kurma
│
└── PUBLIC AREA (/)
    └── Login, Register, Password Reset
```

### Mimari Katmanlar
```
┌─────────────────────────────────────────┐
│         PRESENTATION LAYER              │
│  (Controllers + Twig Templates)         │
│  - Admin/*Controller.php                │
│  - User/*Controller.php                 │
│  - templates/admin/                     │
│  - templates/user/                      │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         BUSINESS LOGIC LAYER            │
│  (Services)                             │
│  - ShopifyService                       │
│  - ShopifySyncService                   │
│  - ShopifyWebhookHandler                │
│  - EmailService                         │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         DATA ACCESS LAYER               │
│  (Repositories + Entities)              │
│  - OrderRepository                      │
│  - ShipmentRepository                   │
│  - ShopRepository                       │
│  - UserRepository                       │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         DATABASE (MySQL 8.0)            │
│  35+ Tables                             │
└─────────────────────────────────────────┘
```

---

## 🔗 URL YAPISI VE ROUTE ORGANİZASYONU {#url-yapısı}

### 🔴 Admin Panel Routes (ROLE_SUPER_ADMIN / ROLE_ADMIN)
**Base URL:** `/admin/*`

#### Dashboard
- `GET /admin/` → `admin_dashboard`
  - Sistem geneli istatistikler
  - Tüm kullanıcıların verilerini görebilir
  - Toplam müşteri, abonelik, gelir raporları

#### Müşteri Yönetimi
- `GET /admin/customers` → `admin_customers_index`
- `GET /admin/customers/create` → `admin_customers_create`
- `GET /admin/customers/{id}` → `admin_customers_show`
- `POST /admin/customers/{id}/edit` → `admin_customers_edit`
- `POST /admin/customers/{id}/delete` → `admin_customers_delete`
- `GET /admin/customers/{id}/transactions` → Customer transaction history

#### Abonelik Paketleri
- `GET /admin/plans` → `admin_plans_index`
- `GET /admin/plans/create` → `admin_plans_create`
- `POST /admin/plans` → `admin_plans_store`
- `GET /admin/plans/{id}/edit` → `admin_plans_edit`
- `POST /admin/plans/{id}/toggle-active` → Enable/disable plan

**Özellikler:**
- 7 paket tipi: Free, Starter, Growth, Premium, Business, Enterprise, Custom
- Aylık/Yıllık fiyatlandırma
- Feature flags (API access, advanced reports, multi-user, etc.)
- Limit ayarları (max shops, orders per month, API calls per day)

#### Kargo Firması Yönetimi (Cargo Providers)
- `GET /admin/cargo-providers` → `admin_cargo_providers`
- `GET /admin/cargo-providers/create` → `admin_cargo_providers_create`
- `POST /admin/cargo-providers/{id}/edit` → `admin_cargo_providers_edit`
- `POST /admin/cargo-providers/{id}/toggle-active` → Enable/disable provider

**Tanımlı Kargo Firmaları:**
1. Aras Kargo
2. MNG Kargo
3. Yurtiçi Kargo
4. Sürat Kargo
5. PTT Kargo
6. UPS
7. DHL

**Özellikler:**
- Logo upload
- API credential template (JSON config_fields)
- Test mode support
- Webhook URL configuration
- Documentation links

#### Fatura Yönetimi
- `GET /admin/invoices` → `admin_invoices_index`
- `GET /admin/invoices/{id}` → `admin_invoices_show`
- `POST /admin/invoices/{id}/send` → Send invoice email

#### Kullanıcı Yönetimi (Panel Users)
- `GET /admin/users` → `admin_panel_users_index`
- `GET /admin/users/create` → `admin_panel_users_create`
- `POST /admin/users/{id}/toggle` → Enable/disable user

#### Rol & Yetki Yönetimi
- `GET /admin/roles` → `admin_roles_index`
- `GET /admin/roles/create` → `admin_roles_create`
- `POST /admin/roles/{id}/permissions` → Update role permissions

**Permission Categories:**
- Dashboard View
- Customer Management (CRUD)
- Plan Management (CRUD)
- Invoice Management
- Cargo Provider Management
- User Management
- Role Management
- Settings Management

#### Sistem Ayarları
- `GET /admin/settings` → `admin_settings_index`
- `POST /admin/settings/shopify` → Shopify API keys
- `POST /admin/settings/email` → Email configuration
- `POST /admin/settings/sms` → SMS provider settings

---

### 🟢 User Panel Routes (ROLE_USER)
**Base URL:** `/user/*`

#### Dashboard
- `GET /user/` → `user_dashboard`
  - **Kullanıcı sadece kendi verilerini görür**
  - Sipariş istatistikleri
  - Gönderi durumları
  - Son siparişler (5 adet)
  - Son gönderiler (5 adet)
  - Mağaza sayısı

#### Sipariş Yönetimi (Orders)
- `GET /user/orders` → `user_orders`
  - DataTables ile liste
  - Filtreleme: mağaza, durum, ödeme durumu, tarih aralığı, tutar
  - Arama: sipariş no, müşteri adı, email
- `GET /user/orders/datatable` → `user_orders_datatable` (AJAX)
- `GET /user/orders/{id}` → `user_order_detail`
  - Sipariş bilgileri
  - Ürün listesi
  - Müşteri bilgileri
  - Teslimat adresi
  - Kargo takip bilgileri
- `POST /user/orders/{id}/status` → `user_order_update_status`
  - Status: pending, processing, ready_to_ship, shipped, delivered, cancelled
- `POST /user/orders/{id}/cancel` → `user_order_cancel`
- `POST /user/orders/{id}/note` → `user_order_add_note`
- `GET /user/orders/export/csv` → `user_orders_export_csv`
- `POST /user/orders/bulk/status` → `user_orders_bulk_status`
- `GET /user/orders/stats/dashboard` → `user_orders_stats` (AJAX)

**Özellikler:**
- ✅ Ownership validation (sadece kendi siparişlerini görebilir)
- ✅ Shopify'dan otomatik senkronizasyon
- ✅ Durum geçişi workflow
- ✅ CSV export
- ✅ Bulk operations

#### Kargo Yönetimi (Shipments)
- `GET /user/shipments` → `user_shipments`
- `GET /user/shipments/datatable` → `user_shipments_datatable` (AJAX)
- `GET /user/shipments/{id}` → `user_shipment_detail`
- `GET|POST /user/shipments/create` → `user_shipment_create`
  - Sipariş seçimi
  - Kargo firması seçimi
  - Servis tipi: standard, express, same_day
  - Ağırlık ve desi girişi
  - COD (cash on delivery) seçimi
- `POST /user/shipments/{id}/status` → `user_shipment_update_status`
  - Status: created, picked_up, in_transit, out_for_delivery, delivered, returned, cancelled
- `POST /user/shipments/{id}/cancel` → `user_shipment_cancel`
- `POST /user/shipments/{id}/track` → `user_shipment_track`
- `GET /user/shipments/{id}/label` → `user_shipment_print_label` (PDF)
- `POST /user/shipments/labels/bulk-print` → `user_shipments_bulk_print`
- `GET /user/shipments/stats/dashboard` → `user_shipments_stats` (AJAX)

**Özellikler:**
- ✅ Ownership validation
- ✅ Otomatik tracking number generation
- ✅ Order status auto-update
- ⚠️ PDF label generation (backend hazır, PDF servisi yapılacak)
- ✅ Bulk label printing ready

#### Shopify Entegrasyonu
- `GET /user/shopify` → `user_shopify_index`
  - Bağlı mağazalar listesi
  - Senkronizasyon durumu
  - İstatistikler (total synced orders, last sync time)
- `GET|POST /user/shopify/install` → `user_shopify_install`
  - OAuth 2.0 başlatma
  - Shop domain girişi
- `GET /user/shopify/callback` → `user_shopify_callback`
  - OAuth token exchange
  - Store kaydetme
  - Webhook registration
- `GET /user/shopify/store/{id}` → `user_shopify_store_detail`
  - Store bilgileri
  - Webhook health status
  - Sync logs
- `POST /user/shopify/store/{id}/sync` → `user_shopify_sync_orders`
  - Manuel senkronizasyon tetikleme
- `POST /user/shopify/store/{id}/test` → `user_shopify_test_connection`
- `POST /user/shopify/store/{id}/disconnect` → `user_shopify_disconnect`
- `POST /user/shopify/webhook` → `user_shopify_webhook`
  - Webhook endpoint (HMAC verified)
  - Events: orders/create, orders/updated, orders/cancelled
- `GET|POST /user/shopify/store/{id}/settings` → `user_shopify_settings`
  - Auto-sync ayarları
  - Webhook management
- `GET /user/shopify/store/{id}/sync-status` → `user_shopify_sync_status` (AJAX)

**Özellikler:**
- ✅ OAuth 2.0 authentication
- ✅ Webhook subscription and verification
- ✅ Order sync (Shopify → Internal DB)
- ✅ Product mapping
- ✅ Rate limiting ready
- ✅ Multiple store support per user

#### Kargo Entegrasyonları
- `GET /user/cargo-integrations` → `user_cargo_integrations`
  - Aktif kargo firmalarını listeler
  - Her firma için entegrasyon durumu
- `GET|POST /user/cargo-integrations/{id}/configure` → `user_cargo_integration_configure`
  - Dinamik form (JSON config_fields based)
  - API credentials girişi
  - Test mode toggle
- `POST /user/cargo-integrations/{id}/test` → `user_cargo_integration_test`
  - Connection test
- `POST /user/cargo-integrations/{id}/toggle` → `user_cargo_integration_toggle`
  - Enable/disable entegrasyon

**Özellikler:**
- ✅ Dynamic form generation
- ✅ Secure credential storage
- ✅ Test mode support
- ✅ Connection testing

---

## 👥 KULLANICI ROLLERİ VE YETKİLENDİRME {#roller-ve-yetkiler}

### Role Hierarchy
```yaml
security:
    role_hierarchy:
        ROLE_ADMIN: [ROLE_USER]
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_USER]
```

### 1. ROLE_SUPER_ADMIN
**Erişim:** Sınırsız - Tüm sistem

**Yetkiler:**
- ✅ Tüm admin panel özelliklerine erişim
- ✅ Tüm müşterileri görüntüleme ve yönetme
- ✅ Abonelik paketleri oluşturma/düzenleme
- ✅ Kargo firmalarını tanımlama
- ✅ Panel kullanıcıları oluşturma
- ✅ Rol ve yetki yönetimi
- ✅ Sistem ayarları değiştirme
- ✅ Fatura yönetimi
- ✅ Raporlama (tüm sistem)

**Erişemez:**
- ❌ User panel özelliklerine doğrudan erişemez (farklı context)

### 2. ROLE_ADMIN
**Erişim:** Admin panel (sınırlı)

**Yetkiler:**
- ✅ Müşteri görüntüleme (düzenleme yok)
- ✅ Rapor görüntüleme
- ✅ Kargo firma listesi görüntüleme
- ❌ Abonelik paketleri düzenleme
- ❌ Sistem ayarları değiştirme
- ❌ Rol/yetki yönetimi

**Not:** Bu rol için permission system kullanılır (role_permissions tablosu)

### 3. ROLE_USER (Default)
**Erişim:** User panel (/user/*)

**Yetkiler:**
- ✅ Kendi mağazalarını yönetme
- ✅ Kendi siparişlerini görüntüleme ve yönetme
- ✅ Kargo gönderisi oluşturma (kendi siparişleri için)
- ✅ Shopify entegrasyonu kurma
- ✅ Kargo entegrasyonları yapılandırma
- ✅ Kendi raporlarını görüntüleme
- ✅ Profil ve ayarlarını düzenleme
- ✅ Abonelik paketini görüntüleme/yükseltme

**Erişemez:**
- ❌ Admin panel (/admin/*)
- ❌ Başka kullanıcıların verilerini görüntüleyemez
- ❌ Sistem ayarlarına erişemez
- ❌ Kargo firması tanımlayamaz (sadece mevcut olanları kullanır)

### Ownership Validation
**Tüm User Panel Controller'larda:**
```php
// Order örneği
$order = $this->orderRepository->find($id);
if (!$order || $order->getShop()->getUser() !== $user) {
    throw $this->createAccessDeniedException();
}

// Shipment örneği
$shipment = $this->shipmentRepository->find($id);
if (!$shipment || $shipment->getOrder()->getShop()->getUser() !== $user) {
    throw $this->createAccessDeniedException();
}
```

**Entity İlişkileri:**
```
User (1) ─┐
          ├─ (Many) Shop
          │          └─ (Many) Order
          │                    └─ (Many) Shipment
          │
          ├─ (Many) ShopifyStore
          └─ (Many) CargoProviderConfig
```

---

## 🎛️ ADMIN PANEL - ÖZELLIKLER VE YETENEKLER {#admin-panel}

### Dashboard (/admin/)
**İstatistikler:**
- Toplam müşteri sayısı
- Aktif abonelikler
- Toplam gelir (aylık/yıllık)
- Yeni kayıtlar (son 30 gün)
- Sistem kullanım oranları

**Grafikler:**
- Aylık gelir trendi
- Abonelik dağılımı (paket bazında)
- Aktif/pasif müşteri oranı

**Son Aktiviteler:**
- Yeni kayıtlar
- Abonelik yenilemeleri
- Ödeme işlemleri

### Müşteri Yönetimi
**Özellikler:**
- DataTables ile liste
- Filtreleme: durum, abonelik paketi, kayıt tarihi
- Arama: ad, email, firma
- Müşteri detay sayfası:
  - Temel bilgiler
  - Abonelik durumu
  - İşlem geçmişi (customer_transactions)
  - Fatura listesi
  - Kullanım istatistikleri
- CRUD operasyonları
- Müşteri aktif/pasif yapma
- Email gönderme

### Abonelik Paketleri
**Paket Özellikleri:**
```json
{
  "name": "Growth",
  "monthly_price": 299.99,
  "yearly_price": 2999.99,
  "features": {
    "max_shops": 5,
    "max_orders_per_month": 1000,
    "max_api_calls_per_day": 10000,
    "has_api_access": true,
    "has_advanced_reports": true,
    "has_priority_support": true,
    "has_multi_user": true,
    "max_users": 3,
    "has_custom_branding": false
  }
}
```

**Yönetim:**
- Paket oluşturma/düzenleme
- Fiyat güncelleme
- Feature toggle
- Popular badge
- Aktif/pasif yapma
- Sıralama (priority)

### Kargo Firması Tanımlama
**Config Fields Örneği (Aras Kargo):**
```json
[
  {
    "name": "api_key",
    "label": "API Key",
    "type": "text",
    "required": true,
    "help_text": "Aras Kargo'dan alınan API anahtarı"
  },
  {
    "name": "api_secret",
    "label": "API Secret",
    "type": "password",
    "required": true
  },
  {
    "name": "customer_code",
    "label": "Müşteri Kodu",
    "type": "text",
    "required": true
  },
  {
    "name": "test_mode",
    "label": "Test Modu",
    "type": "checkbox",
    "default": false
  }
]
```

**Özellikler:**
- Logo upload (SVG/PNG)
- API endpoint configuration
- Webhook URL
- Documentation link
- Support contact
- Active/inactive status
- JSON schema validation

### Fatura Yönetimi
**Otomatik Fatura Oluşturma:**
- Abonelik yenileme zamanı
- Paket değişikliği
- Ek özellik satın alma

**Manuel İşlemler:**
- Fatura düzenleme
- Fatura iptal
- Ödeme durumu güncelleme
- Email gönderme
- PDF export

### Rol & Yetki Sistemi
**Roller:**
1. Super Admin (tam yetki)
2. Admin (sınırlı)
3. Support (görüntüleme)
4. Finance (fatura ve ödeme)

**Yetkiler (Permissions):**
- dashboard.view
- customers.view
- customers.create
- customers.edit
- customers.delete
- plans.view
- plans.create
- plans.edit
- invoices.view
- invoices.manage
- settings.view
- settings.edit
- users.manage
- roles.manage

**Yönetim:**
- Role oluşturma/düzenleme
- Permission atama (checkboxes)
- Kullanıcıya rol atama

---

## 👤 USER PANEL - ÖZELLIKLER VE YETENEKLER {#user-panel}

### Dashboard (/user/)
**İstatistikler:**
- Toplam sipariş sayısı
- Toplam ciro
- Aktif gönderiler
- Mağaza sayısı
- Son 30 gün sipariş

**Widgets:**
- Son 5 sipariş (order number, müşteri, tutar, durum)
- Son 5 gönderi (tracking no, kargo firması, durum)
- Shopify senkronizasyon durumu
- Quick actions (Yeni gönderi, Shopify sync, vb.)

### Sipariş Yönetimi
**Liste Özellikleri:**
- DataTables pagination
- 9 sütun: ID, Sipariş No, Mağaza, Müşteri, Tutar, Durum, Ödeme, Tarih, İşlemler
- Status badges (renkli)
- Real-time search
- Multi-column sorting

**Filtreler:**
- Mağaza seçimi (dropdown)
- Durum (pending, processing, shipped, delivered, cancelled)
- Ödeme durumu (pending, paid, failed, refunded)
- Tarih aralığı (date picker)
- Tutar aralığı (min-max)

**Detay Sayfası:**
- Sipariş bilgileri (order number, tarih, durum, ödeme)
- Ürün listesi (tablo: ürün, SKU, miktar, birim fiyat, toplam)
- Müşteri bilgileri (ad, email, telefon)
- Teslimat adresi (adres, şehir, ülke, telefon)
- Kargo takip (shipment list, tracking numbers)
- Durum güncelleme formu
- Not ekleme
- İptal butonu

**İşlemler:**
- Durum değiştirme (dropdown + not)
- Sipariş iptal etme (onay pop-up)
- CSV export (filtered)
- Bulk status update (multi-select)

### Kargo Yönetimi
**Gönderi Oluşturma Flow:**
1. Sipariş seçimi (dropdown veya order detail'den)
2. Kargo firması seçimi (aktif entegrasyonlar)
3. Servis tipi (standard/express/same_day)
4. Paket bilgileri:
   - Ağırlık (kg)
   - Desi (cm³)
   - Paket sayısı
5. Özel seçenekler:
   - İmza gerekli mi?
   - Kapıda ödeme (COD)
   - COD tutarı
6. Notlar
7. Submit → Tracking number otomatik oluşturulur

**Liste Özellikleri:**
- DataTables pagination
- Sütunlar: Tracking No, Sipariş, Kargo Firması, Durum, Servis, Ağırlık, COD, Tarih, İşlemler
- Filtreleme: kargo firması, durum, tarih aralığı

**Detay Sayfası:**
- Gönderi bilgileri
- Sipariş linki
- Kargo firması bilgileri
- Tracking history (timeline)
- Teslimat adresi
- Durum güncelleme formu
- İptal butonu
- Etiket yazdırma (PDF)

**İşlemler:**
- Durum güncelleme
- Manuel tracking refresh
- Etiket yazdırma (tekli)
- Bulk label printing
- Gönderi iptal

### Shopify Entegrasyonu
**Kurulum Adımları:**
1. "Install" butonuna tıkla
2. Shop domain gir (ornek.myshopify.com)
3. Shopify'a yönlendir (OAuth)
4. İzinleri onayla
5. Callback → token al
6. Store kaydedilir
7. Webhook'lar otomatik kurulur

**Store Yönetimi:**
- Store listesi (cards)
- Her store için:
  - Shop domain
  - Last sync time
  - Total synced orders
  - Sync status (başarılı/hata)
  - Connection status (aktif/pasif)
- Store detay sayfası:
  - Store bilgileri
  - OAuth token durumu
  - Webhook health (yeşil/kırmızı)
  - Sync logs (tablo)
  - Manuel sync butonu
  - Disconnect butonu

**Senkronizasyon:**
- Otomatik: Webhook ile real-time
- Manuel: "Sync Now" butonu
- Bulk: Tüm orders'ı çek (ilk kurulum)
- Mapping: Shopify Order ID ↔ Internal Order ID

**Settings:**
- Auto-sync toggle
- Sync frequency
- Product mapping rules
- Order status mapping
- Webhook re-register

### Kargo Entegrasyonları
**Yapılandırma:**
- Aktif kargo firmalarını listele
- Her firma için:
  - Logo
  - Firma adı
  - Entegrasyon durumu (yok/test/canlı)
  - "Configure" butonu

**Configure Flow:**
1. Kargo firması seç
2. Dinamik form göster (config_fields based)
3. Credentials gir (API key, secret, customer code, etc.)
4. Test mode toggle
5. "Test Connection" butonu
6. Başarılı ise → Kaydet
7. Enable/disable toggle

**Test Connection:**
- API endpoint'e test request
- Credential validation
- Response göster (başarılı/hata)
- Hata detayları log

---

## 🔗 ORTAK MODÜLLER VE ENTİTELER {#ortak-moduller}

### Database Schema (35+ Tables)

#### Core Tables
```sql
users (id, email, password, roles, is_active, created_at)
roles (id, name, description)
permissions (id, name, category, description)
role_permissions (role_id, permission_id)
```

#### Subscription System
```sql
subscription_plans (id, name, monthly_price, yearly_price, features_json, is_active)
user_subscriptions (id, user_id, plan_id, status, starts_at, ends_at)
invoices (id, user_id, subscription_id, amount, status, due_date)
customer_transactions (id, customer_id, type, amount, description, created_at)
```

#### Business Entities
```sql
shops (id, user_id, shop_name, shop_domain, platform, is_active)
orders (id, shop_id, order_number, status, payment_status, total_amount, created_at)
order_items (id, order_id, product_name, sku, quantity, price)
shipments (id, order_id, cargo_company_id, tracking_number, status, weight, desi)
addresses (id, order_id, type, first_name, last_name, address1, city, country)
```

#### Cargo System
```sql
cargo_companies (id, name, code, logo_path, api_endpoint, config_fields_json)
cargo_provider_configs (id, user_id, provider_id, credentials_json, is_active)
```

#### Shopify Integration
```sql
shopify_stores (id, user_id, shop_domain, access_token, scope, installed_at)
shopify_webhooks (id, store_id, topic, address, verified_at)
shopify_sync_logs (id, store_id, type, status, records_processed, error_message)
shopify_order_mappings (id, shopify_store_id, shopify_order_id, internal_order_id)
```

### Entity Relations
```
User
├── shops (OneToMany)
│   └── orders (OneToMany)
│       ├── items (OneToMany)
│       ├── shipments (OneToMany)
│       └── addresses (OneToMany)
├── shopifyStores (OneToMany)
│   ├── webhooks (OneToMany)
│   ├── syncLogs (OneToMany)
│   └── orderMappings (OneToMany)
├── cargoProviderConfigs (OneToMany)
├── subscription (OneToOne)
└── invoices (OneToMany)

CargoCompany
├── configs (OneToMany) → CargoProviderConfig
└── shipments (OneToMany)
```

### Services
```php
// Shopify
ShopifyService
  - getAuthorizationUrl()
  - exchangeCodeForToken()
  - makeApiRequest()
  - verifyWebhook()
  - registerWebhook()

ShopifySyncService
  - syncOrders()
  - syncSingleOrder()
  - createOrUpdateInternalOrder()

ShopifyWebhookHandler
  - handleWebhook()
  - processOrderCreate()
  - processOrderUpdate()
  - processOrderCancelled()

// Email
EmailService
  - sendWelcomeEmail()
  - sendPasswordResetEmail()
  - sendInvoiceEmail()
  - sendOrderConfirmationEmail()
```

---

## 🔒 GÜVENLİK VE ERİŞİM KONTROLÜ {#guvenlik}

### Authentication
```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
    
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
    
    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator: App\Security\LoginFormAuthenticator
            logout:
                path: app_logout
                target: homepage
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800 # 1 week
```

### Authorization
```yaml
access_control:
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/register, roles: PUBLIC_ACCESS }
    - { path: ^/reset-password, roles: PUBLIC_ACCESS }
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/user, roles: ROLE_USER }
    - { path: ^/, roles: PUBLIC_ACCESS }
```

### Route Protection
**Admin Routes:**
```php
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class SomeAdminController extends AbstractController
```

**User Routes:**
```php
#[Route('/user')]
#[IsGranted('ROLE_USER')]
class SomeUserController extends AbstractController
```

### Ownership Validation Pattern
```php
private function validateOwnership(Order $order, User $user): void
{
    if ($order->getShop()->getUser() !== $user) {
        throw $this->createAccessDeniedException(
            'Bu siparişe erişim yetkiniz yok.'
        );
    }
}
```

### CSRF Protection
- Tüm form'larda CSRF token
- API endpoint'lerde token validation
- Symfony Security component

### Webhook Security
```php
// Shopify HMAC Verification
public function verifyWebhook(Request $request): bool
{
    $hmacHeader = $request->headers->get('X-Shopify-Hmac-Sha256');
    $data = $request->getContent();
    $calculatedHmac = base64_encode(
        hash_hmac('sha256', $data, $this->apiSecret, true)
    );
    return hash_equals($hmacHeader, $calculatedHmac);
}
```

### API Rate Limiting
- Shopify: Leaky Bucket algorithm ready
- Internal API: Rate limiter bundle (future)

---

## 🚀 DEPLOYMENT VE ORTAM BİLGİLERİ {#deployment}

### Sunucu Bilgileri
```
Server: kargo.entegrehub.com
SSH: entegrehub@kargo.entegrehub.com
Path: /home/entegrehub/domains/kargo.entegrehub.com/public_html
Web Root: public/
PHP: 8.2+
MySQL: 8.0
Web Server: Apache/Nginx
```

### Environment Variables
```env
APP_ENV=prod
APP_SECRET=your-secret-key
DATABASE_URL="mysql://user:pass@localhost:3306/kargo_db"
SHOPIFY_API_KEY=your-shopify-api-key
SHOPIFY_API_SECRET=your-shopify-api-secret
MAILER_DSN=smtp://smtp.gmail.com
```

### Deployment Commands
```bash
# Production deployment
cd /home/entegrehub/domains/kargo.entegrehub.com/public_html

# Update code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear cache
php bin/console cache:clear --env=prod

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Asset install
php bin/console assets:install public

# Set permissions
chmod -R 775 var/
chown -R entegrehub:entegrehub var/
```

### Cron Jobs (Future)
```bash
# Shopify order sync (every 5 minutes)
*/5 * * * * cd /path && php bin/console app:sync-shopify-orders

# Invoice generation (daily)
0 0 * * * cd /path && php bin/console app:generate-invoices

# Tracking update (every hour)
0 * * * * cd /path && php bin/console app:update-tracking
```

---

## 📊 PROJE DURUMU ÖZET

### Tamamlanan Modüller (%62)
✅ Authentication & Authorization  
✅ Admin Panel - Dashboard  
✅ Admin Panel - Müşteri Yönetimi  
✅ Admin Panel - Abonelik Paketleri  
✅ Admin Panel - Kargo Firması Yönetimi  
✅ Admin Panel - Fatura Yönetimi  
✅ Admin Panel - Rol & Yetki Sistemi  
✅ User Panel - Dashboard  
✅ User Panel - Sipariş Yönetimi (Full)  
✅ User Panel - Kargo Yönetimi (Backend)  
✅ User Panel - Shopify Entegrasyonu (Full)  
✅ User Panel - Kargo Entegrasyonları  

### Devam Eden / Eksik Modüller
⚠️ User Panel - Kargo Yönetimi Frontend (Templates)  
⚠️ Kargo Etiket Yazdırma (PDF Service)  
❌ User Shop Management (CRUD)  
❌ Payment Gateway (Iyzico, PayTR)  
❌ Notification System (Email/SMS)  
❌ Reporting Module  
❌ Automation (Cron jobs)  

### Öncelikli TODO
1. **Shipment Frontend Templates** - En acil (backend hazır)
2. **PDF Label Service** - FPDF/TCPDF + Barcode
3. **User Shop CRUD** - Basit CRUD ekle
4. **Dashboard Enhancement** - Grafikler ekle (Chart.js)
5. **Payment Gateway** - Iyzico entegre et

---

## 📝 SONUÇ VE ÖNERİLER

### Güçlü Yönler
✅ Temiz mimari (Controller → Service → Repository)  
✅ Role-based access control tam çalışıyor  
✅ Ownership validation her yerde mevcut  
✅ Shopify entegrasyonu production-ready  
✅ Admin ve User panelleri tamamen ayrık  
✅ URL yapısı organize (/admin/*, /user/*)  
✅ DataTables ile modern UI  
✅ Metronic 8 theme profesyonel görünüm  

### İyileştirme Alanları
⚠️ Frontend template eksiklikleri (shipment, shop)  
⚠️ PDF generation servisi yok  
⚠️ Payment gateway entegre değil  
⚠️ Notification system eksik  
⚠️ Automated tests yok  
⚠️ API documentation eksik  

### Bir Sonraki Adımlar (Öncelik Sırası)
1. **Shipment templates oluştur** (2-3 saat)
2. **PDF Label Service** (DomPDF + Barcode library) (4-5 saat)
3. **User Shop CRUD** (basit CRUD) (2-3 saat)
4. **Payment Gateway - Iyzico** (6-8 saat)
5. **Dashboard grafikler** (Chart.js) (2-3 saat)
6. **Notification Service** (Email template + Queue) (4-5 saat)
7. **Admin Order Management** (tüm siparişleri görüntüleme) (3-4 saat)
8. **Testing** (PHPUnit + functional tests) (8-10 saat)

**Toplam Tahmini Süre:** 31-42 saat (4-5 iş günü)

---

**Rapor Tarihi:** 2 Kasım 2025  
**Hazırlayan:** AI Assistant  
**Sistem Versiyonu:** 1.0.0  
**Framework:** Symfony 7.1.5 + Metronic 8
