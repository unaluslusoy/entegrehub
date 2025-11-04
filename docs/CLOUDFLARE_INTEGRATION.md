# Cloudflare Entegrasyonu

EntegreHub Kargo sistemi Cloudflare ile tam entegre çalışmaktadır.

## Özellikler

### 🛡️ Güvenlik
- ✅ Real IP Detection (CF-Connecting-IP)
- ✅ Firewall Access Rules (IP block/whitelist)
- ✅ Rate Limiting (API & Login protection)
- ✅ Under Attack Mode
- ✅ Country Blocking
- ✅ Security Event Monitoring
- ✅ Automatic threat detection

### ⚡ Performans
- ✅ Cache Purging (Full & Selective)
- ✅ CDN Integration
- ✅ Analytics & Metrics

### 📊 Yönetim
- ✅ Admin Panel Dashboard
- ✅ CLI Commands
- ✅ Real-time Security Events
- ✅ Firewall Rule Management

## Kurulum

### 1. Cloudflare API Token Oluşturma

1. Cloudflare Dashboard → My Profile → API Tokens
2. "Create Token" → "Custom Token"
3. Permissions:
   - Zone - Zone - Read
   - Zone - Firewall Services - Edit
   - Zone - Analytics - Read
   - Zone - Cache Purge - Purge
   - Zone - Rate Limits - Edit
4. Zone Resources: Include - Specific zone - `kargo.entegrehub.com`
5. Copy token

### 2. Zone ID Bulma

1. Cloudflare Dashboard → Select your domain
2. Overview sayfasında sağ tarafta "Zone ID" bulunur
3. Copy Zone ID

### 3. .env Yapılandırması

```env
CLOUDFLARE_ENABLED=true
CLOUDFLARE_API_TOKEN=your_api_token_here
CLOUDFLARE_ZONE_ID=your_zone_id_here
CLOUDFLARE_EMAIL=your_cloudflare_email@example.com
```

## Kullanım

### CLI Commands

```bash
# Analytics görüntüle
php bin/console app:cloudflare analytics --since=-7d

# Security events
php bin/console app:cloudflare security-events

# Firewall kurallarını listele
php bin/console app:cloudflare firewall-list

# IP engelle
php bin/console app:cloudflare block-ip --ip=123.456.789.0 --mode=block --notes="Suspicious activity"

# IP engelini kaldır
php bin/console app:cloudflare unblock-ip --rule-id=abc123

# Cache temizle
php bin/console app:cloudflare purge-cache

# Under Attack mode
php bin/console app:cloudflare under-attack-on
php bin/console app:cloudflare under-attack-off

# Rate limit kuralları
php bin/console app:cloudflare rate-limits

# Login protection kur
php bin/console app:cloudflare setup-login-protection
```

### Admin Panel

**URL:** `/admin/cloudflare`

#### Endpoints

```
GET  /admin/cloudflare                    - Dashboard
GET  /admin/cloudflare/analytics          - Analytics data
GET  /admin/cloudflare/security-events    - Security events
GET  /admin/cloudflare/firewall/rules     - List firewall rules
POST /admin/cloudflare/firewall/rules     - Add firewall rule
DELETE /admin/cloudflare/firewall/rules/{id} - Delete rule
GET  /admin/cloudflare/rate-limits        - List rate limits
POST /admin/cloudflare/rate-limits        - Create rate limit
DELETE /admin/cloudflare/rate-limits/{id} - Delete rate limit
POST /admin/cloudflare/cache/purge        - Purge cache
POST /admin/cloudflare/security-level     - Set security level
POST /admin/cloudflare/under-attack/enable  - Enable Under Attack
POST /admin/cloudflare/under-attack/disable - Disable Under Attack
POST /admin/cloudflare/block-country      - Block country
POST /admin/cloudflare/quick-actions/block-ip - Quick block IP
```

### PHP Kod Kullanımı

```php
// Controller'da CloudflareService inject et
public function __construct(
    private CloudflareService $cloudflareService
) {}

// Real IP al
$realIp = $this->cloudflareService->getRealIpAddress($request);

// Visitor bilgisi
$visitorInfo = $this->cloudflareService->getVisitorInfo($request);

// IP engelle
$result = $this->cloudflareService->addFirewallRule('1.2.3.4', 'block', 'Spam bot');

// Cache temizle
$result = $this->cloudflareService->purgeCache([
    'https://kargo.entegrehub.com/page1',
    'https://kargo.entegrehub.com/page2',
]);

// Under Attack mode
$this->cloudflareService->enableUnderAttackMode();
$this->cloudflareService->disableUnderAttackMode();

// Analytics
$analytics = $this->cloudflareService->getAnalytics('-7d', 'now');

// Security events
$events = $this->cloudflareService->getSecurityEvents(100);
```

## Rate Limiting

### Otomatik Login Protection

```php
// 5 başarısız giriş denemesi = 15 dakika challenge
php bin/console app:cloudflare setup-login-protection
```

### Custom Rate Limit

```php
// API endpoint için rate limit
$rateLimiter->createApiRateLimit('/api/orders', 60, 3600);

// Config ile
$rateLimiter->createRateLimit([
    'threshold' => 100,
    'period' => 60,
    'action' => [
        'mode' => 'ban',
        'timeout' => 3600,
    ],
    'match' => [
        'request' => [
            'url' => '*/api/*',
        ],
    ],
]);
```

## Security Levels

- `off` - No security
- `essentially_off` - Minimal security
- `low` - Low security
- `medium` - **Default** - Balanced security
- `high` - High security - May challenge more visitors
- `under_attack` - Maximum security - All visitors see challenge

## Firewall Modes

- `block` - Block completely
- `challenge` - CAPTCHA challenge
- `js_challenge` - JavaScript challenge
- `whitelist` - Allow always
- `managed_challenge` - Cloudflare managed challenge

## Event Listener

`CloudflareRequestListener` her request'te otomatik çalışır:

- Real IP detection
- Visitor info extraction
- Suspicious pattern detection
- Security logging

## Country Blocking

```php
// Belirli ülkeden gelen trafiği engelle
$this->cloudflareService->blockCountry('CN'); // China
$this->cloudflareService->blockCountry('RU'); // Russia
```

## Best Practices

### 1. Production'da Açılması Gerekenler
```env
CLOUDFLARE_ENABLED=true
RATE_LIMIT_ENABLED=true
```

### 2. Cache Strategy
- Static assets için: 1 ay
- API responses için: 5 dakika
- Dynamic pages için: Cache bypass

### 3. Rate Limiting
- Public API: 60 req/min per IP
- Authenticated API: 200 req/min per user
- Login attempts: 5 req/min per IP

### 4. Security
- Failed login → Challenge after 3 attempts
- API abuse → Auto-block after threshold
- Known attack patterns → Auto-block

### 5. Monitoring
- Daily analytics check
- Weekly security event review
- Monthly firewall rule audit

## Troubleshooting

### Real IP görünmüyor
```php
// Request'ten kontrol et
$realIp = $request->attributes->get('cloudflare_real_ip');
```

### Rate limit çalışmıyor
1. CLOUDFLARE_ENABLED=true mi?
2. API token geçerli mi?
3. Zone ID doğru mu?

### Cache temizlenmiyor
- API token'da "Cache Purge" yetkisi var mı?
- Zone ID doğru mu?

## İleri Seviye

### Custom Firewall Rules

```php
// Expression based rule
POST /api/v4/zones/{zone_id}/firewall/rules
{
  "filter": {
    "expression": "(ip.geoip.country eq \"CN\") or (http.user_agent contains \"bot\")"
  },
  "action": "block"
}
```

### Page Rules ile Entegrasyon

Cloudflare Dashboard'dan:
- `/api/*` → Cache Level: Bypass
- `/static/*` → Cache Level: Cache Everything
- `/admin/*` → Security Level: High

## Support

- Documentation: https://developers.cloudflare.com/api/
- Cloudflare Status: https://www.cloudflarestatus.com/
