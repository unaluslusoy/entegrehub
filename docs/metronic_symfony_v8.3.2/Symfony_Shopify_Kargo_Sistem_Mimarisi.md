# SHOPİFY KARGO ENTEGRASYON SİSTEMİ
## Symfony 7.x + Metronic 8 - Teknik Mimari Dokümantasyonu

---

## 📚 İÇİNDEKİLER

1. [Teknoloji Stack](#teknoloji-stack)
2. [Proje Yapısı](#proje-yapısı)
3. [Metronic 8 Entegrasyonu](#metronic-8-entegrasyonu)
4. [Çoklu Dil Sistemi](#çoklu-dil-sistemi)
5. [Modül Yapısı](#modül-yapısı)
6. [API Mimarisi](#api-mimarisi)
7. [Database Şeması](#database-şeması)
8. [Shopify Entegrasyonu](#shopify-entegrasyonu)
9. [Güvenlik Yapısı](#güvenlik-yapısı)
10. [Deployment & DevOps](#deployment-devops)

---

## 🛠️ TEKNOLOJİ STACK

### Backend Stack
```yaml
Framework: Symfony 7.x
PHP Version: 8.2+
Database: 
  - MySQL 8.0+ (Primary)
  - Redis 7.0+ (Cache, Queue, Session)
Message Queue: Symfony Messenger + Redis Transport
API: API Platform (REST + GraphQL)
Authentication: 
  - JWT (Lexik JWT Bundle)
  - OAuth 2.0 (Shopify App Auth)
Real-time: Mercure Hub
Job Scheduler: Symfony Scheduler Component
Testing: PHPUnit + Behat
```

### Frontend Stack
```yaml
Admin Panel: 
  - Metronic 8 (Symfony Integration)
  - Webpack Encore
  - jQuery 3.7+
  - Bootstrap 5.3
  - KTDataTables
  - ApexCharts
Asset Management: Symfony Webpack Encore
Template Engine: Twig 3.x
JavaScript: 
  - Vanilla JS
  - Alpine.js (for reactivity)
  - HTMX (optional for dynamic updates)
Barcode: 
  - QuaggaJS (barcode scanning)
  - JsBarcode (barcode generation)
```

### Çoklu Dil Sistemi
```yaml
Translation: Symfony Translation Component
Default Languages: TR, EN
Translation Format: YAML
Fallback: TR (Turkish)
User Detection: Browser locale, User preference
Admin: Language switcher in header
```

### 3. Parti Entegrasyonlar
```yaml
Shopify: 
  - Shopify API (REST + GraphQL)
  - Shopify Webhooks
  - Shopify App Bridge
Kargo Firmaları:
  - Yurtiçi Kargo API
  - MNG Kargo API
  - Sürat Kargo API
  - Aras Kargo API
  - PTT Kargo API
  - UPS API
  - Sendeo API
  - Hepsijet API
SMS Provider: 
  - Netgsm
  - İleti Merkezi
Email: 
  - Symfony Mailer
  - AWS SES / Mailgun
Payment: 
  - İyzico (for subscription)
E-Fatura: 
  - Gelecek özellik
  - Logo Tiger entegrasyonu
```

---

## 📁 PROJE YAPISI

### Symfony Bundle Yapısı

```
shopify-cargo-integration/
│
├── config/                          # Symfony configurations
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── messenger.yaml
│   │   ├── translation.yaml
│   │   ├── lexik_jwt_authentication.yaml
│   │   ├── api_platform.yaml
│   │   └── metronic.yaml
│   ├── routes/
│   │   ├── api.yaml                # API routes
│   │   ├── admin.yaml              # Admin panel routes
│   │   └── webhook.yaml            # Webhook routes
│   └── services.yaml
│
├── src/
│   ├── Controller/
│   │   ├── Admin/                  # Admin Panel Controllers
│   │   │   ├── DashboardController.php
│   │   │   ├── OrderController.php
│   │   │   ├── ShipmentController.php
│   │   │   ├── CargoController.php
│   │   │   ├── ReportController.php
│   │   │   ├── SettingsController.php
│   │   │   ├── UserController.php
│   │   │   └── LanguageController.php
│   │   ├── Api/                    # REST API Controllers
│   │   │   ├── V1/
│   │   │   │   ├── OrderApiController.php
│   │   │   │   ├── ShipmentApiController.php
│   │   │   │   ├── TrackingApiController.php
│   │   │   │   ├── CargoApiController.php
│   │   │   │   └── WebhookApiController.php
│   │   │   └── GraphQL/            # GraphQL resolvers (optional)
│   │   └── Webhook/                # Webhook handlers
│   │       ├── ShopifyWebhookController.php
│   │       └── CargoWebhookController.php
│   │
│   ├── Entity/                     # Doctrine Entities
│   │   ├── User.php
│   │   ├── Shop.php                # Shopify shop info
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Shipment.php
│   │   ├── ShipmentTracking.php
│   │   ├── CargoCompany.php
│   │   ├── CargoLabel.php
│   │   ├── Address.php
│   │   ├── Warehouse.php
│   │   ├── Notification.php
│   │   ├── AuditLog.php
│   │   └── Setting.php
│   │
│   ├── Repository/                 # Doctrine Repositories
│   │   ├── OrderRepository.php
│   │   ├── ShipmentRepository.php
│   │   ├── CargoCompanyRepository.php
│   │   └── ...
│   │
│   ├── Service/                    # Business Logic
│   │   ├── Shopify/
│   │   │   ├── ShopifyAuthService.php
│   │   │   ├── ShopifyOrderService.php
│   │   │   ├── ShopifyWebhookService.php
│   │   │   └── ShopifyApiClient.php
│   │   ├── Cargo/
│   │   │   ├── CargoFactoryService.php
│   │   │   ├── YurticiCargoService.php
│   │   │   ├── MNGCargoService.php
│   │   │   ├── SuratCargoService.php
│   │   │   ├── ArasCargoService.php
│   │   │   └── CargoLabelGenerator.php
│   │   ├── Order/
│   │   │   ├── OrderProcessingService.php
│   │   │   ├── OrderSplitService.php
│   │   │   ├── OrderFilterService.php
│   │   │   └── BulkOrderService.php
│   │   ├── Shipment/
│   │   │   ├── ShipmentCreationService.php
│   │   │   ├── TrackingService.php
│   │   │   ├── BulkShipmentService.php
│   │   │   └── ShipmentReportService.php
│   │   ├── Notification/
│   │   │   ├── EmailNotificationService.php
│   │   │   ├── SmsNotificationService.php
│   │   │   ├── PushNotificationService.php
│   │   │   └── WhatsAppService.php
│   │   ├── Translation/
│   │   │   └── TranslationService.php
│   │   ├── AI/
│   │   │   ├── AddressValidationService.php
│   │   │   └── PredictiveAnalyticsService.php
│   │   └── Report/
│   │       ├── OrderReportService.php
│   │       ├── ShipmentReportService.php
│   │       ├── FinancialReportService.php
│   │       └── PerformanceReportService.php
│   │
│   ├── MessageHandler/             # Async Job Handlers
│   │   ├── CreateShipmentHandler.php
│   │   ├── BulkShipmentHandler.php
│   │   ├── TrackingUpdateHandler.php
│   │   ├── NotificationHandler.php
│   │   └── WebhookProcessHandler.php
│   │
│   ├── Message/                    # Async Messages
│   │   ├── CreateShipmentMessage.php
│   │   ├── BulkShipmentMessage.php
│   │   ├── TrackingUpdateMessage.php
│   │   └── NotificationMessage.php
│   │
│   ├── EventSubscriber/            # Event Listeners
│   │   ├── OrderCreatedSubscriber.php
│   │   ├── ShipmentCreatedSubscriber.php
│   │   ├── LocaleSubscriber.php    # Language switching
│   │   └── AuditLogSubscriber.php
│   │
│   ├── Security/                   # Security
│   │   ├── ShopifyAuthenticator.php
│   │   ├── ApiKeyAuthenticator.php
│   │   └── Voter/
│   │       ├── OrderVoter.php
│   │       └── ShipmentVoter.php
│   │
│   ├── Form/                       # Symfony Forms
│   │   ├── OrderType.php
│   │   ├── ShipmentType.php
│   │   ├── CargoCompanyType.php
│   │   ├── SettingsType.php
│   │   └── UserType.php
│   │
│   ├── DataFixtures/               # Test data
│   │   └── AppFixtures.php
│   │
│   ├── Command/                    # Console commands
│   │   ├── TrackingUpdateCommand.php
│   │   ├── OrderSyncCommand.php
│   │   └── ReportGeneratorCommand.php
│   │
│   └── Kernel.php
│
├── templates/
│   ├── admin/                      # Metronic 8 templates
│   │   ├── base.html.twig         # Base layout
│   │   ├── dashboard/
│   │   │   └── index.html.twig
│   │   ├── order/
│   │   │   ├── list.html.twig
│   │   │   ├── detail.html.twig
│   │   │   └── filter.html.twig
│   │   ├── shipment/
│   │   │   ├── list.html.twig
│   │   │   ├── create.html.twig
│   │   │   ├── bulk.html.twig
│   │   │   └── label.html.twig
│   │   ├── cargo/
│   │   │   ├── companies.html.twig
│   │   │   └── settings.html.twig
│   │   ├── report/
│   │   │   ├── orders.html.twig
│   │   │   ├── shipments.html.twig
│   │   │   └── financial.html.twig
│   │   ├── barcode/
│   │   │   └── scanner.html.twig
│   │   └── settings/
│   │       ├── general.html.twig
│   │       ├── warehouse.html.twig
│   │       └── notifications.html.twig
│   ├── tracking/                   # Customer tracking page
│   │   └── track.html.twig
│   └── email/                      # Email templates
│       ├── shipment_created.html.twig
│       ├── tracking_update.html.twig
│       └── delivery_completed.html.twig
│
├── translations/                   # Çoklu dil dosyaları
│   ├── messages.tr.yaml
│   ├── messages.en.yaml
│   ├── validators.tr.yaml
│   └── validators.en.yaml
│
├── public/
│   ├── metronic/                   # Metronic 8 assets
│   │   ├── assets/
│   │   ├── plugins/
│   │   └── media/
│   └── build/                      # Webpack Encore output
│
├── assets/
│   ├── admin/                      # Custom admin assets
│   │   ├── js/
│   │   │   ├── app.js
│   │   │   ├── order-management.js
│   │   │   ├── shipment-bulk.js
│   │   │   ├── barcode-scanner.js
│   │   │   └── translation.js
│   │   └── scss/
│   │       ├── custom.scss
│   │       └── _variables.scss
│   └── tracking/                   # Customer tracking assets
│       ├── js/
│       └── scss/
│
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Functional/
│
├── migrations/                     # Database migrations
│
├── var/
│   ├── cache/
│   ├── log/
│   └── sessions/
│
├── .env
├── .env.local
├── composer.json
├── package.json
├── webpack.config.js
└── docker-compose.yml
```

---

## 🎨 METRONIC 8 ENTEGRASYONU

### 1. Metronic 8 Kurulumu

#### composer.json Dependencies
```json
{
    "require": {
        "php": ">=8.2",
        "ext-ctype": "*",
        "ext-iconv": "*",
        "api-platform/core": "^3.2",
        "doctrine/doctrine-bundle": "^2.11",
        "doctrine/doctrine-migrations-bundle": "^3.3",
        "doctrine/orm": "^2.17",
        "lexik/jwt-authentication-bundle": "^2.20",
        "predis/predis": "^2.2",
        "symfony/console": "7.0.*",
        "symfony/dotenv": "7.0.*",
        "symfony/flex": "^2",
        "symfony/framework-bundle": "7.0.*",
        "symfony/http-client": "7.0.*",
        "symfony/mailer": "7.0.*",
        "symfony/messenger": "7.0.*",
        "symfony/monolog-bundle": "^3.10",
        "symfony/runtime": "7.0.*",
        "symfony/scheduler": "7.0.*",
        "symfony/security-bundle": "7.0.*",
        "symfony/translation": "7.0.*",
        "symfony/twig-bundle": "7.0.*",
        "symfony/validator": "7.0.*",
        "symfony/webpack-encore-bundle": "^2.1",
        "symfony/yaml": "7.0.*",
        "twig/extra-bundle": "^2.12|^3.0",
        "twig/twig": "^2.12|^3.0"
    }
}
```

#### package.json (Webpack Encore)
```json
{
    "devDependencies": {
        "@symfony/webpack-encore": "^4.6",
        "webpack": "^5.88.0",
        "webpack-cli": "^5.1.0",
        "sass": "^1.69.5",
        "sass-loader": "^13.3.2"
    },
    "dependencies": {
        "jquery": "^3.7.1",
        "bootstrap": "^5.3.2",
        "apexcharts": "^3.44.0",
        "sweetalert2": "^11.10.1",
        "toastr": "^2.1.4",
        "quagga": "^0.12.1",
        "jsbarcode": "^3.11.6"
    }
}
```

### 2. Metronic 8 Layout Entegrasyonu

#### templates/admin/base.html.twig
```twig
<!DOCTYPE html>
<html lang="{{ app.request.locale }}">
<head>
    <meta charset="utf-8" />
    <title>{% block title %}{{ 'app.title'|trans }}{% endblock %}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    
    {# Metronic Fonts #}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    
    {# Metronic Global CSS #}
    {% block stylesheets %}
        <link href="{{ asset('metronic/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" />
        <link href="{{ asset('metronic/assets/css/style.bundle.css') }}" rel="stylesheet" />
        {{ encore_entry_link_tags('admin') }}
    {% endblock %}
    
    {# Dark Mode Support #}
    <script>
        var defaultThemeMode = "light";
        var themeMode = localStorage.getItem("kt_theme_mode_value") || defaultThemeMode;
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    </script>
</head>

<body id="kt_app_body" data-kt-app-header-fixed="true" data-kt-app-sidebar-fixed="true" class="app-default">
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            
            {# Header #}
            {% include 'admin/layout/_header.html.twig' %}
            
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                
                {# Sidebar #}
                {% include 'admin/layout/_sidebar.html.twig' %}
                
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    
                    {# Content #}
                    <div class="d-flex flex-column flex-column-fluid">
                        
                        {# Toolbar #}
                        {% include 'admin/layout/_toolbar.html.twig' %}
                        
                        {# Content #}
                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                
                                {# Flash Messages #}
                                {% for label, messages in app.flashes %}
                                    {% for message in messages %}
                                        <div class="alert alert-{{ label }} alert-dismissible fade show" role="alert">
                                            {{ message|trans }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    {% endfor %}
                                {% endfor %}
                                
                                {# Page Content #}
                                {% block content %}{% endblock %}
                                
                            </div>
                        </div>
                    </div>
                    
                    {# Footer #}
                    {% include 'admin/layout/_footer.html.twig' %}
                    
                </div>
            </div>
        </div>
    </div>
    
    {# Metronic Global JS #}
    {% block javascripts %}
        <script src="{{ asset('metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
        <script src="{{ asset('metronic/assets/js/scripts.bundle.js') }}"></script>
        {{ encore_entry_script_tags('admin') }}
    {% endblock %}
    
    {# Page specific scripts #}
    {% block page_scripts %}{% endblock %}
</body>
</html>
```

#### templates/admin/layout/_header.html.twig
```twig
<div id="kt_app_header" class="app-header">
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between">
        
        {# Logo #}
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15">
            <a href="{{ path('admin_dashboard') }}">
                <img alt="Logo" src="{{ asset('media/logos/logo.svg') }}" class="h-25px" />
            </a>
        </div>
        
        {# Navbar #}
        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">
            
            {# Menu Wrapper #}
            <div class="d-flex align-items-stretch" id="kt_app_header_menu_wrapper">
                {# Menu here if needed #}
            </div>
            
            {# Navbar Right #}
            <div class="app-navbar flex-shrink-0">
                
                {# Language Switcher #}
                <div class="app-navbar-item ms-1 ms-md-3">
                    <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px" 
                         data-kt-menu-trigger="{default: 'click', lg: 'hover'}" 
                         data-kt-menu-attach="parent" 
                         data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-abstract-9 fs-2"></i>
                    </div>
                    
                    {# Language Menu #}
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-4" 
                         data-kt-menu="true">
                        <div class="menu-item px-3">
                            <a href="{{ path('admin_change_locale', {locale: 'tr'}) }}" class="menu-link d-flex px-5">
                                <span class="symbol symbol-20px me-4">
                                    <img class="rounded-1" src="{{ asset('media/flags/turkey.svg') }}" alt="Turkish" />
                                </span>
                                {{ 'language.turkish'|trans }}
                            </a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="{{ path('admin_change_locale', {locale: 'en'}) }}" class="menu-link d-flex px-5">
                                <span class="symbol symbol-20px me-4">
                                    <img class="rounded-1" src="{{ asset('media/flags/united-states.svg') }}" alt="English" />
                                </span>
                                {{ 'language.english'|trans }}
                            </a>
                        </div>
                    </div>
                </div>
                
                {# Theme Mode Toggle #}
                <div class="app-navbar-item ms-1 ms-md-3">
                    <a href="#" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px" 
                       data-kt-menu-trigger="{default:'click', lg: 'hover'}" 
                       data-kt-menu-attach="parent" 
                       data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-night-day theme-light-show fs-2"></i>
                        <i class="ki-outline ki-moon theme-dark-show fs-2"></i>
                    </a>
                </div>
                
                {# Notifications #}
                <div class="app-navbar-item ms-1 ms-md-3">
                    <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px position-relative" 
                         data-kt-menu-trigger="{default: 'click', lg: 'hover'}" 
                         data-kt-menu-attach="parent" 
                         data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-notification-on fs-2"></i>
                        <span class="badge badge-circle badge-danger position-absolute top-0 start-100 translate-middle">3</span>
                    </div>
                </div>
                
                {# User Menu #}
                <div class="app-navbar-item ms-1 ms-md-3">
                    <div class="cursor-pointer symbol symbol-35px symbol-md-40px" 
                         data-kt-menu-trigger="{default: 'click', lg: 'hover'}" 
                         data-kt-menu-attach="parent" 
                         data-kt-menu-placement="bottom-end">
                        <img src="{{ asset('media/avatars/blank.png') }}" alt="user" />
                    </div>
                    
                    {# User account menu #}
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" 
                         data-kt-menu="true">
                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">
                                <div class="symbol symbol-50px me-5">
                                    <img alt="Logo" src="{{ asset('media/avatars/blank.png') }}" />
                                </div>
                                <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center fs-5">
                                        {{ app.user.fullName }}
                                    </div>
                                    <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">
                                        {{ app.user.email }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="separator my-2"></div>
                        
                        <div class="menu-item px-5">
                            <a href="{{ path('admin_profile') }}" class="menu-link px-5">
                                {{ 'menu.my_profile'|trans }}
                            </a>
                        </div>
                        
                        <div class="menu-item px-5">
                            <a href="{{ path('admin_settings') }}" class="menu-link px-5">
                                {{ 'menu.settings'|trans }}
                            </a>
                        </div>
                        
                        <div class="separator my-2"></div>
                        
                        <div class="menu-item px-5">
                            <a href="{{ path('app_logout') }}" class="menu-link px-5">
                                {{ 'menu.sign_out'|trans }}
                            </a>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
```

#### templates/admin/layout/_sidebar.html.twig
```twig
<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar">
    
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5">
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="kt_app_sidebar_menu">
                
                {# Dashboard #}
                <div class="menu-item">
                    <a class="menu-link {{ app.request.get('_route') == 'admin_dashboard' ? 'active' : '' }}" 
                       href="{{ path('admin_dashboard') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-element-11 fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.dashboard'|trans }}</span>
                    </a>
                </div>
                
                {# Orders #}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">{{ 'menu.orders'|trans }}</span>
                    </div>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_orders') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-basket fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.all_orders'|trans }}</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_orders_cod') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-dollar fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.cod_orders'|trans }}</span>
                    </a>
                </div>
                
                {# Shipments #}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">{{ 'menu.shipments'|trans }}</span>
                    </div>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_shipments') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-delivery fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.all_shipments'|trans }}</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_shipments_create') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-plus-square fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.create_shipment'|trans }}</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_shipments_bulk') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-package fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.bulk_shipments'|trans }}</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_tracking') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-geolocation fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.tracking'|trans }}</span>
                    </a>
                </div>
                
                {# Barcode Scanner #}
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_barcode_scanner') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-barcode fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.barcode_scanner'|trans }}</span>
                    </a>
                </div>
                
                {# Cargo #}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">{{ 'menu.cargo'|trans }}</span>
                    </div>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_cargo_companies') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-truck fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.cargo_companies'|trans }}</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_cargo_labels') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-tag fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.label_designer'|trans }}</span>
                    </a>
                </div>
                
                {# Reports #}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">{{ 'menu.reports'|trans }}</span>
                    </div>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_reports') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-chart-simple fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.all_reports'|trans }}</span>
                    </a>
                </div>
                
                {# Settings #}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">{{ 'menu.system'|trans }}</span>
                    </div>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_warehouses') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-home-2 fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.warehouses'|trans }}</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_users') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-people fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.users'|trans }}</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a class="menu-link" href="{{ path('admin_settings') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-setting-2 fs-2"></i>
                        </span>
                        <span class="menu-title">{{ 'menu.settings'|trans }}</span>
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
</div>
```

---

## 🌍 ÇOKLU DİL SİSTEMİ

### 1. Translation Configuration

#### config/packages/translation.yaml
```yaml
framework:
    default_locale: tr
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks:
            - tr
        providers:
            database:
                dsn: 'doctrine://default'
```

### 2. Translation Files

#### translations/messages.tr.yaml
```yaml
app:
    title: 'Shopify Kargo Entegrasyonu'
    welcome: 'Hoş Geldiniz'

menu:
    dashboard: 'Kontrol Paneli'
    orders: 'Siparişler'
    all_orders: 'Tüm Siparişler'
    cod_orders: 'Kapıda Ödeme Siparişleri'
    shipments: 'Gönderiler'
    all_shipments: 'Tüm Gönderiler'
    create_shipment: 'Gönderi Oluştur'
    bulk_shipments: 'Toplu Gönderi'
    tracking: 'Kargo Takip'
    barcode_scanner: 'Barkod Okuyucu'
    cargo: 'Kargo'
    cargo_companies: 'Kargo Firmaları'
    label_designer: 'Etiket Tasarımcısı'
    reports: 'Raporlar'
    all_reports: 'Tüm Raporlar'
    system: 'Sistem'
    warehouses: 'Depolar'
    users: 'Kullanıcılar'
    settings: 'Ayarlar'
    my_profile: 'Profilim'
    sign_out: 'Çıkış Yap'

language:
    turkish: 'Türkçe'
    english: 'English'

order:
    list_title: 'Sipariş Listesi'
    order_number: 'Sipariş No'
    customer: 'Müşteri'
    date: 'Tarih'
    total: 'Toplam'
    status: 'Durum'
    payment_method: 'Ödeme Yöntemi'
    actions: 'İşlemler'
    create_shipment: 'Gönderi Oluştur'
    view_details: 'Detayları Gör'
    
shipment:
    list_title: 'Gönderi Listesi'
    tracking_number: 'Takip No'
    cargo_company: 'Kargo Firması'
    create_date: 'Oluşturma Tarihi'
    status: 'Durum'
    print_label: 'Etiket Yazdır'
    track: 'Takip Et'
    
status:
    pending: 'Beklemede'
    processing: 'İşleniyor'
    shipped: 'Kargoya Verildi'
    delivered: 'Teslim Edildi'
    cancelled: 'İptal Edildi'
    
payment:
    cod_cash: 'Kapıda Nakit'
    cod_credit: 'Kapıda Kredi Kartı'
    online: 'Online Ödeme'
    
button:
    save: 'Kaydet'
    cancel: 'İptal'
    delete: 'Sil'
    edit: 'Düzenle'
    filter: 'Filtrele'
    export: 'Dışa Aktar'
    print: 'Yazdır'
    bulk_action: 'Toplu İşlem'
```

#### translations/messages.en.yaml
```yaml
app:
    title: 'Shopify Cargo Integration'
    welcome: 'Welcome'

menu:
    dashboard: 'Dashboard'
    orders: 'Orders'
    all_orders: 'All Orders'
    cod_orders: 'COD Orders'
    shipments: 'Shipments'
    all_shipments: 'All Shipments'
    create_shipment: 'Create Shipment'
    bulk_shipments: 'Bulk Shipments'
    tracking: 'Tracking'
    barcode_scanner: 'Barcode Scanner'
    cargo: 'Cargo'
    cargo_companies: 'Cargo Companies'
    label_designer: 'Label Designer'
    reports: 'Reports'
    all_reports: 'All Reports'
    system: 'System'
    warehouses: 'Warehouses'
    users: 'Users'
    settings: 'Settings'
    my_profile: 'My Profile'
    sign_out: 'Sign Out'

language:
    turkish: 'Türkçe'
    english: 'English'

order:
    list_title: 'Order List'
    order_number: 'Order #'
    customer: 'Customer'
    date: 'Date'
    total: 'Total'
    status: 'Status'
    payment_method: 'Payment Method'
    actions: 'Actions'
    create_shipment: 'Create Shipment'
    view_details: 'View Details'
    
shipment:
    list_title: 'Shipment List'
    tracking_number: 'Tracking #'
    cargo_company: 'Cargo Company'
    create_date: 'Created Date'
    status: 'Status'
    print_label: 'Print Label'
    track: 'Track'
    
status:
    pending: 'Pending'
    processing: 'Processing'
    shipped: 'Shipped'
    delivered: 'Delivered'
    cancelled: 'Cancelled'
    
payment:
    cod_cash: 'Cash on Delivery'
    cod_credit: 'Credit Card on Delivery'
    online: 'Online Payment'
    
button:
    save: 'Save'
    cancel: 'Cancel'
    delete: 'Delete'
    edit: 'Edit'
    filter: 'Filter'
    export: 'Export'
    print: 'Print'
    bulk_action: 'Bulk Action'
```

### 3. Locale EventSubscriber

#### src/EventSubscriber/LocaleSubscriber.php
```php
<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'tr')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Try to get locale from session
        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
```

### 4. Language Controller

#### src/Controller/Admin/LanguageController.php
```php
<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class LanguageController extends AbstractController
{
    #[Route('/change-locale/{locale}', name: 'admin_change_locale')]
    public function changeLocale(string $locale, Request $request): Response
    {
        // Store locale in session
        $request->getSession()->set('_locale', $locale);
        
        // Redirect back to previous page
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('admin_dashboard');
    }
}
```

### 5. Twig Translation Extension

#### assets/admin/js/translation.js
```javascript
// Client-side translation helper
class TranslationManager {
    constructor(locale) {
        this.locale = locale;
        this.translations = {};
    }
    
    async loadTranslations() {
        try {
            const response = await fetch(`/api/translations/${this.locale}`);
            this.translations = await response.json();
        } catch (error) {
            console.error('Failed to load translations:', error);
        }
    }
    
    trans(key, parameters = {}) {
        let translation = this.translations[key] || key;
        
        // Replace parameters
        Object.keys(parameters).forEach(param => {
            translation = translation.replace(`{${param}}`, parameters[param]);
        });
        
        return translation;
    }
}

// Initialize
const translator = new TranslationManager(document.documentElement.lang);
translator.loadTranslations();
```

---

## 📊 DATABASE ŞEMASI

### Entity İlişkileri

```
User (Kullanıcılar)
├── Shop (Shopify Mağazalar)
│   ├── Orders (Siparişler)
│   │   ├── OrderItems (Sipariş Kalemleri)
│   │   ├── Shipments (Gönderiler)
│   │   │   ├── ShipmentTracking (Takip Kayıtları)
│   │   │   └── CargoLabels (Kargo Etiketleri)
│   │   └── Address (Teslimat Adresi)
│   ├── Warehouses (Depolar)
│   └── Settings (Ayarlar)
├── CargoCompany (Kargo Firmaları)
├── Notifications (Bildirimler)
└── AuditLogs (İşlem Kayıtları)
```

### Ana Entity'ler

#### src/Entity/User.php
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 100)]
    private string $fullName;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $locale = 'tr';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Shop::class)]
    private Collection $shops;

    // Getters and setters...
}
```

#### src/Entity/Shop.php
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shops')]
class Shop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'shops')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255)]
    private string $shopifyDomain;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $shopifyShopId;

    #[ORM\Column(type: 'string', length: 500)]
    private string $accessToken;

    #[ORM\Column(type: 'string', length: 100)]
    private string $shopName;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $currency = 'TRY';

    #[ORM\Column(type: 'string', length: 10)]
    private string $locale = 'tr';

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $installedAt;

    #[ORM\OneToMany(mappedBy: 'shop', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\OneToMany(mappedBy: 'shop', targetEntity: Warehouse::class)]
    private Collection $warehouses;

    // Getters and setters...
}
```

#### src/Entity/Order.php
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\Index(columns: ['shopify_order_id'])]
#[ORM\Index(columns: ['order_number'])]
#[ORM\Index(columns: ['status'])]
#[ORM\Index(columns: ['payment_method'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Shop::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private Shop $shop;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $shopifyOrderId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $orderNumber;

    #[ORM\Column(type: 'string', length: 100)]
    private string $customerName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $customerEmail;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $customerPhone;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $totalPrice;

    #[ORM\Column(type: 'string', length: 50)]
    private string $currency = 'TRY';

    #[ORM\Column(type: 'string', length: 50)]
    private string $paymentMethod; // cod_cash, cod_credit, online

    #[ORM\Column(type: 'string', length: 50)]
    private string $status; // pending, processing, shipped, delivered, cancelled

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(type: 'json')]
    private array $shopifyData = []; // Raw Shopify order data

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $orderDate;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'])]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: Shipment::class)]
    private Collection $shipments;

    #[ORM\OneToOne(mappedBy: 'order', targetEntity: Address::class, cascade: ['persist', 'remove'])]
    private ?Address $shippingAddress;

    // Getters and setters...
}
```

#### src/Entity/OrderItem.php
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\Column(type: 'string', length: 100)]
    private string $shopifyProductId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $shopifyVariantId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $productName;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $sku;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $barcode;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3, nullable: true)]
    private ?string $weight; // in kg

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $variant = null; // color, size, etc.

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $imageUrl;

    // Getters and setters...
}
```

#### src/Entity/Shipment.php
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShipmentRepository::class)]
#[ORM\Table(name: 'shipments')]
#[ORM\Index(columns: ['tracking_number'])]
#[ORM\Index(columns: ['status'])]
class Shipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'shipments')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\ManyToOne(targetEntity: CargoCompany::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CargoCompany $cargoCompany;

    #[ORM\ManyToOne(targetEntity: Warehouse::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Warehouse $warehouse;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $trackingNumber;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status; // created, in_transit, delivered, returned

    #[ORM\Column(type: 'json')]
    private array $items = []; // Which order items in this shipment

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3, nullable: true)]
    private ?string $totalWeight;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $packageCount = 1;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $shippingCost;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $shippedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveredAt;

    #[ORM\OneToMany(mappedBy: 'shipment', targetEntity: ShipmentTracking::class, cascade: ['persist'])]
    private Collection $trackingHistory;

    #[ORM\OneToOne(mappedBy: 'shipment', targetEntity: CargoLabel::class, cascade: ['persist', 'remove'])]
    private ?CargoLabel $label;

    // Getters and setters...
}
```

#### src/Entity/CargoCompany.php
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cargo_companies')]
class CargoCompany
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name; // Yurtiçi, MNG, Sürat, etc.

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $code; // yurtici, mng, surat

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $logo;

    #[ORM\Column(type: 'json')]
    private array $apiCredentials = [];

    #[ORM\Column(type: 'json')]
    private array $settings = [];

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    private int $priority = 0; // For sorting

    // Getters and setters...
}
```

### Migration Örneği

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 🔌 API MİMARİSİ

### API Platform Configuration

#### config/packages/api_platform.yaml
```yaml
api_platform:
    title: 'Shopify Cargo Integration API'
    version: '1.0.0'
    description: 'RESTful API for Shopify Cargo Integration System'
    
    defaults:
        stateless: true
        cache_headers:
            max_age: 0
            shared_max_age: 3600
            vary: ['Accept', 'Accept-Language']
        
    formats:
        jsonld: ['application/ld+json']
        json: ['application/json']
        html: ['text/html']
        
    swagger:
        versions: [3]
        api_keys:
            apiKey:
                name: Authorization
                type: header
                
    graphql:
        enabled: true
        graphiql:
            enabled: true
        graphql_playground:
            enabled: true
```

### API Endpoints

#### Order API
```php
<?php

namespace App\Controller\Api\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/orders', name: 'api_orders_')]
class OrderApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // GET /api/v1/orders?status=pending&payment=cod&page=1&limit=20
        // Returns paginated order list with filters
    }
    
    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        // GET /api/v1/orders/123
        // Returns single order details
    }
    
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // POST /api/v1/orders
        // Creates new order (manual entry)
    }
    
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        // PUT/PATCH /api/v1/orders/123
        // Updates order information
    }
    
    #[Route('/{id}/split', name: 'split', methods: ['POST'])]
    public function split(int $id, Request $request): JsonResponse
    {
        // POST /api/v1/orders/123/split
        // Splits order into multiple shipments
    }
}
```

#### Shipment API
```php
<?php

namespace App\Controller\Api\V1;

#[Route('/api/v1/shipments', name: 'api_shipments_')]
class ShipmentApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // GET /api/v1/shipments?status=in_transit&cargo=yurtici
    }
    
    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        // GET /api/v1/shipments/456
    }
    
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // POST /api/v1/shipments
        // Creates single shipment
    }
    
    #[Route('/bulk', name: 'bulk_create', methods: ['POST'])]
    public function bulkCreate(Request $request): JsonResponse
    {
        // POST /api/v1/shipments/bulk
        // Creates multiple shipments at once
    }
    
    #[Route('/{id}/label', name: 'label', methods: ['GET'])]
    public function getLabel(int $id): Response
    {
        // GET /api/v1/shipments/456/label
        // Returns PDF label
    }
    
    #[Route('/{id}/track', name: 'track', methods: ['GET'])]
    public function track(int $id): JsonResponse
    {
        // GET /api/v1/shipments/456/track
        // Returns tracking history
    }
}
```

#### Webhook API
```php
<?php

namespace App\Controller\Webhook;

#[Route('/webhook', name: 'webhook_')]
class ShopifyWebhookController extends AbstractController
{
    #[Route('/shopify/orders/create', name: 'shopify_order_create', methods: ['POST'])]
    public function orderCreate(Request $request): Response
    {
        // Shopify webhook: order creation
    }
    
    #[Route('/shopify/orders/update', name: 'shopify_order_update', methods: ['POST'])]
    public function orderUpdate(Request $request): Response
    {
        // Shopify webhook: order update
    }
    
    #[Route('/shopify/orders/cancelled', name: 'shopify_order_cancel', methods: ['POST'])]
    public function orderCancel(Request $request): Response
    {
        // Shopify webhook: order cancellation
    }
    
    #[Route('/cargo/{company}/tracking', name: 'cargo_tracking', methods: ['POST'])]
    public function cargoTracking(string $company, Request $request): Response
    {
        // Cargo company webhook: tracking updates
    }
}
```

### API Authentication

#### JWT Configuration

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600 # 1 hour
```

#### API Key Authentication
```php
<?php

namespace App\Security;

use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-KEY');
    }
    
    // Implementation...
}
```

---

Bu ilk bölüm Ünal. Devam edeyim mi? Şu konular kaldı:

1. ✅ Teknoloji Stack
2. ✅ Proje Yapısı
3. ✅ Metronic 8 Entegrasyonu
4. ✅ Çoklu Dil Sistemi
5. ✅ Database Şeması
6. ✅ API Mimarisi
7. ⏳ Modül Detayları (Shopify, Cargo, Order Processing)
8. ⏳ Frontend JavaScript Örnekleri
9. ⏳ Deployment & DevOps

Devam edelim mi?