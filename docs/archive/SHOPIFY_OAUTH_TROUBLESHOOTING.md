# Shopify OAuth "accounts.shopify.com bağlanmayı reddetti" Hatası Çözümü

## Hata Nedeni
Bu hata, Shopify Partners Dashboard'da eksik/yanlış konfigürasyon olduğunda ortaya çıkar.

## ✅ Yapılması Gerekenler

### 1. Shopify Partners Dashboard Ayarları
https://partners.shopify.com/ → Apps → Uygulamanız → App Setup

**CRITICAL Settings:**

#### App URL (ZORUNLU)
```
https://kargo.entegrehub.com/user/shopify/app
```

#### Allowed redirection URL(s) (ZORUNLU)
```
https://kargo.entegrehub.com/user/shopify/callback
```

#### App distribution
- ✅ **Public app** VEYA **Custom app** seçili olmalı
- ❌ "This app is unlisted" KAPALI olmalı

#### App proxy (Opsiyonel)
Eğer kullanıyorsanız:
- Subpath prefix: `apps`
- Subpath: `kargo-integration`
- Proxy URL: `https://kargo.entegrehub.com/shopify/proxy`

### 2. API Credentials Kontrolü
**src/Controller/User/ShopifyController.php** ve **config/services.yaml**:

```yaml
parameters:
    shopify.api_key: 'f7e2132178fab2f4cf9d857f01394c36'  # API key
    shopify.api_secret: 'YOUR_SECRET_KEY'                 # API secret key
    shopify.scopes: 'read_orders,write_orders,...'
    app.url: 'https://kargo.entegrehub.com'
```

### 3. OAuth Redirect URI Format
**Şu anki ayarlar:**
```php
// ShopifyService.php
public function getRedirectUri(): string
{
    return $this->appUrl . '/user/shopify/callback';
}
```

**Shopify'da AYNEN bu URL olmalı:**
```
https://kargo.entegrehub.com/user/shopify/callback
```

### 4. OAuth Scopes
Mevcut scopes:
```
read_orders,write_orders,read_products,write_products,
read_fulfillments,write_fulfillments,read_shipping,
write_shipping,read_customers,read_inventory
```

### 5. Test Etme

#### A. Manuel Test (Tarayıcıdan)
```
https://kargo.entegrehub.com/user/shopify/install
```
- Shop domain gir: `YOUR-STORE.myshopify.com`
- Submit → Shopify login sayfasına yönlendirilmeli
- ❌ "accounts.shopify.com bağlanmayı reddetti" → Config hatası

#### B. Log İnceleme
```bash
tail -f var/log/prod.log | grep -i shopify
```

Başarılı OAuth:
```
[info] Shopify OAuth initiated {"shop":"test.myshopify.com","user_id":"guest","redirect_uri":"https://kargo.entegrehub.com/user/shopify/callback"}
[info] Shopify OAuth initiated redirecting to: https://test.myshopify.com/admin/oauth/authorize?...
```

#### C. Redirect URL Kontrolü
```bash
curl -v "https://kargo.entegrehub.com/user/shopify/install" 2>&1 | grep -i redirect
```

### 6. Common Issues

#### "Redirect URI not whitelisted"
✅ Partners Dashboard → Allowed redirection URL(s) ekle
```
https://kargo.entegrehub.com/user/shopify/callback
```

#### "Invalid request"
✅ API key ve secret kontrol et
✅ Shopify Partners Dashboard'da app status "Active" olmalı

#### "Connection refused"
✅ SSL sertifikası geçerli olmalı (CloudFlare OK)
✅ Firewall portu 443 açık olmalı

#### "HMAC verification failed"
✅ API secret doğru mu kontrol et
✅ Timestamp çok eskiyse (15+ dakika) tekrar dene

### 7. Embedded App Settings (Opsiyonel)

Eğer Shopify Admin'de embedded app olarak açılacaksa:

#### App Bridge CDN
```html
<script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
```

#### CSP Headers (Zaten var)
```php
// ShopifyController.php - appEntry()
$response->headers->set('Content-Security-Policy', 
    "frame-ancestors https://*.myshopify.com https://admin.shopify.com;"
);
```

### 8. Debug Modu

Geçici olarak debug için:

```php
// ShopifyService.php - getAuthorizationUrl()
$authUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
    'client_id' => $this->apiKey,
    'scope' => $this->scopes,
    'redirect_uri' => $this->getRedirectUri(),
    'state' => $state ?? bin2hex(random_bytes(16)),
    'grant_options[]' => 'per-user',
]);

// LOG THE URL
$this->logger->info('OAuth URL generated', ['url' => $authUrl]);

return $authUrl;
```

Log'da URL'yi görünce tarayıcıda manuel test edebilirsin.

### 9. Checklist

- [ ] Partners Dashboard → App URL doğru
- [ ] Partners Dashboard → Allowed redirection URL(s) doğru
- [ ] API key ve secret config'de doğru
- [ ] App status "Active"
- [ ] SSL sertifikası geçerli
- [ ] Test store'da app install edilebilir durumda
- [ ] Logs'da hata yok

### 10. Hızlı Çözüm

**EN KOLAY:** Shopify Partners Dashboard'da:
1. Apps → Uygulamanız
2. App Setup
3. **Allowed redirection URL(s)** kısmına ekle:
   ```
   https://kargo.entegrehub.com/user/shopify/callback
   ```
4. Save
5. Tekrar dene

Bu %90 sorunu çözer! 🎉
