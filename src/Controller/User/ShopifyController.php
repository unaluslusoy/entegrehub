<?php

namespace App\Controller\User;

use App\Entity\ShopifyStore;
use App\Repository\ShopifyStoreRepository;
use App\Service\ShopifyService;
use App\Service\ShopifyWebhookHandler;
use App\Service\ShopifySyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/user/shopify')]
class ShopifyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShopifyStoreRepository $storeRepository,
        private ShopifyService $shopifyService,
        private ShopifyWebhookHandler $webhookHandler,
        private ShopifySyncService $syncService,
        private LoggerInterface $logger
    ) {}

    /**
     * Shopify App Entry Point - Direct from Shopify Admin
     * Automatically initiates OAuth flow or shows embedded app
     */
    #[Route('/app', name: 'user_shopify_app_entry', methods: ['GET'])]
    public function appEntry(Request $request): Response
    {
        $shop = $request->query->get('shop');
        $hmac = $request->query->get('hmac');
        $timestamp = $request->query->get('timestamp');
        $host = $request->query->get('host');

        // Basic shop parameter check
        if (!$shop) {
            return $this->render('user/shopify/error.html.twig', [
                'message' => 'Geçersiz Shopify isteği. Shop parametresi eksik.',
            ]);
        }

        // Verify HMAC if provided
        if ($hmac) {
            $queryParams = $request->query->all();
            if (!$this->shopifyService->verifyHmac($queryParams, $hmac)) {
                $this->logger->warning('Shopify app entry HMAC verification failed', [
                    'shop' => $shop,
                    'hmac' => $hmac,
                ]);
                // Continue anyway - might be development or first access
            }
        }

        // Check if store already connected and has valid token
        $existingStore = $this->storeRepository->findOneBy([
            'shopDomain' => $shop,
            'isActive' => true,
        ]);

        if ($existingStore && $existingStore->getAccessToken()) {
            // Store connected - show embedded app interface
            $this->logger->info('Shopify store already connected, showing embedded app', [
                'shop' => $shop,
                'store_id' => $existingStore->getId(),
            ]);
            
            return $this->render('user/shopify/embedded_app.html.twig', [
                'shop' => $shop,
                'host' => $host,
                'api_key' => $this->getParameter('shopify.api_key'),
                'api_base' => $this->getParameter('app.url'),
                'store' => $existingStore,
            ]);
        }

        // Store not connected - start OAuth flow
        $this->logger->info('Starting OAuth flow from Shopify Admin', [
            'shop' => $shop,
            'timestamp' => $timestamp,
        ]);

        // Redirect to OAuth authorization
        $authUrl = $this->shopifyService->getAuthorizationUrl($shop);
        
        return $this->render('user/shopify/auth_redirect.html.twig', [
            'redirect_url' => $authUrl,
            'shop' => $shop,
        ]);
    }

    /**
     * Shopify dashboard - list connected stores
     */
    #[Route('', name: 'user_shopify_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        
        $this->logger->info('Loading Shopify stores index', [
            'user_id' => $user ? $user->getId() : null,
            'user_email' => $user ? method_exists($user, 'getEmail') ? $user->getEmail() : null : null,
        ]);
        
        $stores = $this->storeRepository->findActiveByUser($user);
        $stats = $this->storeRepository->getSyncStatistics($user);

        $this->logger->info('Shopify stores loaded', [
            'store_count' => count($stores),
            'stats' => $stats,
        ]);

        return $this->render('user/shopify/index.html.twig', [
            'stores' => $stores,
            'stats' => $stats,
        ]);
    }

    /**
     * Individual store dashboard
     */
    #[Route('/{id}/dashboard', name: 'user_shopify_dashboard', methods: ['GET'])]
    public function dashboard(ShopifyStore $store): Response
    {
        $this->denyAccessUnlessGranted('view', $store);

        return $this->render('user/shopify/dashboard.html.twig', [
            'store' => $store,
        ]);
    }

    /**
     * Debug OAuth configuration
     */
    #[Route('/debug', name: 'user_shopify_debug', methods: ['GET'])]
    public function debug(): Response
    {
        return $this->render('user/shopify/oauth_test.html.twig', [
            'app_url' => $this->getParameter('app.url'),
            'redirect_uri' => $this->shopifyService->getRedirectUri(),
            'api_key' => $this->getParameter('shopify.api_key'),
            'scopes' => $this->getParameter('shopify.scopes'),
        ]);
    }

    /**
     * Install - initiate OAuth flow
     */
    #[Route('/install', name: 'user_shopify_install', methods: ['GET', 'POST'])]
    public function install(Request $request): Response
    {
        // Check if user is authenticated FIRST
        $user = $this->getUser();
        
        if (!$user) {
            // Save the current URL to redirect back after login
            $session = $request->getSession();
            $session->set('_security.main.target_path', $request->getUri());
            
            $this->addFlash('warning', 'Shopify mağazası bağlamak için önce giriş yapmalısınız.');
            return $this->redirectToRoute('app_login');
        }
        
        if ($request->isMethod('POST')) {
            $shopDomain = $request->request->get('shop_domain');

            if (!$shopDomain) {
                $this->addFlash('error', 'Lütfen Shopify mağaza alanınızı girin.');
                return $this->redirectToRoute('user_shopify_install');
            }

            // Normalize shop domain
            $shopDomain = str_replace(['https://', 'http://', 'www.'], '', trim($shopDomain));
            
            // Remove trailing slashes
            $shopDomain = rtrim($shopDomain, '/');
            
            // Add .myshopify.com if not present
            if (!str_ends_with($shopDomain, '.myshopify.com')) {
                // If user entered just the store name
                if (strpos($shopDomain, '.') === false) {
                    $shopDomain .= '.myshopify.com';
                }
            }

            // Validate shop domain format
            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shopDomain)) {
                $this->addFlash('error', 'Geçersiz Shopify mağaza adresi formatı. Örnek: my-store.myshopify.com');
                return $this->redirectToRoute('user_shopify_install');
            }
            
            // Check if store already connected
            $existingStore = $this->storeRepository->findOneBy([
                'shopDomain' => $shopDomain,
                'user' => $user,
                'isActive' => true
            ]);

            if ($existingStore) {
                $this->addFlash('warning', 'Bu mağaza zaten bağlı. Ayarlar sayfasından yönetebilirsiniz.');
                return $this->redirectToRoute('user_shopify_store_detail', ['id' => $existingStore->getId()]);
            }

            // Generate OAuth URL
            try {
                $authUrl = $this->shopifyService->getAuthorizationUrl($shopDomain);
                
                $this->logger->info('Shopify OAuth initiated', [
                    'shop' => $shopDomain,
                    'user_id' => $user->getId(),
                    'user_email' => $user->getEmail(),
                    'redirect_uri' => $this->shopifyService->getRedirectUri(),
                    'oauth_url' => $authUrl,
                    'ip' => $request->getClientIp(),
                ]);
                
                $this->addFlash('info', sprintf(
                    'Yönlendirme URL: %s | Callback URL: %s',
                    $shopDomain,
                    $this->shopifyService->getRedirectUri()
                ));
                
                return $this->redirect($authUrl);
            } catch (\Exception $e) {
                $this->logger->error('Shopify OAuth initialization failed', [
                    'shop' => $shopDomain,
                    'user_id' => $user ? $user->getId() : 'guest',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->addFlash('error', 'Shopify bağlantısı başlatılamadı: ' . $e->getMessage());
                return $this->redirectToRoute('user_shopify_install');
            }
        }

        return $this->render('user/shopify/install.html.twig', [
            'app_url' => $this->getParameter('app.url'),
            'redirect_uri' => $this->shopifyService->getRedirectUri()
        ]);
    }

    /**
     * OAuth callback - handle authorization response
     */
    #[Route('/callback', name: 'user_shopify_callback', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        $shop = $request->query->get('shop');
        $hmac = $request->query->get('hmac');
        $state = $request->query->get('state');

        // Security: Validate required parameters
        if (!$code || !$shop || !$hmac) {
            $this->logger->error('OAuth callback - Missing required parameters', [
                'shop' => $shop,
                'has_code' => !empty($code),
                'has_hmac' => !empty($hmac),
                'ip' => $request->getClientIp(),
            ]);
            $this->addFlash('error', 'Geçersiz OAuth yanıtı. Eksik parametreler.');
            return $this->redirectToRoute('user_shopify_install');
        }

        // Security: Validate shop domain format
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shop)) {
            $this->logger->error('OAuth callback - Invalid shop domain format', [
                'shop' => $shop,
                'ip' => $request->getClientIp(),
            ]);
            $this->addFlash('error', 'Geçersiz mağaza domain formatı.');
            return $this->redirectToRoute('user_shopify_install');
        }

        // Security: Verify HMAC signature (CRITICAL)
        $queryParams = $request->query->all();
        if (!$this->shopifyService->verifyHmac($queryParams, $hmac)) {
            $this->logger->error('OAuth callback - HMAC verification failed', [
                'shop' => $shop,
                'hmac' => $hmac,
                'query_params' => array_keys($queryParams),
                'ip' => $request->getClientIp(),
            ]);
            $this->addFlash('error', 'Güvenlik doğrulaması başarısız. HMAC geçersiz.');
            return $this->redirectToRoute('user_shopify_install');
        }

        try {
            // Exchange code for access token
            $tokenData = $this->shopifyService->exchangeCodeForToken($shop, $code);

            // Check if user is authenticated
            $user = $this->getUser();
            
            // User should be authenticated since we checked before OAuth
            if (!$user) {
                $this->logger->error('OAuth callback without authenticated user - this should not happen', [
                    'shop' => $shop,
                    'ip' => $request->getClientIp(),
                ]);
                
                $this->addFlash('error', 'Oturum süresi doldu. Lütfen tekrar giriş yapıp mağazayı bağlayın.');
                return $this->redirectToRoute('app_login');
            }

            // Create or update store connection
            $store = $this->shopifyService->createOrUpdateStore(
                $user,
                $shop,
                $tokenData['access_token'],
                $tokenData['scope']
            );

            // Register webhooks
            $webhookBaseUrl = $request->getSchemeAndHttpHost();
            $webhookResults = $this->webhookHandler->registerWebhooks($store, $this->shopifyService, $webhookBaseUrl);

            $this->logger->info('Shopify store connected successfully', [
                'store_id' => $store->getId(),
                'shop' => $shop,
                'user_id' => $user->getId(),
                'webhooks' => $webhookResults
            ]);

            $this->addFlash('success', 'Shopify mağazanız başarıyla bağlandı! Siparişler senkronize ediliyor...');

            return $this->redirectToRoute('user_shopify_store_detail', ['id' => $store->getId()]);

        } catch (\Exception $e) {
            $this->logger->error('Shopify OAuth callback failed', [
                'shop' => $shop,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Shopify bağlantısı tamamlanamadı: ' . $e->getMessage());
            return $this->redirectToRoute('user_shopify_install');
        }
    }

    /**
     * Store detail page
     */
    #[Route('/store/{id}', name: 'user_shopify_store_detail', methods: ['GET'])]
    public function storeDetail(ShopifyStore $store): Response
    {
        // Check ownership
        if ($store->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $syncLogs = $store->getSyncLogs()->slice(0, 10);
        $orderMappings = $store->getOrderMappings()->slice(0, 20);
        $webhooks = $store->getWebhooks();

        return $this->render('user/shopify/detail.html.twig', [
            'store' => $store,
            'syncLogs' => $syncLogs,
            'orderMappings' => $orderMappings,
            'webhooks' => $webhooks,
        ]);
    }

    /**
     * Sync orders manually
     */
    #[Route('/store/{id}/sync', name: 'user_shopify_sync_orders', methods: ['POST'])]
    public function syncOrders(ShopifyStore $store): JsonResponse
    {
        // Check ownership
        if ($store->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        try {
            // Start sync in background (in production, use Messenger/Queue)
            $syncLog = $this->syncService->syncOrders($store);

            return new JsonResponse([
                'success' => true,
                'message' => 'Senkronizasyon başlatıldı',
                'sync_log_id' => $syncLog->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Manual order sync failed', [
                'store_id' => $store->getId(),
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Test connection
     */
    #[Route('/store/{id}/test', name: 'user_shopify_test_connection', methods: ['POST'])]
    public function testConnection(ShopifyStore $store): JsonResponse
    {
        // Check ownership
        if ($store->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $success = $this->shopifyService->testConnection($store);

        return new JsonResponse([
            'success' => $success,
            'message' => $success ? 'Bağlantı başarılı' : 'Bağlantı başarısız'
        ]);
    }

    /**
     * Disconnect store
     */
    #[Route('/store/{id}/disconnect', name: 'user_shopify_disconnect', methods: ['POST'])]
    public function disconnect(ShopifyStore $store): Response
    {
        // Check ownership
        if ($store->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        try {
            // Unregister webhooks
            $this->webhookHandler->unregisterWebhooks($store, $this->shopifyService);

            // Disconnect store
            $this->shopifyService->disconnectStore($store);

            $this->addFlash('success', 'Shopify mağazası başarıyla bağlantısı kesildi.');

        } catch (\Exception $e) {
            $this->logger->error('Failed to disconnect store', [
                'store_id' => $store->getId(),
                'error' => $e->getMessage()
            ]);

            $this->addFlash('error', 'Bağlantı kesilemedi: ' . $e->getMessage());
        }

        return $this->redirectToRoute('user_shopify_index');
    }

    /**
     * Webhook endpoint - receives Shopify webhooks
     */
    #[Route('/webhook', name: 'user_shopify_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Get shop domain from header
            $shopDomain = $request->headers->get('X-Shopify-Shop-Domain');

            if (!$shopDomain) {
                return new JsonResponse(['error' => 'Shop domain not found'], Response::HTTP_BAD_REQUEST);
            }

            // Find store
            $store = $this->storeRepository->findByShopDomain($shopDomain);

            if (!$store || !$store->isActive()) {
                return new JsonResponse(['error' => 'Store not found or inactive'], Response::HTTP_NOT_FOUND);
            }

            // Verify webhook signature
            if (!$this->shopifyService->verifyWebhook($request)) {
                $this->logger->warning('Shopify webhook signature verification failed', [
                    'shop' => $shopDomain
                ]);
                return new JsonResponse(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
            }

            // Process webhook
            $result = $this->webhookHandler->handleWebhook($request, $store);

            return new JsonResponse($result);

        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Settings page
     */
    #[Route('/store/{id}/settings', name: 'user_shopify_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, ShopifyStore $store): Response
    {
        // Check ownership
        if ($store->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            // Update settings
            $webhookAddress = $request->request->get('webhook_address');
            
            if ($webhookAddress) {
                $store->setWebhookAddress($webhookAddress);
                $this->entityManager->flush();

                $this->addFlash('success', 'Ayarlar kaydedildi.');
            }

            return $this->redirectToRoute('user_shopify_settings', ['id' => $store->getId()]);
        }

        return $this->render('user/shopify/settings.html.twig', [
            'store' => $store
        ]);
    }

    /**
     * Complete Shopify connection after login
     * Called after user logs in with pending Shopify connection
     */
    #[Route('/complete-connection', name: 'user_shopify_complete_connection', methods: ['GET'])]
    public function completeConnection(Request $request): Response
    {
        $session = $request->getSession();
        $pendingConnection = $session->get('shopify_pending_connection');

        if (!$pendingConnection) {
            $this->addFlash('warning', 'Bekleyen Shopify bağlantısı bulunamadı.');
            return $this->redirectToRoute('user_dashboard');
        }

        // Check if connection data is not too old (30 minutes)
        if ((time() - $pendingConnection['timestamp']) > 1800) {
            $session->remove('shopify_pending_connection');
            $this->addFlash('error', 'Shopify bağlantısı zaman aşımına uğradı. Lütfen tekrar deneyin.');
            return $this->redirectToRoute('user_shopify_install');
        }

        try {
            $user = $this->getUser();
            
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            // Create or update store connection
            $store = $this->shopifyService->createOrUpdateStore(
                $user,
                $pendingConnection['shop'],
                $pendingConnection['access_token'],
                $pendingConnection['scope']
            );

            // Register webhooks
            $webhookBaseUrl = $request->getSchemeAndHttpHost();
            $webhookResults = $this->webhookHandler->registerWebhooks($store, $this->shopifyService, $webhookBaseUrl);

            // Clear session data
            $session->remove('shopify_pending_connection');

            $this->logger->info('Shopify connection completed after login', [
                'store_id' => $store->getId(),
                'shop' => $pendingConnection['shop'],
                'user_id' => $user->getId(),
                'webhooks' => $webhookResults
            ]);

            $this->addFlash('success', 'Shopify mağazanız başarıyla bağlandı!');
            return $this->redirectToRoute('user_shopify_store_detail', ['id' => $store->getId()]);

        } catch (\Exception $e) {
            $session->remove('shopify_pending_connection');
            
            $this->logger->error('Failed to complete Shopify connection', [
                'shop' => $pendingConnection['shop'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            $this->addFlash('error', 'Shopify bağlantısı tamamlanamadı: ' . $e->getMessage());
            return $this->redirectToRoute('user_shopify_install');
        }
    }

    /**
     * Get sync status (AJAX)
     */
    #[Route('/store/{id}/sync-status', name: 'user_shopify_sync_status', methods: ['GET'])]
    public function syncStatus(ShopifyStore $store): JsonResponse
    {
        // Check ownership
        if ($store->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'status' => $store->getSyncStatus(),
            'progress' => $store->getSyncProgress(),
            'last_sync_at' => $store->getLastSyncAt()?->format('Y-m-d H:i:s'),
            'total_orders_synced' => $store->getTotalOrdersSynced()
        ]);
    }
}
