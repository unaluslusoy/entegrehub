# Kullanıcı Özelleştirme Modülleri - İlerleme Raporu
## Tarih: 2024
## Durum: Entity ve Repository Katmanı Tamamlandı ✅

---

## 🎯 Hedef Özellikler

### 1. **Kullanıcı Özel Kargo Firmaları**
Kullanıcılar kendi kargo firmalarını sisteme ekleyebilir ve API entegrasyonlarını yapılandırabilir.

### 2. **Kurumsal SMS/WhatsApp Entegrasyonu**
Kullanıcılar kendi kurumsal numaralarını ve API hesaplarını kullanarak bildirim gönderebilir.

### 3. **Özelleştirilebilir Bildirim Şablonları**
Kullanıcılar otomatik bildirim mesajlarını kendi marka kimliklerine göre düzenleyebilir.

---

## ✅ Tamamlanan İşler

### 📦 1. User Custom Cargo Providers

#### Entity: `UserCargoProvider.php`
```
✅ 20+ property tanımı
✅ JSON fields: credentials, configFields
✅ Test mode desteği (isTestMode, lastTestAt, lastTestStatus)
✅ Logo, documentation, support bilgileri
✅ Lifecycle callbacks (timestamps)
✅ Tüm getter/setter metodları
```

#### Repository: `UserCargoProviderRepository.php`
```
✅ findByUser($user, $activeOnly) - Kullanıcının kargo firmalarını listele
✅ findActiveByUser($user) - Aktif firmaları getir
✅ findByCode($code) - Kod ile ara
✅ countByUser($user) - Kullanıcı firma sayısı
```

---

### 📱 2. SMS/WhatsApp Configuration

#### Entity: `UserNotificationConfig.php`
```
✅ SMS konfigürasyonu (provider, credentials, header)
✅ WhatsApp konfigürasyonu (provider, credentials, number)
✅ Email konfigürasyonu (custom SMTP)
✅ Notification preferences (event bazlı açma/kapama)
✅ Test mode destekleri
✅ OneToOne ilişki User entity ile
```

#### Repository: `UserNotificationConfigRepository.php`
```
✅ findByUser($user) - Kullanıcı config'i getir
✅ findOrCreateForUser($user) - Config yoksa oluştur
✅ findAllWithSmsEnabled() - SMS aktif kullanıcılar
✅ findAllWithWhatsappEnabled() - WhatsApp aktif kullanıcılar
✅ countConfiguredUsers() - Yapılandırılmış kullanıcı sayısı
```

---

### 💬 3. Notification Templates

#### Entity: `UserNotificationTemplate.php`
```
✅ Event type tanımları (order_created, shipment_delivered, vb.)
✅ Channel tanımları (sms, whatsapp, email)
✅ Subject ve body alanları
✅ availableVariables (JSON) - Kullanılabilir değişkenler
✅ renderTemplate($data) metodu - {{variable}} replacement
✅ getDefaultVariablesForEventType() - Event bazlı varsayılan değişkenler
```

#### Repository: `UserNotificationTemplateRepository.php`
```
✅ findByUser($user, $activeOnly) - Kullanıcı şablonları
✅ findByUserAndEvent($user, $eventType, $channel) - Spesifik şablon
✅ findDefaultTemplates() - Sistem varsayılan şablonları
✅ countByUser($user) - Kullanıcı şablon sayısı
✅ findAvailableEventTypes() - Mevcut event'ler
✅ findAvailableChannels() - Mevcut kanallar
```

---

## 🗄️ Veritabanı Migration

### Migration Dosyası: `006_user_customization_features.sql`

#### ✅ Oluşturulan Tablolar:

**1. `user_cargo_providers`**
- 18 kolon (id, user_id, name, code, api_endpoint, credentials, vb.)
- UNIQUE constraint: `user_id + code`
- Foreign key: `users.id` (ON DELETE CASCADE)
- JSON fields: credentials, config_fields

**2. `user_notification_configs`**
- 16 kolon (id, user_id, sms_*, whatsapp_*, email_*)
- UNIQUE constraint: `user_id` (OneToOne ilişki)
- Foreign key: `users.id` (ON DELETE CASCADE)
- JSON fields: sms_credentials, whatsapp_credentials, email_credentials, notification_settings

**3. `user_notification_templates`**
- 11 kolon (id, user_id, event_type, channel, subject, body, vb.)
- UNIQUE constraint: `user_id + event_type + channel`
- Foreign key: `users.id` (ON DELETE CASCADE)
- JSON field: available_variables

#### ✅ Default Template Seeds:
```sql
✅ 5 adet varsayılan template oluşturuldu
   - order_created (SMS, Email)
   - shipment_created (SMS)
   - shipment_delivered (SMS, WhatsApp)
✅ Türkçe içerik
✅ Variable placeholders: {{order_number}}, {{customer_name}}, vb.
```

#### ✅ Migration Çalıştırıldı:
```bash
mysql -u entegrehub_kargo -p entegrehub_kargo < migrations/006_user_customization_features.sql
✅ SUCCESS - No errors
```

---

## 🔗 Entity İlişkileri

### User Entity Güncellemeleri:
```php
✅ OneToOne ilişki eklendi: $notificationConfig
✅ getNotificationConfig() metodu
✅ setNotificationConfig() metodu
✅ Bidirectional relation düzgün kuruldu
```

---

## 📊 Özellik Detayları

### 1. Custom Cargo Provider Özellikleri:

**Kullanım Senaryosu:**
```
1. Kullanıcı "Özel Kargo Firması Ekle" butonuna tıklar
2. Form doldurur: Ad, Kod, API Endpoint
3. API credentials ekler (JSON): api_key, customer_code, secret
4. Config fields tanımlar (dynamic form): Hangi alanlara ihtiyaç var?
5. Test modunda API'yi test eder
6. Aktif hale getirir
7. Artık kendi API'si üzerinden gönderi oluşturabilir
```

**Desteklenen Özellikler:**
- ✅ Test mode (production'a geçmeden test et)
- ✅ Logo upload (marka görselliği)
- ✅ Documentation & Support links (yardım)
- ✅ Webhook URL (status update callbacks)
- ✅ Dynamic config fields (her firma farklı parametre isteyebilir)
- ✅ Last test status tracking (API sağlık kontrolü)

---

### 2. SMS/WhatsApp Configuration Özellikleri:

**Desteklenen SMS Providers:**
- NetGSM (Türkiye)
- İletimerkezi (Türkiye)
- Twilio (International)
- Custom (Kendi API'si)

**Desteklenen WhatsApp Providers:**
- WhatsApp Business API (Official)
- Twilio WhatsApp
- Custom Integration

**Notification Settings (Event Bazlı):**
```json
{
  "order_created": {"sms": true, "whatsapp": true, "email": true},
  "shipment_delivered": {"sms": true, "whatsapp": true, "email": true},
  "payment_received": {"sms": false, "whatsapp": false, "email": true}
}
```

**Kullanıcı kontrol eder:**
- Hangi event'te hangi kanal aktif?
- Test mode (gerçek SMS gönderme)
- Kendi başlığı/numarası (corporate identity)

---

### 3. Notification Templates Özellikleri:

**Available Event Types:**
```
✅ order_created - Sipariş Oluşturuldu
✅ order_cancelled - Sipariş İptal Edildi
✅ shipment_created - Kargo Oluşturuldu
✅ shipment_picked_up - Kargo Teslim Alındı
✅ shipment_in_transit - Kargo Yolda
✅ shipment_delivered - Kargo Teslim Edildi
✅ payment_received - Ödeme Alındı
```

**Variable System:**
```
{{order_number}} → #12345
{{customer_name}} → Ahmet Yılmaz
{{tracking_number}} → 123456789
{{cargo_company}} → MNG Kargo
{{order_total}} → 299.90 TL
... ve 20+ değişken
```

**Template Rendering:**
```php
$template = "Merhaba {{customer_name}}, {{tracking_number}} numaralı kargonuz yolda.";
$data = ['customer_name' => 'Ahmet', 'tracking_number' => '123456'];
$result = $template->renderTemplate($data);
// "Merhaba Ahmet, 123456 numaralı kargonuz yolda."
```

---

## 🎯 Sıradaki Adımlar (Controller & Views)

### 1. Custom Cargo Controller
```
⏳ User/CustomCargoController.php
   - index() - Liste görünümü
   - create() - Yeni firma ekleme formu
   - edit($id) - Düzenleme formu
   - delete($id) - Silme
   - toggle($id) - Aktif/Pasif
   - test($id) - API test
```

### 2. Notification Settings Controller
```
⏳ User/NotificationSettingsController.php
   - index() - SMS/WhatsApp ayarları formu
   - save() - Kaydetme
   - testSms() - Test SMS gönder
   - testWhatsapp() - Test WhatsApp gönder
```

### 3. Notification Templates Controller
```
⏳ User/NotificationTemplatesController.php
   - index() - Şablon listesi
   - create() - Yeni şablon
   - edit($id) - Şablon düzenleme
   - preview($id) - Önizleme
   - resetToDefault($id) - Varsayılana dön
```

### 4. Frontend Templates
```
⏳ templates/user/cargo/custom/
   - index.html.twig (liste + kartlar)
   - form.html.twig (add/edit form)
   
⏳ templates/user/notifications/
   - settings.html.twig (SMS/WhatsApp config)
   - templates.html.twig (template list)
   - template_form.html.twig (WYSIWYG editor)
```

### 5. Services
```
⏳ Service/SmsService.php - SMS gönderim
⏳ Service/WhatsAppService.php - WhatsApp gönderim
⏳ Service/NotificationService.php - Template rendering + routing
⏳ Service/CargoProviderService.php - Custom API çağrıları
```

---

## 💡 Teknik Notlar

### JSON Field Kullanımı:
```php
// credentials field örneği
$provider->setCredentials([
    'api_key' => 'xxx-xxx-xxx',
    'customer_code' => '12345',
    'secret' => 'yyy-yyy-yyy'
]);

// notification_settings field örneği
$config->setNotificationSettings([
    'order_created' => ['sms' => true, 'email' => true]
]);
```

### Dynamic Form Generation:
```php
// configFields - Her kargo firması farklı form alanları isteyebilir
$provider->setConfigFields([
    ['name' => 'customer_code', 'type' => 'text', 'label' => 'Müşteri Kodu', 'required' => true],
    ['name' => 'api_key', 'type' => 'password', 'label' => 'API Anahtarı', 'required' => true],
    ['name' => 'use_sandbox', 'type' => 'checkbox', 'label' => 'Sandbox Kullan', 'required' => false]
]);
```

### Template Variable Security:
```php
// XSS koruması için htmlspecialchars kullanılacak
$safe_value = htmlspecialchars($data[$key], ENT_QUOTES, 'UTF-8');
```

---

## 📈 Sistem Mimarisi

```
┌─────────────────┐
│     User        │
│  (Multi-tenant) │
└────────┬────────┘
         │
    ┌────┴────┬──────────┬──────────────┐
    │         │          │              │
    ▼         ▼          ▼              ▼
┌───────┐ ┌──────┐ ┌─────────┐ ┌──────────────┐
│Cargo  │ │ SMS/ │ │Template │ │   Orders/    │
│Config │ │ WA   │ │ Manager │ │  Shipments   │
└───┬───┘ └──┬───┘ └────┬────┘ └──────┬───────┘
    │        │          │              │
    └────────┴──────────┴──────────────┘
                    │
         ┌──────────┴───────────┐
         ▼                      ▼
    ┌─────────┐          ┌──────────┐
    │ External│          │ Customer │
    │   APIs  │          │          │
    └─────────┘          └──────────┘
```

---

## 🎨 UI/UX Konseptleri

### Custom Cargo Provider Card:
```
┌─────────────────────────────────────┐
│ [LOGO]  MNG Kargo - Özel API        │
│                                      │
│ 📊 Durum: Aktif  🧪 Test Mode: Kapalı│
│ 🔗 API: https://api.mng.com.tr      │
│ ✅ Son Test: 2024-01-15 14:30       │
│                                      │
│ [Test Et] [Düzenle] [Sil]           │
└─────────────────────────────────────┘
```

### Notification Settings:
```
SMS Ayarları
├─ Provider: [NetGSM ▼]
├─ Başlık: [FIRMAM    ]
├─ API Key: [•••••••••]
└─ ☑ Test Modu

WhatsApp Ayarları
├─ Provider: [Twilio ▼]
├─ Numara: [+90 5xx xxx xx xx]
├─ Token: [•••••••••]
└─ ☑ Test Modu

[Test SMS Gönder] [Kaydet]
```

### Template Editor:
```
Event: [Sipariş Oluşturuldu ▼]
Kanal: [SMS ▼]

Mesaj:
┌────────────────────────────────────┐
│ Merhaba {{customer_name}},         │
│ {{order_number}} numaralı          │
│ siparişiniz alındı.                │
│ Toplam: {{order_total}} TL         │
└────────────────────────────────────┘

Kullanılabilir Değişkenler:
• {{customer_name}}
• {{order_number}}
• {{order_total}}
• {{company_name}}

[Önizleme] [Kaydet]
```

---

## 🔒 Güvenlik Önlemleri

### 1. Credentials Encryption
```php
// TODO: Implement encryption for sensitive fields
$encrypted = $encryptor->encrypt($credentials);
$provider->setCredentials($encrypted);
```

### 2. API Rate Limiting
```php
// TODO: Implement rate limiting per user
$rateLimiter->check($user, 'sms_send', 1000); // 1000/day
```

### 3. Test Mode Protection
```php
// Test mode'da gerçek SMS gönderilmemeli
if ($config->isSmsTestMode()) {
    $logger->info('SMS would be sent: ' . $message);
    return new TestResult(['status' => 'test_mode']);
}
```

---

## 📝 Sonraki Sprint Hedefleri

### Sprint 1: Controllers (2-3 gün)
- [ ] CustomCargoController - CRUD operations
- [ ] NotificationSettingsController - SMS/WhatsApp config
- [ ] NotificationTemplatesController - Template management

### Sprint 2: Frontend (3-4 gün)
- [ ] Custom cargo provider UI (cards, forms)
- [ ] Notification settings UI (tabs, test buttons)
- [ ] Template editor UI (WYSIWYG, variable picker)

### Sprint 3: Services (2-3 gün)
- [ ] SMS/WhatsApp integration services
- [ ] Template rendering engine
- [ ] Custom cargo API caller

### Sprint 4: Testing & Polish (2 gün)
- [ ] Unit tests for repositories
- [ ] Integration tests for services
- [ ] UI/UX improvements
- [ ] Documentation

---

## 🎉 İlerleme Özeti

```
Entity Layer:         ████████████████████ 100% ✅
Repository Layer:     ████████████████████ 100% ✅
Migration:            ████████████████████ 100% ✅
Controller Layer:     ░░░░░░░░░░░░░░░░░░░░   0% ⏳
View Layer:           ░░░░░░░░░░░░░░░░░░░░   0% ⏳
Service Layer:        ░░░░░░░░░░░░░░░░░░░░   0% ⏳
Testing:              ░░░░░░░░░░░░░░░░░░░░   0% ⏳

TOPLAM İLERLEME:      ██████░░░░░░░░░░░░░░  30% 🚀
```

---

## 📞 İletişim & Destek

Sorularınız için: developer@entegrehub.com
Proje Durumu: https://kargo.entegrehub.com

---

**Son Güncelleme:** 2024-01-15
**Hazırlayan:** GitHub Copilot AI Assistant
**Durum:** Entity & Repository Layer Complete ✅
