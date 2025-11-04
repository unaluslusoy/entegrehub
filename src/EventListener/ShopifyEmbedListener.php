<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Shopify App Bridge compatibility
 * Allow embedding in Shopify Admin iframe
 */
#[AsEventListener(event: KernelEvents::RESPONSE, priority: -10)]
class ShopifyEmbedListener
{
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Check if request is from Shopify embedded app
        $isShopifyEmbed = $request->query->has('shop') 
            || $request->query->has('embedded')
            || str_contains($request->headers->get('Referer', ''), 'shopify.com')
            || str_contains($request->headers->get('Referer', ''), 'myshopify.com');

        // Check if user is accessing Shopify routes
        $route = $request->attributes->get('_route', '');
        $isShopifyRoute = str_starts_with($route, 'user_shopify_') 
            || str_starts_with($route, 'shopify_');

        if ($isShopifyEmbed || $isShopifyRoute) {
            // FORCE remove X-Frame-Options to allow iframe embedding
            $response->headers->remove('X-Frame-Options');
            
            // Also try alternative header names
            $response->headers->remove('x-frame-options');
            
            // Set Content-Security-Policy for Shopify
            $shop = $request->query->get('shop', '*.myshopify.com');
            $csp = sprintf(
                "frame-ancestors https://%s https://admin.shopify.com;",
                $shop
            );
            $response->headers->set('Content-Security-Policy', $csp, true);
            
            // Set P3P for legacy IE support in iframe
            $response->headers->set('P3P', 'CP="Not used"');
            
            // Ensure cookies work in iframe (SameSite=None with Secure)
            $response->headers->set('Set-Cookie', 'shopify_embed=1; SameSite=None; Secure', false);
        }
    }
}
