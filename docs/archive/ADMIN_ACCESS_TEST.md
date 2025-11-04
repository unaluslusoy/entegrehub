# Admin Panel Erişim Testi

## Test Adımları

### 1. ROLE_USER ile Test
```bash
# Tarayıcıdan:
1. ROLE_USER olan bir kullanıcı ile login yap
2. https://kargo.entegrehub.com/admin URL'sine git
3. Beklenen: Otomatik olarak /user/dashboard'a redirect
4. Hata mesajı YOK - sadece sessiz redirect
```

### 2. ROLE_ADMIN ile Test
```bash
# Tarayıcıdan:
1. ROLE_ADMIN olan bir kullanıcı ile login yap
2. https://kargo.entegrehub.com/admin URL'sine git
3. Beklenen: Admin dashboard görünsün
```

### 3. Log Kontrolü
```bash
# Unauthorized erişim denemelerini görmek için:
tail -f var/log/prod.log | grep "Unauthorized admin access"

# Örnek çıktı:
# [2025-11-03 23:50:12] app.WARNING: Unauthorized admin access attempt 
# {"user_id":123,"user_email":"user@example.com","path":"/admin","ip":"192.168.1.100"}
```

## Kullanıcı Rolleri Kontrolü

### Mevcut Kullanıcıları Kontrol Et
```bash
# Database'de user rolleri:
mysql -u username -p database_name -e "SELECT id, email, roles FROM user LIMIT 10;"
```

### Role Değiştir (Test için)
```bash
# ROLE_USER'a çevir:
php bin/console app:user:role user@example.com ROLE_USER

# ROLE_ADMIN'e çevir:
php bin/console app:user:role admin@example.com ROLE_ADMIN
```

## Sistem Davranışı

### ROLE_USER kullanıcısı için:
```
1. Login → user_dashboard
2. /admin açmaya çalışırsa → user_dashboard'a redirect
3. /user/* → Erişebilir
4. /admin/* → Erişemez (redirect)
```

### ROLE_ADMIN kullanıcısı için:
```
1. Login → admin_dashboard
2. /admin → Erişebilir
3. /user → Erişebilir (çünkü ROLE_ADMIN, ROLE_USER'ı içerir)
```

## Güvenlik Katmanları

✅ **Katman 1:** security.yaml - `{ path: ^/admin, roles: ROLE_ADMIN }`
✅ **Katman 2:** AdminAccessListener - User-friendly redirect
✅ **Katman 3:** LoginFormAuthenticator - Role bazlı login redirect

## Canlı Test

Şimdi test edebilirsiniz:
1. Cache temizlendi ✅
2. AdminAccessListener aktif ✅
3. security.yaml güncellendi ✅
4. Login authenticator role bazlı ✅
