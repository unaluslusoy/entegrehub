# Admin Panel Erişim Kontrolü

## Uygulanan Değişiklikler

### 1. AdminAccessListener (Yeni Event Listener)
**Dosya:** `src/EventListener/AdminAccessListener.php`

**İşlevi:**
- Her HTTP isteğini dinler (priority: 9)
- `/admin` ile başlayan tüm istekleri kontrol eder
- Kullanıcı `ROLE_ADMIN` yoksa otomatik olarak `/user/dashboard`'a yönlendirir
- Unauthorized erişim denemelerini loglar

**Özellikler:**
```php
- İstek /admin ile başlıyorsa kontrol et
- Kullanıcı giriş yapmışsa ve ROLE_ADMIN yoksa
  → user_dashboard'a redirect
- Erişim denemesini logla (user_id, email, IP, path)
```

### 2. Security.yaml Güncelleme
**Değişiklik:**
```yaml
# ÖNCE
- { path: ^/admin$, roles: ROLE_ADMIN }
- { path: ^/admin/$, roles: ROLE_ADMIN }
- { path: ^/admin, roles: ROLE_USER }  # ❌ HATALI

# SONRA
- { path: ^/admin, roles: ROLE_ADMIN }  # ✅ DOĞRU
- { path: ^/user, roles: ROLE_USER }
```

**Etki:**
- Artık `/admin` altındaki TÜM route'lar `ROLE_ADMIN` gerektirir
- `ROLE_USER` sadece `/user` alanına erişebilir

### 3. LoginFormAuthenticator Role Bazlı Yönlendirme
**Dosya:** `src/Security/LoginFormAuthenticator.php`

**Yeni Davranış:**
```php
onAuthenticationSuccess() {
    // 1. Target path varsa oraya git
    if ($targetPath) return redirect($targetPath);
    
    // 2. Role kontrolü
    if (ROLE_ADMIN || ROLE_SUPER_ADMIN) {
        return redirect('admin_dashboard');
    }
    
    // 3. Default: User dashboard
    return redirect('user_dashboard');
}
```

## Güvenlik Katmanları

### Katman 1: Access Control (security.yaml)
```yaml
- { path: ^/admin, roles: ROLE_ADMIN }
```
- Symfony Security Component seviyesinde engelleme
- AccessDeniedException fırlatır
- En temel koruma

### Katman 2: Event Listener (AdminAccessListener)
```php
#[AsEventListener(event: KernelEvents::REQUEST, priority: 9)]
```
- Request seviyesinde engelleme
- User-friendly redirect (exception yerine)
- Erişim denemelerini loglar
- Daha kullanıcı dostu

### Katman 3: Controller (Optional)
```php
#[IsGranted('ROLE_ADMIN')]
class AdminController {}
```
- Controller seviyesinde ek koruma
- Attribute ile deklaratif kontrol

## Test Senaryoları

### Senaryo 1: ROLE_USER ile /admin erişimi
```
1. User login yapar (ROLE_USER)
2. https://kargo.entegrehub.com/admin açar
3. AdminAccessListener devreye girer
4. user_dashboard'a redirect edilir
5. Log kaydı oluşturulur
```

### Senaryo 2: ROLE_ADMIN ile /admin erişimi
```
1. Admin login yapar (ROLE_ADMIN)
2. https://kargo.entegrehub.com/admin açar
3. AdminAccessListener kontrol eder
4. ROLE_ADMIN var → geçer
5. Admin paneli görüntülenir
```

### Senaryo 3: Giriş yapmamış kullanıcı
```
1. Anonim kullanıcı /admin açar
2. security.yaml'daki access_control devreye girer
3. LoginFormAuthenticator'a yönlendirilir
4. Login sonrası role bazlı redirect
```

## Monitoring

### Log İnceleme
```bash
# Unauthorized erişim denemelerini izle
tail -f var/log/prod.log | grep "Unauthorized admin access"

# Örnek log:
[2025-11-03 23:45:12] app.WARNING: Unauthorized admin access attempt {
    "user_id": 123,
    "user_email": "user@example.com",
    "path": "/admin",
    "ip": "192.168.1.100"
}
```

### Metrics
- Kaç user admin paneline erişmeye çalıştı?
- Hangi IP'ler sık sık deneme yapıyor?
- En çok hangi admin route'ları deneniyor?

## Avantajlar

1. **Çift Koruma**: security.yaml + Event Listener
2. **User-Friendly**: Exception yerine redirect
3. **Auditability**: Tüm denemeler loglanır
4. **Centralized**: Tek noktadan yönetim
5. **Flexible**: Priority ile sıralama ayarlanabilir

## Dezavantajlar ve Çözümler

### Problem: Event listener her istekte çalışır
**Çözüm:** Priority düşük (9), sadece /admin kontrolü

### Problem: Cache invalidation
**Çözüm:** Her değişiklikten sonra cache clear

### Problem: Test environment
**Çözüm:** Test'te event listener'ı disable et (when@test)

## Yapılandırma

### Event Listener Priority
```php
#[AsEventListener(event: KernelEvents::REQUEST, priority: 9)]
```
- **255+**: Çok erken (routing öncesi)
- **10-50**: Normal (routing sonrası)
- **0-9**: Geç (controller öncesi) ✅ BİZ BURDAYIZ
- **Negatif**: Çok geç (response sonrası)

### Role Hierarchy
```yaml
role_hierarchy:
    ROLE_ADMIN: [ROLE_USER]
    ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```
- ROLE_ADMIN, ROLE_USER'ın tüm yetkilerine sahip
- ROLE_SUPER_ADMIN her şeyi yapabilir

## Test Komutları

```bash
# 1. Cache temizle
php bin/console cache:clear --env=prod

# 2. ROLE_USER ile test (tarayıcıdan)
# - user@example.com ile login
# - https://kargo.entegrehub.com/admin aç
# - user_dashboard'a redirect edilmeli

# 3. ROLE_ADMIN ile test
# - admin@example.com ile login  
# - https://kargo.entegrehub.com/admin aç
# - Admin panel görünmeli

# 4. Log kontrolü
tail -f var/log/prod.log | grep -E "(admin|Unauthorized)"
```

## Sonuç

✅ **ROLE_USER artık /admin'e ERİŞEMEZ**
✅ **Otomatik olarak /user/dashboard'a yönlendirilir**
✅ **Tüm denemeler loglanır**
✅ **User-friendly redirect (exception yok)**
✅ **Çift katmanlı güvenlik**
