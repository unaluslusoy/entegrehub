# 🔐 LOGIN & YÖNLENDİRME TEST SENARYOLARI

**Tarih:** 2025-11-03
**Proje:** Kargo Entegrasyon Sistemi
**Test Eden:** Manuel Test Gerekli

---

## 📋 TEST SENARYOLARı

### ✅ SENARYO 1: SUPER ADMIN GİRİŞİ

**Adımlar:**
1. `/login` adresine git
2. Super Admin hesabıyla giriş yap
   - Email: `superadmin@example.com`
   - Password: `******`

**Beklenen Sonuç:**
- ✅ Giriş başarılı
- ✅ Yönlendirme: `/admin/` (Admin Dashboard)
- ✅ Admin menüsü görünür
- ✅ "Hoşgeldiniz SUPER_ADMIN" mesajı

**Test Edilecek Alt Senaryolar:**
- [ ] Admin dashboard'a erişim
- [ ] Kullanıcı yönetimi erişimi
- [ ] Sistem ayarları erişimi
- [ ] Tüm admin modüllerine erişim

---

### ✅ SENARYO 2: ADMIN GİRİŞİ

**Adımlar:**
1. `/login` adresine git
2. Admin hesabıyla giriş yap
   - Email: `admin@example.com`
   - Password: `******`

**Beklenen Sonuç:**
- ✅ Giriş başarılı
- ✅ Yönlendirme: `/admin/` (Admin Dashboard)
- ✅ Admin menüsü görünür
- ✅ ROLE_ADMIN yetkileri aktif

**Test Edilecek Alt Senaryolar:**
- [ ] Admin dashboard'a erişim
- [ ] User yönetimi erişimi (kısıtlı olabilir)
- [ ] Sipariş/Gönderi yönetimi

---

### ✅ SENARYO 3: NORMAL USER GİRİŞİ

**Adımlar:**
1. `/login` adresine git
2. Normal user hesabıyla giriş yap
   - Email: `user@example.com`
   - Password: `******`

**Beklenen Sonuç:**
- ✅ Giriş başarılı
- ✅ Yönlendirme: `/user/dashboard` (User Dashboard)
- ✅ User menüsü görünür
- ✅ Admin paneline ERİŞEMEZ

**Test Edilecek Alt Senaryolar:**
- [ ] User dashboard görünür
- [ ] Etiket tasarımcısına erişim
- [ ] Sipariş listesine erişim
- [ ] Gönderi yönetimi

---

### ✅ SENARYO 4: HATALI GİRİŞ

**Adımlar:**
1. `/login` adresine git
2. Yanlış şifre ile giriş dene

**Beklenen Sonuç:**
- ❌ Giriş başarısız
- ⚠️ "Geçersiz kullanıcı adı veya şifre" hatası
- ✅ Login sayfasında kal
- ✅ Email alanı dolu kalmalı

---

### ✅ SENARYO 5: ZATEn GİRİŞ YAPıLMıŞ (USER)

**Adımlar:**
1. User olarak giriş yap
2. Tarayıcıda `/login` adresine git

**Beklenen Sonuç:**
- ✅ Otomatik yönlendirme: `/user/dashboard`
- ✅ Flash mesaj YOK (zaten giriş yapılmış)

---

### ✅ SENARYO 6: ZATEN GİRİŞ YAPıLMıŞ (ADMIN)

**Adımlar:**
1. Admin olarak giriş yap
2. Tarayıcıda `/login` adresine git

**Beklenen Sonuç:**
- ✅ Otomatik yönlendirme: `/admin/`
- ✅ Flash mesaj YOK

---

### ✅ SENARYO 7: LOGOUT (USER)

**Adımlar:**
1. User olarak giriş yap
2. "Çıkış Yap" butonuna tıkla

**Beklenen Sonuç:**
- ✅ Session sonlandı
- ✅ Yönlendirme: `/login`
- ✅ Remember Me cookie silindi
- ✅ Tekrar giriş gerekli

---

### ✅ SENARYO 8: LOGOUT (ADMIN)

**Adımlar:**
1. Admin olarak giriş yap
2. "Çıkış Yap" butonuna tıkla

**Beklenen Sonuç:**
- ✅ Session sonlandı
- ✅ Yönlendirme: `/login`
- ✅ Admin paneline erişim YOK

---

### ✅ SENARYO 9: YETKİSİZ ERİŞİM (USER → ADMIN)

**Adımlar:**
1. User olarak giriş yap
2. Tarayıcıda `/admin/` adresine git

**Beklenen Sonuç:**
- ❌ Erişim reddedildi
- ⚠️ Flash mesaj: "Bu sayfaya erişim yetkiniz bulunmamaktadır"
- ✅ Yönlendirme: `/user/dashboard`
- ✅ 403 Forbidden hatası YOK (smooth redirect)

---

### ✅ SENARYO 10: YETKİSİZ ERİŞİM (GUEST → USER)

**Adımlar:**
1. Logout durumda ol
2. Tarayıcıda `/user/dashboard` adresine git

**Beklenen Sonuç:**
- ❌ Erişim reddedildi
- ⚠️ Flash mesaj: "Bu sayfayı görüntülemek için lütfen giriş yapın"
- ✅ Yönlendirme: `/login`
- ✅ Login sonrası target path: `/user/dashboard`

---

### ✅ SENARYO 11: YETKİSİZ ERİŞİM (GUEST → ADMIN)

**Adımlar:**
1. Logout durumda ol
2. Tarayıcıda `/admin/users` adresine git

**Beklenen Sonuç:**
- ❌ Erişim reddedildi
- ⚠️ Flash mesaj: "Bu sayfayı görüntülemek için lütfen giriş yapın"
- ✅ Yönlendirme: `/login`
- ✅ Login sonrası target path: `/admin/users`

---

### ✅ SENARYO 12: REMEMBER ME (USER)

**Adımlar:**
1. User olarak giriş yap (Remember Me işaretle)
2. Tarayıcıyı kapat
3. Tarayıcıyı tekrar aç
4. `/user/dashboard` adresine git

**Beklenen Sonuç:**
- ✅ Otomatik giriş yapıldı
- ✅ User dashboard görünür
- ✅ Tekrar login GEREKMEDİ
- ✅ Cookie 7 gün geçerli

---

### ✅ SENARYO 13: REMEMBER ME (ADMIN)

**Adımlar:**
1. Admin olarak giriş yap (Remember Me işaretle)
2. Tarayıcıyı kapat
3. Tarayıcıyı tekrar aç
4. `/admin/` adresine git

**Beklenen Sonuç:**
- ✅ Otomatik giriş yapıldı
- ✅ Admin dashboard görünür
- ✅ Tekrar login GEREKMEDİ

---

### ✅ SENARYO 14: KAYIT (REGISTER)

**Adımlar:**
1. `/register` adresine git
2. Yeni kullanıcı kaydı yap
   - Ad: Test
   - Soyad: User
   - Email: test@example.com
   - Şifre: Test1234!
   - Şifre Tekrar: Test1234!

**Beklenen Sonuç:**
- ✅ Kayıt başarılı
- ✅ Flash mesaj: "Hesabınız başarıyla oluşturuldu"
- ✅ Yönlendirme: `/login`
- ✅ Otomatik ROLE_USER atandı
- ✅ Email unique kontrolü yapıldı

---

### ✅ SENARYO 15: ŞİFRE SIFIRLAMA

**Adımlar:**
1. `/reset-password` adresine git
2. Email gir: `user@example.com`
3. Email'i kontrol et
4. Reset link'e tıkla
5. Yeni şifre gir

**Beklenen Sonuç:**
- ✅ Reset email gönderildi
- ✅ Token 1 saat geçerli
- ✅ Yeni şifre kaydedildi
- ✅ Yönlendirme: `/login`
- ✅ Eski şifre çalışmıyor
- ✅ Yeni şifre ile giriş yapılıyor

---

### ✅ SENARYO 16: CSRF KORUNMASI

**Adımlar:**
1. Login formunu aç
2. Browser DevTools'u aç
3. CSRF token'ı değiştir veya sil
4. Login dene

**Beklenen Sonuç:**
- ❌ Giriş başarısız
- ⚠️ CSRF validation hatası
- ✅ Güvenlik koruması çalışıyor

---

### ✅ SENARYO 17: AKTİF OLMAYAN HESAP

**Adımlar:**
1. Admin panelden bir kullanıcıyı pasif yap (is_active = false)
2. O kullanıcı ile giriş dene

**Beklenen Sonuç:**
- ❌ Giriş başarısız
- ⚠️ "Hesabınız aktif değil" hatası
- ✅ Dashboard'a erişim YOK

---

### ✅ SENARYO 18: TARGET PATH REDIRECT

**Adımlar:**
1. Logout durumda ol
2. `/user/orders` adresine git
3. Login sayfasına yönlendir
4. Giriş yap

**Beklenen Sonuç:**
- ✅ Login sonrası yönlendirme: `/user/orders`
- ✅ Target path korundu
- ✅ Dashboard'a GİTMEDİ

---

### ✅ SENARYO 19: SHOPIFY PENDING CONNECTION

**Adımlar:**
1. Shopify OAuth başlat
2. Login sayfasına yönlendir (session'da pending connection var)
3. Giriş yap

**Beklenen Sonuç:**
- ✅ Login sonrası: `/user/shopify/complete-connection`
- ✅ Shopify bağlantısı tamamlanıyor
- ✅ Normal dashboard'a GİTMEDİ

---

### ✅ SENARYO 20: API LOGIN (JWT)

**Adımlar:**
```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

**Beklenen Sonuç:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "Test",
    "last_name": "User",
    "roles": ["ROLE_USER"]
  }
}
```

---

## 🎯 TEST CHECKLIST

### Manuel Test Listesi
- [ ] SENARYO 1: Super Admin Girişi
- [ ] SENARYO 2: Admin Girişi
- [ ] SENARYO 3: Normal User Girişi
- [ ] SENARYO 4: Hatalı Giriş
- [ ] SENARYO 5: Zaten Giriş Yapılmış (User)
- [ ] SENARYO 6: Zaten Giriş Yapılmış (Admin)
- [ ] SENARYO 7: Logout (User)
- [ ] SENARYO 8: Logout (Admin)
- [ ] SENARYO 9: Yetkisiz Erişim (User → Admin)
- [ ] SENARYO 10: Yetkisiz Erişim (Guest → User)
- [ ] SENARYO 11: Yetkisiz Erişim (Guest → Admin)
- [ ] SENARYO 12: Remember Me (User)
- [ ] SENARYO 13: Remember Me (Admin)
- [ ] SENARYO 14: Kayıt (Register)
- [ ] SENARYO 15: Şifre Sıfırlama
- [ ] SENARYO 16: CSRF Korunması
- [ ] SENARYO 17: Aktif Olmayan Hesap
- [ ] SENARYO 18: Target Path Redirect
- [ ] SENARYO 19: Shopify Pending Connection
- [ ] SENARYO 20: API Login (JWT)

---

## 🔧 YAPILAN İYİLEŞTİRMELER

### 1. AuthController Güncellemeleri ✅
- `/login` route'unda doğru role-based redirect
- `/register` route'unda admin/user ayrımı
- `/reset-password` route'larında doğru yönlendirmeler
- Tüm auth metodlarında SUPER_ADMIN kontrolü eklendi

### 2. LoginFormAuthenticator Güncellemeleri ✅
- `onAuthenticationSuccess()` metodunda:
  - Target path kontrolü (priority 1)
  - Shopify pending connection kontrolü (priority 2)
  - Role-based redirect (priority 3)
    - SUPER_ADMIN → `/admin/`
    - ADMIN → `/admin/`
    - USER → `/user/dashboard`

### 3. HomeController ✅
- Zaten mükemmel çalışıyor
- `isGranted()` kullanarak role kontrolü
- Doğru dashboard yönlendirmeleri

### 4. Security.yaml Güncellemeleri ✅
- `invalidate_session: true` eklendi logout'a
- `AccessDeniedHandler` eklendi
- Smooth redirect için custom handler

### 5. AccessDeniedHandler Eklendi ✅ **[YENİ]**
- Yetkisiz erişimlerde kullanıcı dostu mesajlar
- Smart redirect:
  - Admin'e erişim yok → User dashboard
  - User'a erişim yok → Login
  - Authenticated ama yetkisiz → Dashboard
  - Guest → Login

---

## 📊 TEST SONUÇLARI (Manuel Doldurulacak)

| Senaryo | Durum | Notlar | Test Tarihi |
|---------|-------|--------|-------------|
| SENARYO 1 | ⏳ Bekliyor | - | - |
| SENARYO 2 | ⏳ Bekliyor | - | - |
| SENARYO 3 | ⏳ Bekliyor | - | - |
| SENARYO 4 | ⏳ Bekliyor | - | - |
| SENARYO 5 | ⏳ Bekliyor | - | - |
| SENARYO 6 | ⏳ Bekliyor | - | - |
| SENARYO 7 | ⏳ Bekliyor | - | - |
| SENARYO 8 | ⏳ Bekliyor | - | - |
| SENARYO 9 | ⏳ Bekliyor | - | - |
| SENARYO 10 | ⏳ Bekliyor | - | - |
| SENARYO 11 | ⏳ Bekliyor | - | - |
| SENARYO 12 | ⏳ Bekliyor | - | - |
| SENARYO 13 | ⏳ Bekliyor | - | - |
| SENARYO 14 | ⏳ Bekliyor | - | - |
| SENARYO 15 | ⏳ Bekliyor | - | - |
| SENARYO 16 | ⏳ Bekliyor | - | - |
| SENARYO 17 | ⏳ Bekliyor | - | - |
| SENARYO 18 | ⏳ Bekliyor | - | - |
| SENARYO 19 | ⏳ Bekliyor | - | - |
| SENARYO 20 | ⏳ Bekliyor | - | - |

---

## 🚀 DEPLOYMENT ÖNCESİ KONTROL

### Kod Seviyesinde ✅
- [x] AuthController role kontrolü
- [x] LoginFormAuthenticator redirect logic
- [x] HomeController role-based redirect
- [x] AccessDeniedHandler eklendi
- [x] Security.yaml güncellemeleri
- [x] CSRF protection aktif
- [x] Remember Me konfigürasyonu

### Test Seviyesinde ⏳
- [ ] Manuel testler tamamlandı
- [ ] Tüm 20 senaryo test edildi
- [ ] Test sonuçları dokümante edildi
- [ ] Bug'lar düzeltildi

### Production Seviyesinde ⏳
- [ ] Cache temizlendi
- [ ] Session store çalışıyor
- [ ] HTTPS aktif (önemli!)
- [ ] Remember Me cookie güvenli
- [ ] Rate limiting aktif (brute force)

---

**Test Raporu Oluşturan:** Claude AI
**Tarih:** 2025-11-03
**Versiyon:** 1.0.0
**Durum:** ✅ Kod Hazır - ⏳ Manuel Test Gerekli
