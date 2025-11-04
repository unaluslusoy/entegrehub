# Shopify OAuth "accounts.shopify.com bağlanmayı reddetti" Hatası

## 🔴 Sorun
OAuth flow başlatıldığında "accounts.shopify.com bağlanmayı reddetti" hatası alınıyor.

## ✅ Çözüm

### Adım 1: Test Sayfasını Aç
```
https://kargo.entegrehub.com/user/shopify/debug
```

Bu sayfa size:
- ✅ App URL
- ✅ Redirect URI 
- ✅ API Key
- ✅ Scopes

bilgilerini gösterir ve kopyalamanızı sağlar.

### Adım 2: Shopify Partners Dashboard Ayarları

1. **Shopify Partners Dashboard'a git:**
   ```
   https://partners.shopify.com/
   ```

2. **Apps → Uygulamanız → App Setup**

3. **Aşağıdaki alanları doldur:**

   **App URL:**
   ```
   https://kargo.entegrehub.com/user/shopify/app
   ```

   **Allowed redirection URL(s):** (ÇOK ÖNEMLİ!)
   ```
   https://kargo.entegrehub.com/user/shopify/callback
   ```

4. **Save** butonuna bas

5. **5-10 dakika bekle** (DNS/CDN propagation)

### Adım 3: Test Et

1. Sayfayı yenile
2. https://kargo.entegrehub.com/user/shopify/install
3. Mağaza domain gir ve "Bağlan" butonuna tıkla

## 📋 Mevcut Ayarlar

```bash
APP_URL=https://kargo.entegrehub.com
SHOPIFY_API_KEY=your_shopify_api_key_here
SHOPIFY_API_SECRET=your_shopify_api_secret_here
```

**Callback URL:**
```
https://kargo.entegrehub.com/user/shopify/callback
```

## 🔍 Debug

### Log İzleme
```bash
# OAuth başlatma logları
tail -f var/log/prod.log | grep "OAuth initiated"

# Örnek çıktı:
# [info] Shopify OAuth initiated {
#   "shop":"test.myshopify.com",
#   "oauth_url":"https://test.myshopify.com/admin/oauth/authorize?...",
#   "redirect_uri":"https://kargo.entegrehub.com/user/shopify/callback"
# }
```

### Test Komutu
```bash
# OAuth URL'yi görmek için
cd /home/entegrehub/domains/kargo.entegrehub.com/public_html
php bin/console app:shopify:test-oauth test.myshopify.com
```

## ❌ Yaygın Hatalar

### 1. "Redirect URI not whitelisted"
**Neden:** Shopify Partners Dashboard'da callback URL yok
**Çözüm:** `https://kargo.entegrehub.com/user/shopify/callback` ekle

### 2. "accounts.shopify.com bağlanmayı reddetti"
**Neden:** Aynı - redirect URI whitelist edilmemiş
**Çözüm:** Yukarıdaki adımları takip et

### 3. "Invalid API key"
**Neden:** API Key yanlış veya app silinmiş
**Çözüm:** Partners Dashboard'dan API Key'i kontrol et

### 4. "HMAC verification failed"
**Neden:** API Secret yanlış
**Çözüm:** .env dosyasındaki SHOPIFY_API_SECRET'i kontrol et

## 🎯 Checklist

Shopify Partners Dashboard'da:
- [ ] App oluşturuldu
- [ ] App URL: `https://kargo.entegrehub.com/user/shopify/app`
- [ ] Allowed redirection URL(s): `https://kargo.entegrehub.com/user/shopify/callback`
- [ ] API Key doğru (.env ile eşleşiyor)
- [ ] API Secret doğru (.env ile eşleşiyor)
- [ ] App distribution: "Custom app" veya "Public app" (Unlisted değil!)
- [ ] Save butonuna basıldı
- [ ] 5-10 dakika beklendi

## 📞 Destek

Hala sorun yaşıyorsanız:

1. Screenshot al:
   - Shopify Partners Dashboard App Setup sayfası
   - OAuth hata ekranı
   - Browser console (F12)

2. Log gönder:
   ```bash
   tail -100 var/log/prod.log | grep -i shopify
   ```

3. Test sayfası sonuçları:
   ```
   https://kargo.entegrehub.com/user/shopify/debug
   ```
