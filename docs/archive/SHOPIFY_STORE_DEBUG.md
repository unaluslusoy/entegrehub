# Shopify Mağaza Gösterilmeme Sorunu - Çözümler

## Sorun Analizi

Kullanıcı mağaza bağlıyor ama liste sayfasında görünmüyor.

### Olası Nedenler:

1. **User ID ilişkisi yok** - Mağaza user ile ilişkilendirilmemiş
2. **is_active = false** - Mağaza pasif durumda
3. **Session sorunu** - OAuth callback'te user null
4. **Cache problemi** - Eski cache tutuyor

## Uygulanan Düzeltmeler

### 1. AdminAccessListener Dependency Hatası
**Hata:** `Cannot autowire service... Symfony\Component\Security\Core\Security not found`

**Çözüm:**
```php
// ÖNCE (Hatalı)
use Symfony\Component\Security\Core\Security;

// SONRA (Doğru - Symfony 6+)
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
```

### 2. Debug Logging Eklendi
`ShopifyController::index()` metoduna debug log eklendi:
```php
$this->logger->info('Loading Shopify stores index', [
    'user_id' => $user ? $user->getId() : null,
    'user_email' => $user->getEmail(),
]);

$stores = $this->storeRepository->findActiveByUser($user);

$this->logger->info('Shopify stores loaded', [
    'store_count' => count($stores),
    'stats' => $stats,
]);
```

### 3. Debug Script Oluşturuldu
`scripts/debug_shopify_stores.sh` - Database'deki mağazaları kontrol eder

## Test Adımları

### 1. Log Kontrolü
```bash
# User shopify sayfasını açtığında:
tail -f var/log/prod.log | grep "Loading Shopify stores"

# Beklenen çıktı:
# [info] Loading Shopify stores index {"user_id":123,"user_email":"user@example.com"}
# [info] Shopify stores loaded {"store_count":1,"stats":{...}}
```

### 2. Database Manuel Kontrol
```bash
# MySQL'e bağlan
mysql -u username -p database_name

# Mağazaları kontrol et
SELECT id, user_id, shop_domain, is_active, created_at 
FROM shopify_stores 
ORDER BY created_at DESC 
LIMIT 10;

# Belirli user'ın mağazalarını kontrol et
SELECT * FROM shopify_stores WHERE user_id = 123;
```

### 3. Debug Script Çalıştır
```bash
cd /home/entegrehub/domains/kargo.entegrehub.com/public_html
./scripts/debug_shopify_stores.sh
```

## Yaygın Sorunlar ve Çözümler

### Problem 1: user_id NULL
**Belirti:** Mağaza database'de var ama user_id NULL

**Çözüm:**
```sql
-- User ID'yi düzelt
UPDATE shopify_stores 
SET user_id = 123 
WHERE shop_domain = 'your-store.myshopify.com';
```

### Problem 2: is_active = 0
**Belirti:** Mağaza pasif durumda

**Çözüm:**
```sql
-- Mağazayı aktif yap
UPDATE shopify_stores 
SET is_active = 1 
WHERE id = <store_id>;
```

### Problem 3: OAuth callback'te user yok
**Belirti:** Log'da "OAuth callback without authenticated user"

**Çözüm:**
1. Önce login yap
2. Sonra Shopify bağlantısı yap
3. VEYA: Pending connection'ı tamamla

### Problem 4: Pending Connection Tamamlanmamış
**Belirti:** Login sonrası complete_connection'a gitmiyor

**Çözüm:**
Manuel olarak complete:
```
https://kargo.entegrehub.com/user/shopify/complete-connection
```

## Manuel Mağaza Bağlama (Debug için)

```bash
# Console command ile test
php bin/console app:shopify:connect user@example.com your-store.myshopify.com

# Veya SQL ile manuel insert
INSERT INTO shopify_stores (
    user_id, 
    shop_domain, 
    access_token, 
    is_active, 
    created_at, 
    updated_at
) VALUES (
    123,  -- user_id
    'test-store.myshopify.com',
    'test_token_here',
    1,
    NOW(),
    NOW()
);
```

## Monitoring

### Real-time Log Monitoring
```bash
# Terminal 1: Shopify index logs
tail -f var/log/prod.log | grep "Shopify stores"

# Terminal 2: OAuth callback logs
tail -f var/log/prod.log | grep "OAuth callback"

# Terminal 3: Create/Update logs
tail -f var/log/prod.log | grep "store connected"
```

## Sonraki Adımlar

1. ✅ Cache temizlendi
2. ✅ Debug logging eklendi
3. ✅ AdminAccessListener hatası düzeltildi
4. 🔄 Log'ları kontrol edin
5. 🔄 Database'i kontrol edin
6. 🔄 Gerekirse user_id'yi düzeltin

## Hızlı Test

```bash
# 1. Cache temizle
php bin/console cache:clear --env=prod

# 2. Log başlat
tail -f var/log/prod.log &

# 3. Tarayıcıdan test
# https://kargo.entegrehub.com/user/shopify

# 4. Log'u kontrol et
# "store_count" kaç geldi?
```
