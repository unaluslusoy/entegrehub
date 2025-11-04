# SHOPİFY KARGO ENTEGRASYON SİSTEMİ - BÖLÜM 2
## Modül Detayları, JavaScript & Deployment

---

## 📦 MODÜL DETAYLARI

### 1. SHOPIFY ENTEGRASYON MODÜLÜ

#### ShopifyAuthService.php
```php
<?php

namespace App\Service\Shopify;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ShopifyAuthService
{
    private const OAUTH_URL = 'https://{shop}/admin/oauth';
    private const API_VERSION = '2024-01';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $apiSecret,
        private string $appUrl
    ) {}
    
    public function getAuthorizationUrl(string $shop, array $scopes): string
    {
        $params = [
            'client_id' => $this->apiKey,
            'scope' => implode(',', $scopes),
            'redirect_uri' => $this->appUrl . '/shopify/callback',
            'state' => bin2hex(random_bytes(16))
        ];
        
        return str_replace('{shop}', $shop, self::OAUTH_URL) . '/authorize?' . http_build_query($params);
    }
    
    public function exchangeCodeForAccessToken(string $shop, string $code): array
    {
        $response = $this->httpClient->request('POST', 
            str_replace('{shop}', $shop, self::OAUTH_URL) . '/access_token',
            [
                'json' => [
                    'client_id' => $this->apiKey,
                    'client_secret' => $this->apiSecret,
                    'code' => $code
                ]
            ]
        );
        
        return $response->toArray();
    }
    
    public function verifyWebhook(Request $request): bool
    {
        $hmacHeader = $request->headers->get('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        
        $calculated = base64_encode(hash_hmac('sha256', $data, $this->apiSecret, true));
        
        return hash_equals($calculated, $hmacHeader);
    }
}
```

#### ShopifyApiClient.php
```php
<?php

namespace App\Service\Shopify;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;

class ShopifyApiClient
{
    private const API_VERSION = '2024-01';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache
    ) {}
    
    public function get(string $shop, string $accessToken, string $endpoint): array
    {
        $url = "https://{$shop}/admin/api/" . self::API_VERSION . "/{$endpoint}";
        
        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);
        
        return $response->toArray();
    }
    
    public function post(string $shop, string $accessToken, string $endpoint, array $data): array
    {
        $url = "https://{$shop}/admin/api/" . self::API_VERSION . "/{$endpoint}";
        
        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json'
            ],
            'json' => $data
        ]);
        
        return $response->toArray();
    }
    
    public function put(string $shop, string $accessToken, string $endpoint, array $data): array
    {
        $url = "https://{$shop}/admin/api/" . self::API_VERSION . "/{$endpoint}";
        
        $response = $this->httpClient->request('PUT', $url, [
            'headers' => [
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json'
            ],
            'json' => $data
        ]);
        
        return $response->toArray();
    }
    
    // GraphQL query method
    public function graphql(string $shop, string $accessToken, string $query, array $variables = []): array
    {
        $url = "https://{$shop}/admin/api/" . self::API_VERSION . "/graphql.json";
        
        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'query' => $query,
                'variables' => $variables
            ]
        ]);
        
        return $response->toArray();
    }
}
```

#### ShopifyOrderService.php
```php
<?php

namespace App\Service\Shopify;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Address;
use App\Entity\Shop;
use Doctrine\ORM\EntityManagerInterface;

class ShopifyOrderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ShopifyApiClient $apiClient
    ) {}
    
    public function importOrder(Shop $shop, array $shopifyOrder): Order
    {
        // Check if order already exists
        $order = $this->em->getRepository(Order::class)
            ->findOneBy(['shopifyOrderId' => $shopifyOrder['id']]);
        
        if ($order) {
            return $this->updateOrder($order, $shopifyOrder);
        }
        
        // Create new order
        $order = new Order();
        $order->setShop($shop);
        $order->setShopifyOrderId($shopifyOrder['id']);
        $order->setOrderNumber($shopifyOrder['order_number']);
        $order->setCustomerName($shopifyOrder['customer']['first_name'] . ' ' . $shopifyOrder['customer']['last_name']);
        $order->setCustomerEmail($shopifyOrder['customer']['email'] ?? null);
        $order->setCustomerPhone($shopifyOrder['customer']['phone'] ?? null);
        $order->setTotalPrice($shopifyOrder['total_price']);
        $order->setCurrency($shopifyOrder['currency']);
        $order->setPaymentMethod($this->determinePaymentMethod($shopifyOrder));
        $order->setStatus('pending');
        $order->setShopifyData($shopifyOrder);
        $order->setOrderDate(new \DateTimeImmutable($shopifyOrder['created_at']));
        $order->setCreatedAt(new \DateTimeImmutable());
        
        // Import order items
        foreach ($shopifyOrder['line_items'] as $lineItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setShopifyProductId($lineItem['product_id']);
            $orderItem->setShopifyVariantId($lineItem['variant_id']);
            $orderItem->setProductName($lineItem['title']);
            $orderItem->setSku($lineItem['sku']);
            $orderItem->setBarcode($lineItem['barcode'] ?? null);
            $orderItem->setQuantity($lineItem['quantity']);
            $orderItem->setPrice($lineItem['price']);
            $orderItem->setWeight($lineItem['grams'] ? $lineItem['grams'] / 1000 : null);
            $orderItem->setVariant($lineItem['variant_title'] ? ['title' => $lineItem['variant_title']] : null);
            $orderItem->setImageUrl($lineItem['image']['src'] ?? null);
            
            $order->addItem($orderItem);
        }
        
        // Import shipping address
        if (isset($shopifyOrder['shipping_address'])) {
            $address = new Address();
            $address->setOrder($order);
            $address->setFirstName($shopifyOrder['shipping_address']['first_name']);
            $address->setLastName($shopifyOrder['shipping_address']['last_name']);
            $address->setCompany($shopifyOrder['shipping_address']['company'] ?? null);
            $address->setAddress1($shopifyOrder['shipping_address']['address1']);
            $address->setAddress2($shopifyOrder['shipping_address']['address2'] ?? null);
            $address->setCity($shopifyOrder['shipping_address']['city']);
            $address->setProvince($shopifyOrder['shipping_address']['province']);
            $address->setZip($shopifyOrder['shipping_address']['zip']);
            $address->setCountry($shopifyOrder['shipping_address']['country']);
            $address->setPhone($shopifyOrder['shipping_address']['phone'] ?? null);
            
            $order->setShippingAddress($address);
        }
        
        $this->em->persist($order);
        $this->em->flush();
        
        return $order;
    }
    
    private function determinePaymentMethod(array $shopifyOrder): string
    {
        $gateway = $shopifyOrder['gateway'] ?? '';
        
        if (str_contains(strtolower($gateway), 'cash_on_delivery') || 
            str_contains(strtolower($gateway), 'cod')) {
            // Check if cash or credit card
            if (isset($shopifyOrder['payment_gateway_names']) && 
                in_array('credit_card', $shopifyOrder['payment_gateway_names'])) {
                return 'cod_credit';
            }
            return 'cod_cash';
        }
        
        return 'online';
    }
    
    public function updateOrderStatus(Order $order, string $status): void
    {
        $order->setStatus($status);
        $this->em->flush();
        
        // Update Shopify order
        $this->apiClient->put(
            $order->getShop()->getShopifyDomain(),
            $order->getShop()->getAccessToken(),
            "orders/{$order->getShopifyOrderId()}.json",
            [
                'order' => [
                    'id' => $order->getShopifyOrderId(),
                    'note' => "Order status updated to: {$status}"
                ]
            ]
        );
    }
    
    public function addTrackingToShopify(Order $order, string $trackingNumber, string $trackingUrl): void
    {
        $this->apiClient->post(
            $order->getShop()->getShopifyDomain(),
            $order->getShop()->getAccessToken(),
            "orders/{$order->getShopifyOrderId()}/fulfillments.json",
            [
                'fulfillment' => [
                    'tracking_number' => $trackingNumber,
                    'tracking_url' => $trackingUrl,
                    'notify_customer' => true
                ]
            ]
        );
    }
}
```

#### ShopifyWebhookService.php
```php
<?php

namespace App\Service\Shopify;

use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\ProcessShopifyOrderMessage;

class ShopifyWebhookService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ShopifyOrderService $orderService
    ) {}
    
    public function registerWebhooks(Shop $shop): void
    {
        $webhooks = [
            ['topic' => 'orders/create', 'address' => '/webhook/shopify/orders/create'],
            ['topic' => 'orders/updated', 'address' => '/webhook/shopify/orders/update'],
            ['topic' => 'orders/cancelled', 'address' => '/webhook/shopify/orders/cancelled'],
        ];
        
        foreach ($webhooks as $webhook) {
            $this->createWebhook($shop, $webhook['topic'], $webhook['address']);
        }
    }
    
    private function createWebhook(Shop $shop, string $topic, string $address): void
    {
        // Implementation to create webhook via Shopify API
    }
    
    public function handleOrderCreated(Shop $shop, array $orderData): void
    {
        // Dispatch to message queue for async processing
        $this->messageBus->dispatch(
            new ProcessShopifyOrderMessage($shop->getId(), $orderData)
        );
    }
}
```

---

### 2. KARGO FİRMASI ENTEGRASYON MODÜLÜ

#### CargoFactoryService.php
```php
<?php

namespace App\Service\Cargo;

use App\Entity\CargoCompany;

class CargoFactoryService
{
    private array $services = [];
    
    public function __construct(
        YurticiCargoService $yurtici,
        MNGCargoService $mng,
        SuratCargoService $surat,
        ArasCargoService $aras
        // Add other cargo services...
    ) {
        $this->services = [
            'yurtici' => $yurtici,
            'mng' => $mng,
            'surat' => $surat,
            'aras' => $aras,
        ];
    }
    
    public function getService(string $code): CargoServiceInterface
    {
        if (!isset($this->services[$code])) {
            throw new \InvalidArgumentException("Cargo service not found: {$code}");
        }
        
        return $this->services[$code];
    }
}
```

#### CargoServiceInterface.php
```php
<?php

namespace App\Service\Cargo;

use App\Entity\Shipment;

interface CargoServiceInterface
{
    public function createShipment(Shipment $shipment): array;
    public function getTracking(string $trackingNumber): array;
    public function cancelShipment(string $trackingNumber): bool;
    public function printLabel(string $trackingNumber): string; // Returns PDF
    public function calculatePrice(array $params): float;
}
```

#### YurticiCargoService.php
```php
<?php

namespace App\Service\Cargo;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class YurticiCargoService implements CargoServiceInterface
{
    private const API_URL = 'https://api.yurticikargo.com';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $username,
        private string $password,
        private string $customerId
    ) {}
    
    public function createShipment(Shipment $shipment): array
    {
        $order = $shipment->getOrder();
        $address = $order->getShippingAddress();
        
        $data = [
            'cargoKey' => $this->generateCargoKey(),
            'invoiceKey' => $order->getOrderNumber(),
            'receiverName' => $address->getFullName(),
            'receiverAddress' => $address->getAddress1(),
            'receiverCity' => $address->getCity(),
            'receiverDistrict' => $address->getProvince(),
            'receiverPhone' => $address->getPhone(),
            'desi' => $this->calculateDesi($shipment),
            'paymentType' => $this->getPaymentType($order),
        ];
        
        $response = $this->httpClient->request('POST', self::API_URL . '/createShipment', [
            'auth_basic' => [$this->username, $this->password],
            'json' => $data
        ]);
        
        return $response->toArray();
    }
    
    public function getTracking(string $trackingNumber): array
    {
        $response = $this->httpClient->request('GET', self::API_URL . '/tracking', [
            'auth_basic' => [$this->username, $this->password],
            'query' => ['trackingNo' => $trackingNumber]
        ]);
        
        return $response->toArray();
    }
    
    public function cancelShipment(string $trackingNumber): bool
    {
        $response = $this->httpClient->request('POST', self::API_URL . '/cancelShipment', [
            'auth_basic' => [$this->username, $this->password],
            'json' => ['trackingNo' => $trackingNumber]
        ]);
        
        return $response->getStatusCode() === 200;
    }
    
    public function printLabel(string $trackingNumber): string
    {
        $response = $this->httpClient->request('GET', self::API_URL . '/printLabel', [
            'auth_basic' => [$this->username, $this->password],
            'query' => ['trackingNo' => $trackingNumber]
        ]);
        
        return $response->getContent(); // PDF content
    }
    
    public function calculatePrice(array $params): float
    {
        // Implementation
        return 0.0;
    }
    
    private function generateCargoKey(): string
    {
        return uniqid('YK-', true);
    }
    
    private function calculateDesi(Shipment $shipment): float
    {
        // Desi calculation: (Width x Height x Length) / 3000
        // For now, use weight
        return $shipment->getTotalWeight() ?? 1.0;
    }
    
    private function getPaymentType(Order $order): int
    {
        return match($order->getPaymentMethod()) {
            'cod_cash' => 1, // Cash on delivery
            'cod_credit' => 2, // Credit card on delivery
            default => 0 // Prepaid
        };
    }
}
```

#### CargoLabelGenerator.php
```php
<?php

namespace App\Service\Cargo;

use Dompdf\Dompdf;
use Twig\Environment;

class CargoLabelGenerator
{
    public function __construct(
        private Environment $twig,
        private Dompdf $pdf
    ) {}
    
    public function generate(Shipment $shipment, string $template = 'default'): string
    {
        $html = $this->twig->render("cargo/labels/{$template}.html.twig", [
            'shipment' => $shipment,
            'order' => $shipment->getOrder(),
            'address' => $shipment->getOrder()->getShippingAddress(),
            'barcode' => $this->generateBarcode($shipment->getTrackingNumber())
        ]);
        
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper([0, 0, 283.46, 425.20]); // 10x15cm in points
        $this->pdf->render();
        
        return $this->pdf->output();
    }
    
    private function generateBarcode(string $code): string
    {
        // Generate barcode using a library
        // Return base64 encoded image
        return base64_encode($code);
    }
}
```

---

### 3. SİPARİŞ İŞLEME MODÜLÜ

#### OrderProcessingService.php
```php
<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Service\Notification\NotificationService;
use Symfony\Component\Messenger\MessageBusInterface;

class OrderProcessingService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private NotificationService $notificationService
    ) {}
    
    public function processNewOrder(Order $order): void
    {
        // Mark as processing
        $order->setStatus('processing');
        
        // Check if auto-shipment is enabled
        $autoShipment = $order->getShop()->getSetting('auto_shipment', false);
        
        if ($autoShipment) {
            // Dispatch shipment creation message
            $this->messageBus->dispatch(
                new CreateShipmentMessage($order->getId())
            );
        }
        
        // Send notification to admin
        $this->notificationService->notifyAdmins(
            'order.new',
            ['order' => $order]
        );
    }
    
    public function filterOrders(array $criteria): array
    {
        // Advanced filtering logic
        // status, payment_method, date_range, customer, etc.
    }
}
```

#### OrderSplitService.php
```php
<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Entity\Shipment;

class OrderSplitService
{
    public function splitByItems(Order $order, array $itemGroups): array
    {
        $shipments = [];
        
        foreach ($itemGroups as $group) {
            $shipment = new Shipment();
            $shipment->setOrder($order);
            $shipment->setItems($group);
            // Set other shipment properties...
            
            $shipments[] = $shipment;
        }
        
        return $shipments;
    }
    
    public function splitByWeight(Order $order, float $maxWeight): array
    {
        // Split order based on weight limit
    }
    
    public function splitByWarehouse(Order $order): array
    {
        // Split order based on warehouse stock availability
    }
}
```

---

### 4. BİLDİRİM SİSTEMİ

#### NotificationService.php
```php
<?php

namespace App\Service\Notification;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private SmsNotificationService $sms,
        private PushNotificationService $push
    ) {}
    
    public function sendShipmentCreatedNotification(Shipment $shipment): void
    {
        $order = $shipment->getOrder();
        
        // Email
        if ($order->getCustomerEmail()) {
            $email = (new TemplatedEmail())
                ->to($order->getCustomerEmail())
                ->subject('Your order has been shipped')
                ->htmlTemplate('email/shipment_created.html.twig')
                ->context([
                    'order' => $order,
                    'shipment' => $shipment,
                    'trackingUrl' => $this->generateTrackingUrl($shipment)
                ]);
            
            $this->mailer->send($email);
        }
        
        // SMS
        if ($order->getCustomerPhone()) {
            $message = "Your order #{$order->getOrderNumber()} has been shipped. " .
                      "Tracking: {$shipment->getTrackingNumber()}";
            $this->sms->send($order->getCustomerPhone(), $message);
        }
    }
    
    public function sendTrackingUpdateNotification(Shipment $shipment, array $trackingData): void
    {
        // Send tracking update notifications
    }
    
    private function generateTrackingUrl(Shipment $shipment): string
    {
        return "https://yoursite.com/track/{$shipment->getTrackingNumber()}";
    }
}
```

---

## 💻 JAVASCRIPT MODÜLLERI

### 1. Order Management JavaScript

#### assets/admin/js/order-management.js
```javascript
class OrderManager {
    constructor() {
        this.table = null;
        this.selectedOrders = new Set();
        this.init();
    }
    
    init() {
        this.initDataTable();
        this.initFilters();
        this.initBulkActions();
        this.initEventListeners();
    }
    
    initDataTable() {
        this.table = $('#orders-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/orders/datatable',
                type: 'POST',
                data: (d) => {
                    d.status = $('#filter-status').val();
                    d.payment = $('#filter-payment').val();
                    d.dateFrom = $('#filter-date-from').val();
                    d.dateTo = $('#filter-date-to').val();
                    d.search = $('#filter-search').val();
                }
            },
            columns: [
                { 
                    data: 'id',
                    render: (data) => {
                        return `<input type="checkbox" class="order-checkbox" value="${data}">`;
                    },
                    orderable: false,
                    searchable: false
                },
                { data: 'orderNumber' },
                { data: 'customer' },
                { 
                    data: 'date',
                    render: (data) => moment(data).format('DD.MM.YYYY HH:mm')
                },
                { 
                    data: 'total',
                    render: (data, type, row) => `${data} ${row.currency}`
                },
                {
                    data: 'paymentMethod',
                    render: (data) => this.getPaymentBadge(data)
                },
                {
                    data: 'status',
                    render: (data) => this.getStatusBadge(data)
                },
                {
                    data: null,
                    render: (data) => this.getActionButtons(data),
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[3, 'desc']],
            language: {
                url: `/assets/datatables/lang/${document.documentElement.lang}.json`
            }
        });
    }
    
    initFilters() {
        $('#filter-status, #filter-payment').on('change', () => {
            this.table.ajax.reload();
        });
        
        $('#filter-date-from, #filter-date-to').on('change', () => {
            this.table.ajax.reload();
        });
        
        $('#filter-search').on('keyup', debounce(() => {
            this.table.ajax.reload();
        }, 500));
    }
    
    initBulkActions() {
        $('#select-all-orders').on('change', (e) => {
            $('.order-checkbox').prop('checked', e.target.checked);
            this.updateSelectedOrders();
        });
        
        $(document).on('change', '.order-checkbox', () => {
            this.updateSelectedOrders();
        });
        
        $('#bulk-create-shipment').on('click', () => {
            this.bulkCreateShipment();
        });
        
        $('#bulk-export').on('click', () => {
            this.bulkExport();
        });
    }
    
    initEventListeners() {
        $(document).on('click', '.btn-create-shipment', (e) => {
            const orderId = $(e.currentTarget).data('order-id');
            this.createShipment(orderId);
        });
        
        $(document).on('click', '.btn-view-order', (e) => {
            const orderId = $(e.currentTarget).data('order-id');
            this.viewOrder(orderId);
        });
    }
    
    updateSelectedOrders() {
        this.selectedOrders.clear();
        $('.order-checkbox:checked').each((i, el) => {
            this.selectedOrders.add($(el).val());
        });
        
        $('#selected-count').text(this.selectedOrders.size);
        $('#bulk-actions').toggle(this.selectedOrders.size > 0);
    }
    
    async createShipment(orderId) {
        const modal = $('#shipment-modal');
        
        try {
            // Load order details
            const response = await fetch(`/admin/orders/${orderId}/shipment-form`);
            const html = await response.text();
            
            modal.find('.modal-body').html(html);
            modal.modal('show');
            
            // Initialize shipment form
            this.initShipmentForm(orderId);
            
        } catch (error) {
            toastr.error('Failed to load shipment form');
        }
    }
    
    initShipmentForm(orderId) {
        $('#shipment-form').on('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch(`/admin/shipments/create`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    toastr.success('Shipment created successfully');
                    $('#shipment-modal').modal('hide');
                    this.table.ajax.reload();
                    
                    // Auto-print label if enabled
                    if (data.autoPrint) {
                        window.open(`/admin/shipments/${data.shipmentId}/label`, '_blank');
                    }
                } else {
                    const error = await response.json();
                    toastr.error(error.message);
                }
            } catch (error) {
                toastr.error('Failed to create shipment');
            }
        });
    }
    
    async bulkCreateShipment() {
        if (this.selectedOrders.size === 0) {
            toastr.warning('Please select orders first');
            return;
        }
        
        const confirmed = await Swal.fire({
            title: 'Create Shipments?',
            text: `Create shipments for ${this.selectedOrders.size} orders?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, create',
            cancelButtonText: 'Cancel'
        });
        
        if (!confirmed.isConfirmed) return;
        
        try {
            const response = await fetch('/admin/shipments/bulk-create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    orderIds: Array.from(this.selectedOrders)
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                toastr.success(`${data.created} shipments created successfully`);
                this.table.ajax.reload();
                this.selectedOrders.clear();
                $('.order-checkbox').prop('checked', false);
                $('#bulk-actions').hide();
            }
        } catch (error) {
            toastr.error('Failed to create shipments');
        }
    }
    
    async bulkExport() {
        if (this.selectedOrders.size === 0) {
            toastr.warning('Please select orders first');
            return;
        }
        
        const orderIds = Array.from(this.selectedOrders).join(',');
        window.location.href = `/admin/orders/export?ids=${orderIds}`;
    }
    
    getPaymentBadge(method) {
        const badges = {
            'cod_cash': '<span class="badge badge-light-warning">Cash on Delivery</span>',
            'cod_credit': '<span class="badge badge-light-info">Card on Delivery</span>',
            'online': '<span class="badge badge-light-success">Online Payment</span>'
        };
        return badges[method] || method;
    }
    
    getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge badge-light-warning">Pending</span>',
            'processing': '<span class="badge badge-light-info">Processing</span>',
            'shipped': '<span class="badge badge-light-primary">Shipped</span>',
            'delivered': '<span class="badge badge-light-success">Delivered</span>',
            'cancelled': '<span class="badge badge-light-danger">Cancelled</span>'
        };
        return badges[status] || status;
    }
    
    getActionButtons(order) {
        return `
            <div class="btn-group">
                <button class="btn btn-sm btn-light-primary btn-create-shipment" 
                        data-order-id="${order.id}"
                        title="Create Shipment">
                    <i class="ki-outline ki-delivery"></i>
                </button>
                <button class="btn btn-sm btn-light-info btn-view-order" 
                        data-order-id="${order.id}"
                        title="View Details">
                    <i class="ki-outline ki-eye"></i>
                </button>
            </div>
        `;
    }
}

// Utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize on document ready
$(document).ready(() => {
    window.orderManager = new OrderManager();
});
```

### 2. Barcode Scanner JavaScript

#### assets/admin/js/barcode-scanner.js
```javascript
class BarcodeScanner {
    constructor() {
        this.scanner = null;
        this.isScanning = false;
        this.init();
    }
    
    init() {
        this.initQuagga();
        this.initEventListeners();
    }
    
    initQuagga() {
        this.scanner = Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#scanner-container'),
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: "environment"
                },
            },
            decoder: {
                readers: [
                    "code_128_reader",
                    "ean_reader",
                    "ean_8_reader",
                    "code_39_reader",
                    "code_39_vin_reader",
                    "codabar_reader",
                    "upc_reader",
                    "upc_e_reader",
                    "i2of5_reader"
                ]
            },
        }, (err) => {
            if (err) {
                console.error(err);
                toastr.error('Failed to initialize camera');
                return;
            }
            console.log("Barcode scanner initialized");
        });
        
        Quagga.onDetected((data) => {
            this.onBarcodeDetected(data.codeResult.code);
        });
    }
    
    initEventListeners() {
        $('#start-scan-btn').on('click', () => this.startScanning());
        $('#stop-scan-btn').on('click', () => this.stopScanning());
        $('#manual-barcode-form').on('submit', (e) => {
            e.preventDefault();
            const barcode = $('#manual-barcode').val();
            if (barcode) {
                this.onBarcodeDetected(barcode);
            }
        });
    }
    
    startScanning() {
        if (this.isScanning) return;
        
        Quagga.start();
        this.isScanning = true;
        $('#scanner-container').show();
        $('#start-scan-btn').hide();
        $('#stop-scan-btn').show();
    }
    
    stopScanning() {
        if (!this.isScanning) return;
        
        Quagga.stop();
        this.isScanning = false;
        $('#scanner-container').hide();
        $('#start-scan-btn').show();
        $('#stop-scan-btn').hide();
    }
    
    async onBarcodeDetected(barcode) {
        // Play beep sound
        this.playBeep();
        
        // Show detected barcode
        $('#detected-barcode').text(barcode);
        
        // Fetch product/order info
        try {
            const response = await fetch(`/admin/barcode/lookup/${barcode}`);
            const data = await response.json();
            
            if (data.type === 'product') {
                this.displayProductInfo(data.product);
            } else if (data.type === 'order') {
                this.displayOrderInfo(data.order);
            } else {
                toastr.warning('Barcode not found in system');
            }
            
        } catch (error) {
            toastr.error('Failed to lookup barcode');
        }
    }
    
    displayProductInfo(product) {
        const html = `
            <div class="card">
                <div class="card-body">
                    <h5>${product.name}</h5>
                    <p>SKU: ${product.sku}</p>
                    <p>Stock: ${product.stock}</p>
                    <button class="btn btn-primary" onclick="barcodeScanner.addToPackage(${product.id})">
                        Add to Package
                    </button>
                </div>
            </div>
        `;
        $('#barcode-result').html(html);
    }
    
    displayOrderInfo(order) {
        const html = `
            <div class="card">
                <div class="card-body">
                    <h5>Order #${order.orderNumber}</h5>
                    <p>Customer: ${order.customer}</p>
                    <p>Items: ${order.itemCount}</p>
                    <p>Status: ${order.status}</p>
                    <button class="btn btn-primary" onclick="barcodeScanner.viewOrder(${order.id})">
                        View Order
                    </button>
                    <button class="btn btn-success" onclick="barcodeScanner.startPacking(${order.id})">
                        Start Packing
                    </button>
                </div>
            </div>
        `;
        $('#barcode-result').html(html);
    }
    
    playBeep() {
        const audio = new Audio('/sounds/beep.mp3');
        audio.play();
    }
    
    addToPackage(productId) {
        // Add product to current package
        console.log('Adding product to package:', productId);
    }
    
    viewOrder(orderId) {
        window.location.href = `/admin/orders/${orderId}`;
    }
    
    startPacking(orderId) {
        window.location.href = `/admin/orders/${orderId}/pack`;
    }
}

// Initialize
$(document).ready(() => {
    window.barcodeScanner = new BarcodeScanner();
});
```

### 3. Tracking Page JavaScript

#### assets/tracking/js/tracking.js
```javascript
class TrackingPage {
    constructor() {
        this.trackingNumber = null;
        this.updateInterval = null;
        this.init();
    }
    
    init() {
        this.trackingNumber = $('#tracking-number').val();
        if (this.trackingNumber) {
            this.loadTracking();
            this.startAutoUpdate();
        }
        
        $('#tracking-form').on('submit', (e) => {
            e.preventDefault();
            this.trackingNumber = $('#tracking-input').val();
            this.loadTracking();
        });
    }
    
    async loadTracking() {
        if (!this.trackingNumber) return;
        
        try {
            const response = await fetch(`/api/tracking/${this.trackingNumber}`);
            const data = await response.json();
            
            this.displayTracking(data);
            
        } catch (error) {
            toastr.error('Failed to load tracking information');
        }
    }
    
    displayTracking(data) {
        // Display shipment info
        $('#shipment-status').html(this.getStatusBadge(data.status));
        $('#order-number').text(data.orderNumber);
        $('#cargo-company').text(data.cargoCompany);
        
        // Display tracking timeline
        const timelineHtml = data.trackingHistory.map((event, index) => {
            const isLast = index === data.trackingHistory.length - 1;
            return `
                <div class="timeline-item ${isLast ? 'active' : ''}">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h6>${event.status}</h6>
                        <p>${event.description}</p>
                        <small class="text-muted">${this.formatDate(event.date)}</small>
                    </div>
                </div>
            `;
        }).join('');
        
        $('#tracking-timeline').html(timelineHtml);
        
        // Show estimated delivery if available
        if (data.estimatedDelivery) {
            $('#estimated-delivery').text(this.formatDate(data.estimatedDelivery));
            $('#estimated-delivery-section').show();
        }
    }
    
    startAutoUpdate() {
        // Update every 5 minutes
        this.updateInterval = setInterval(() => {
            this.loadTracking();
        }, 5 * 60 * 1000);
    }
    
    stopAutoUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }
    
    getStatusBadge(status) {
        const badges = {
            'created': '<span class="badge badge-light">Created</span>',
            'in_transit': '<span class="badge badge-primary">In Transit</span>',
            'out_for_delivery': '<span class="badge badge-info">Out for Delivery</span>',
            'delivered': '<span class="badge badge-success">Delivered</span>',
            'returned': '<span class="badge badge-warning">Returned</span>',
            'cancelled': '<span class="badge badge-danger">Cancelled</span>'
        };
        return badges[status] || status;
    }
    
    formatDate(dateString) {
        return moment(dateString).format('DD MMMM YYYY, HH:mm');
    }
}

// Initialize
$(document).ready(() => {
    window.trackingPage = new TrackingPage();
});
```

---

## 🚀 DEPLOYMENT & DEVOPS

### 1. Docker Configuration

#### docker-compose.yml
```yaml
version: '3.8'

services:
  php:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - .:/var/www/html
    environment:
      DATABASE_URL: mysql://app:secret@db:3306/shopify_cargo
      REDIS_URL: redis://redis:6379
    depends_on:
      - db
      - redis
    networks:
      - app-network

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/ssl:/etc/nginx/ssl
    depends_on:
      - php
    networks:
      - app-network

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: shopify_cargo
      MYSQL_USER: app
      MYSQL_PASSWORD: secret
    volumes:
      - db-data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - app-network

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    networks:
      - app-network

  messenger-consumer:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    command: php bin/console messenger:consume async --time-limit=3600 --memory-limit=256M
    volumes:
      - .:/var/www/html
    depends_on:
      - db
      - redis
    networks:
      - app-network
    restart: always

volumes:
  db-data:
  redis-data:

networks:
  app-network:
    driver: bridge
```

#### docker/php/Dockerfile
```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create necessary directories
RUN mkdir -p var/cache var/log

# Set permissions
RUN chown -R www-data:www-data var

EXPOSE 9000

CMD ["php-fpm"]
```

### 2. CI/CD Pipeline (GitHub Actions)

#### .github/workflows/deploy.yml
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: test_db
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      
      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, redis
          coverage: xdebug
      
      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run PHPUnit tests
        env:
          DATABASE_URL: mysql://root:root@127.0.0.1:3306/test_db
          REDIS_URL: redis://127.0.0.1:6379
        run: php bin/phpunit
      
      - name: Run PHP CodeSniffer
        run: vendor/bin/phpcs
  
  deploy:
    needs: test
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/shopify-cargo
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php bin/console doctrine:migrations:migrate --no-interaction
            php bin/console cache:clear --env=prod
            php bin/console cache:warmup --env=prod
            sudo systemctl restart php8.2-fpm
            sudo systemctl reload nginx
```

### 3. Production Environment Setup

#### .env.prod
```bash
APP_ENV=prod
APP_SECRET=your-secret-key-here
DATABASE_URL="mysql://user:password@localhost:3306/shopify_cargo?serverVersion=8.0"

# Redis
REDIS_URL=redis://localhost:6379

# Symfony Messenger
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase

# Shopify
SHOPIFY_API_KEY=your-api-key
SHOPIFY_API_SECRET=your-api-secret
SHOPIFY_APP_URL=https://your-app-url.com

# Cargo Companies
YURTICI_USERNAME=your-username
YURTICI_PASSWORD=your-password
YURTICI_CUSTOMER_ID=your-customer-id

MNG_USERNAME=your-username
MNG_PASSWORD=your-password

# Email
MAILER_DSN=smtp://username:password@smtp.example.com:587

# SMS
NETGSM_USERNAME=your-username
NETGSM_PASSWORD=your-password
```

### 4. Nginx Configuration

#### /etc/nginx/sites-available/shopify-cargo
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/shopify-cargo/public;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/shopify_cargo_error.log;
    access_log /var/log/nginx/shopify_cargo_access.log;
}
```

### 5. Supervisor Configuration (Background Jobs)

#### /etc/supervisor/conf.d/messenger-worker.conf
```ini
[program:messenger-worker]
command=php /var/www/shopify-cargo/bin/console messenger:consume async --time-limit=3600 --memory-limit=256M
user=www-data
numprocs=4
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/var/www/shopify-cargo/var/log/messenger-worker.log
stderr_logfile=/var/www/shopify-cargo/var/log/messenger-worker-error.log
```

---

## 📊 MONITORING & LOGGING

### 1. Monolog Configuration

#### config/packages/prod/monolog.yaml
```yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            action_level: error
            handler: grouped
            excluded_http_codes: [404, 405]
            buffer_size: 50

        grouped:
            type: group
            members: [streamed, deduplicated]

        streamed:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug

        deduplicated:
            type: deduplication
            handler: swift

        swift:
            type: swift_mailer
            from_email: 'alerts@your-domain.com'
            to_email: 'admin@your-domain.com'
            subject: 'Production Error!'
            level: critical

        slack:
            type: slack
            token: 'your-slack-token'
            channel: '#alerts'
            bot_name: 'Cargo Bot'
            level: error
```

---

## ✅ CHECKLIST BEFORE GO-LIVE

### Security
- [ ] Change all default passwords
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Configure firewall rules
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Set up rate limiting
- [ ] Configure CORS properly
- [ ] Enable 2FA for admin accounts
- [ ] Set up regular backups

### Performance
- [ ] Enable OPcache
- [ ] Configure Redis caching
- [ ] Set up CDN for static assets
- [ ] Enable gzip compression
- [ ] Optimize database indexes
- [ ] Set up database replication (if needed)

### Monitoring
- [ ] Set up error tracking (Sentry, Rollbar)
- [ ] Configure application monitoring (New Relic, DataDog)
- [ ] Set up uptime monitoring
- [ ] Configure log rotation
- [ ] Set up alerts for critical errors

### Testing
- [ ] Run full test suite
- [ ] Perform load testing
- [ ] Test all integrations
- [ ] Test backup/restore procedure
- [ ] Test disaster recovery plan

---

## 🎓 EK KAYNAKLAR

- Symfony Documentation: https://symfony.com/doc/current/index.html
- Metronic 8 Documentation: https://preview.keenthemes.com/symfony/metronic/docs/
- Shopify API: https://shopify.dev/docs/api
- Docker Documentation: https://docs.docker.com/

---

**Hazırlayan:** Web Geliştirme Uzmanı - Ünal için özel derlenmiştir
**Tarih:** 31 Ekim 2025
**Versiyon:** 2.0