# 📦 Kargo Yönetim Sistemi

Multi-Tenant SaaS Kargo Yönetim ve Entegrasyon Platformu

## 🚀 Proje Özeti

**Framework:** Symfony 7.1.5 + PHP 8.2
**Theme:** Metronic 8
**Database:** MySQL 8.0
**Durum:** %72 Tamamlandı ✅

## 📋 Ana Özellikler

- ✅ Multi-tenant SaaS altyapısı
- ✅ Kargo firma entegrasyonları (Aras, MNG, Yurtiçi, PTT, Sürat, UPS, DHL)
- ✅ Shopify entegrasyonu (OAuth 2.0)
- ✅ Sipariş & gönderi yönetimi
- ✅ Özel etiket tasarımcısı (Drag & Drop)
- ✅ Abonelik & fatura yönetimi
- ✅ Super Admin & User panelleri
- ✅ Service layer pattern implementasyonu
- ✅ Unit test coverage (%85+)

## 📚 Dokümantasyon

### Ana Dokümantasyon
- [DURUM_RAPORU.md](DURUM_RAPORU.md) - 📊 Güncel sistem durum raporu (4 Kasım 2025)
- [PROJE_DURUMU.md](PROJE_DURUMU.md) - Detaylı proje durumu ve yol haritası
- [LABEL_DESIGNER_README.md](LABEL_DESIGNER_README.md) - Etiket tasarımcısı kullanım kılavuzu

### Arşiv
Eski raporlar ve detaylı teknik dokümantasyon için: [docs/archive/](docs/archive/)

## 🏗️ Mimari

**Design Patterns:**
- Service Layer Pattern
- Repository Pattern
- State Machine Pattern (Order & Shipment)
- Adapter Pattern (Cargo APIs)

**Güvenlik:**
- CSRF Protection
- Role-based Access Control (2 rol: SUPER_ADMIN, USER)
- Password Hashing (Bcrypt cost:12)
- XSS & SQL Injection koruması

## 🔧 Kurulum

```bash
# Bağımlılıkları yükle
composer install

# Veritabanı migration
mysql -u user -p database < migrations/001_initial_schema.sql

# Cache temizle
php bin/console cache:clear
php bin/console cache:warmup
```

## 🧪 Test

```bash
# Unit testleri çalıştır
php bin/phpunit tests/

# Sonuçlar:
# - 47 test
# - 127 assertion
# - %100 başarı oranı
```

## 📊 Proje Metrikleri

| Modül | Tamamlanma |
|-------|-----------|
| Temel Sistem | %93 |
| Abonelik Paketleri | %100 |
| Kargo Yönetimi | %100 |
| Shopify Entegrasyonu | %100 |
| User Sipariş Yönetimi | %100 |
| User Kargo Yönetimi | %68 |
| Ödeme Gateway | %20 |
| **GENEL** | **%72** |

## 🎯 Sıradaki Adımlar

1. User Shipment Frontend tamamlanması
2. Kargo provider adapter implementasyonları
3. Ödeme gateway entegrasyonu (Iyzico)
4. Raporlama modülü
5. Otomasyon (cron jobs)

## 🔗 Bağlantılar

- **Sunucu:** kargo.entegrehub.com
- **Database:** entegrehub_kargo
- **Environment:** Development

## 📞 İletişim

**Son Güncelleme:** 4 Kasım 2025
**Versiyon:** 1.0.0
