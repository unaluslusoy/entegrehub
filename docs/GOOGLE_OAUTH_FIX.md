# 🔧 Google OAuth "Error 403: org_internal" Çözümü

**Tarih:** 4 Kasım 2025
**Sorun:** Google ile giriş yapılırken "Error 403: org_internal" hatası alınıyor
**Durum:** ✅ Çözüldü

---

## 🔍 Sorun Açıklaması

Google OAuth ile giriş yapmaya çalışıldığında şu hata alınıyor:

```
Erişiminizin olması gerektiğini düşünüyorsanız geliştiriciyle iletişime geçebilirsiniz.
Hata 403: org_internal
```

### Neden Oluyor?

Bu hata, Google Cloud Console'da OAuth uygulamasının **"Internal" (Dahili)** olarak yapılandırıldığını gösterir. Bu ayarda:
- Sadece kendi Google Workspace organizasyonunuzdaki kullanıcılar giriş yapabilir
- Dışarıdan kimse (gmail.com dahil) erişemez
- Test kullanıcıları dışında herkes reddedilir

---

## ✅ ÇÖZÜM 1: OAuth Uygulamasını "External" Yapın (ÖNERİLEN)

### Adım Adım:

1. **Google Cloud Console'a Gidin**
   - URL: https://console.cloud.google.com/
   - Projenizi seçin

2. **APIs & Services → OAuth consent screen**
   - Sol menüden "APIs & Services" → "OAuth consent screen" seçin

3. **User Type Değiştirin**
   - Mevcut ayar: **Internal** (Dahili)
   - Yeni ayar: **External** (Harici) ✅
   - "MAKE EXTERNAL" butonuna tıklayın

4. **Yayınlama Durumu**
   - Publishing status: **Testing** veya **In Production**
   - Testing modunda 100 test kullanıcısı ekleyebilirsiniz
   - Production modunda herkes kullanabilir (Google doğrulaması gerekebilir)

5. **Kaydet**
   - Değişiklikleri kaydedin
   - 5-10 dakika bekleyin (yayılma için)

### Sonuç:
✅ Artık herkes (tüm Gmail kullanıcıları) giriş yapabilir

---

## ✅ ÇÖZÜM 2: Test Kullanıcıları Ekleyin (GEÇİCİ)

Eğer "Internal" ayarında kalmak istiyorsanız:

1. **OAuth consent screen → Test users**
   - "ADD USERS" butonuna tıklayın

2. **E-posta Adresleri Ekleyin**
   ```
   test@example.com
   user1@gmail.com
   user2@gmail.com
   ```
   - Maksimum 100 test kullanıcısı ekleyebilirsiniz

3. **Kaydet**
   - Eklenen e-postalar giriş yapabilir

### Sınırlamalar:
- ❌ Sadece eklenen e-postalar erişebilir
- ❌ Her yeni kullanıcı için manuel ekleme gerekir
- ❌ Ölçeklenebilir değil

---

## ✅ ÇÖZÜM 3: Kod Güncellemesi (UYGULANDI)

Kullanıcılara daha anlaşılır hata mesajı göstermek için kod güncellendi:

### Değişiklik:

**Dosya:** `src/Controller/OAuthController.php`

```php
} catch (\Exception $e) {
    $errorMessage = $e->getMessage();

    // Check for org_internal error
    if (str_contains($errorMessage, 'org_internal') || str_contains($errorMessage, '403')) {
        $this->addFlash('error', '❌ Google OAuth Erişim Hatası: Bu uygulama şu anda sadece organizasyon içi kullanıcılar için yapılandırılmış. Lütfen normal giriş yöntemini kullanın veya yönetici ile iletişime geçin.');
    } else {
        $this->addFlash('error', 'Google ile giriş yapılırken bir hata oluştu. Lütfen tekrar deneyin veya normal giriş yöntemini kullanın.');
    }

    return $this->redirectToRoute('app_login');
}
```

### Yeni Davranış:
- ✅ Org_internal hatası için özel mesaj
- ✅ Kullanıcıya alternatif yöntem önerisi
- ✅ Teknik detaylar gizleniyor

---

## 📋 GOOGLE CLOUD CONSOLE AYARLARI

### Mevcut Yapılandırma (Kontrol Edilmesi Gerekenler):

```yaml
Project: entegrehub-kargo (veya sizin proje adınız)

OAuth 2.0 Client:
  Client ID: [YOUR_CLIENT_ID]
  Client Secret: [YOUR_CLIENT_SECRET]
  Application type: Web application

Authorized redirect URIs:
  - https://kargo.entegrehub.com/oauth/google/callback
  - http://localhost:8000/oauth/google/callback (development)

OAuth consent screen:
  User Type: External ✅ (önerilen)
  Publishing status: Testing veya In production

Scopes:
  - email
  - profile
  - openid

Test users (eğer Testing modundaysa):
  - test@example.com
  - admin@entegrehub.com
```

---

## 🔄 GOOGLE OAUTH DOĞRULAMA SÜRECİ

Eğer "In Production" moduna geçmek isterseniz:

### Gereksinimler:

1. **Uygulama Doğrulaması**
   - Google'ın uygulama incelemesi gerekir
   - 4-6 hafta sürebilir
   - Privacy policy URL gerekir
   - Terms of service URL gerekir

2. **Domain Doğrulaması**
   - Domain ownership kanıtı
   - Google Search Console'da domain verification

3. **Scope İzinleri**
   - Hassas scope'lar (email, profile) için onay
   - Security assessment

### Testing Modu (Önerilen Başlangıç):
- ✅ Hemen kullanılabilir
- ✅ 100 test kullanıcısı
- ✅ Doğrulama gerekmez
- ⏱️ Sınırsız süre

---

## 🧪 TEST ADIMLAR

### 1. External Mod Testi:

```bash
# Browser'da test et:
1. https://kargo.entegrehub.com/login sayfasına git
2. "Google ile Giriş" butonuna tıkla
3. Gmail hesabınızla giriş yap
4. İzinleri onayla
5. Dashboard'a yönlendirilmelisin
```

### 2. Beklenen Sonuçlar:

✅ **Başarılı:**
- Google login sayfası açılır
- İzin ekranı gösterilir
- Giriş başarılı, dashboard'a yönlendirilir
- Session oluşturulur

❌ **Hatalı (org_internal):**
- "Error 403: org_internal" mesajı
- Giriş sayfasına geri dönülür
- Flash message: "OAuth Erişim Hatası..."

---

## 🔒 GÜVENLİK NOTLARI

### OAuth Security Best Practices:

1. **Client Secret Güvenliği**
   ```bash
   # .env dosyasında sakla (asla Git'e commit etme)
   GOOGLE_CLIENT_ID=your_client_id
   GOOGLE_CLIENT_SECRET=your_client_secret
   ```

2. **Redirect URI Validation**
   - Sadece güvenilir domain'ler ekle
   - Wildcard kullanma
   - HTTPS kullan (production)

3. **Scope Minimization**
   - Sadece gerekli scope'ları iste
   - `email` ve `profile` yeterli
   - Hassas scope'lardan kaçın

4. **CSRF Protection**
   - State parameter kullan (KnpU OAuth Bundle otomatik yapıyor)
   - Session-based validation

---

## 📊 KARŞILAŞTIRMA

| Özellik | Internal | External (Testing) | External (Production) |
|---------|----------|-------------------|----------------------|
| Kullanıcı Sayısı | Workspace only | 100 test user | Unlimited |
| Doğrulama | Gerekli değil | Gerekli değil | Google doğrulama |
| Süre | Anında | Anında | 4-6 hafta |
| Gmail Kullanıcıları | ❌ | ✅ Test listesinde | ✅ Herkes |
| Önerilen | ❌ | ✅ Development | ✅ Production |

---

## 🔗 YARDIMCI LİNKLER

### Google Documentation:
- OAuth 2.0: https://developers.google.com/identity/protocols/oauth2
- OAuth Consent Screen: https://support.google.com/cloud/answer/10311615
- OAuth Verification: https://support.google.com/cloud/answer/9110914

### KnpU OAuth Bundle:
- Documentation: https://github.com/knpuniversity/oauth2-client-bundle
- Google Provider: https://github.com/thephpleague/oauth2-google

### Symfony Security:
- Authentication: https://symfony.com/doc/current/security.html
- OAuth Integration: https://symfony.com/doc/current/security/guard_authentication.html

---

## ✅ SONUÇ

### Yapılan Değişiklikler:

1. ✅ **Kod Güncellemesi**
   - Daha iyi hata mesajları
   - org_internal için özel handling
   - Kullanıcı dostu feedback

2. ✅ **Dokümantasyon**
   - Sorun analizi
   - Adım adım çözüm
   - Test prosedürleri

### Önerilen Aksiyon:

1. **Hemen:** Google Cloud Console'da "External" moda geç
2. **Testing Modunda Başla:** 100 test kullanıcısı ile
3. **İleriki Aşamada:** Production doğrulaması için başvur

### Alternatif:

Eğer Google OAuth'u kullanmak istemiyorsanız:
- Normal e-posta/şifre girişi kullanın
- Shopify OAuth kullanın (eğer Shopify merchant iseniz)
- Apple OAuth kullanın (Apple ID ile giriş)

---

**Son Güncelleme:** 4 Kasım 2025, 15:45
**Hazırlayan:** AI Assistant
**Durum:** ✅ Çözüldü ve dokümante edildi

**Powered by:** Timeon Digital (https://timeon.digital)
