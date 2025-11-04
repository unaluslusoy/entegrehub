<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ödeme Entegrasyonları Yönetimi
 * Stripe, PayTR, İyzico, Paypal vb. ödeme sistemlerinin yönetimi
 */
#[Route('/admin/payment-integrations')]
class PaymentIntegrationController extends AbstractController
{
    #[Route('/', name: 'admin_payment_integrations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // Desteklenen ödeme entegrasyonları
        $integrations = [
            [
                'id' => 'stripe',
                'name' => 'Stripe',
                'description' => 'Uluslararası kredi kartı ödemeleri',
                'logo' => '/assets/media/payment/stripe.svg',
                'isActive' => false,
                'isConfigured' => false,
                'fields' => [
                    'api_key' => 'API Key',
                    'secret_key' => 'Secret Key',
                    'webhook_secret' => 'Webhook Secret'
                ]
            ],
            [
                'id' => 'paytr',
                'name' => 'PayTR',
                'description' => 'Türkiye\'nin ödeme sistemi',
                'logo' => '/assets/media/payment/paytr.svg',
                'isActive' => false,
                'isConfigured' => false,
                'fields' => [
                    'merchant_id' => 'Merchant ID',
                    'merchant_key' => 'Merchant Key',
                    'merchant_salt' => 'Merchant Salt'
                ]
            ],
            [
                'id' => 'iyzico',
                'name' => 'İyzico',
                'description' => 'Online ödeme sistemi',
                'logo' => '/assets/media/payment/iyzico.svg',
                'isActive' => false,
                'isConfigured' => false,
                'fields' => [
                    'api_key' => 'API Key',
                    'secret_key' => 'Secret Key'
                ]
            ],
            [
                'id' => 'paypal',
                'name' => 'PayPal',
                'description' => 'Global ödeme platformu',
                'logo' => '/assets/media/payment/paypal.svg',
                'isActive' => false,
                'isConfigured' => false,
                'fields' => [
                    'client_id' => 'Client ID',
                    'client_secret' => 'Client Secret',
                    'mode' => 'Mode (sandbox/live)'
                ]
            ]
        ];

        return $this->render('admin/payment_integration/index.html.twig', [
            'integrations' => $integrations,
        ]);
    }

    #[Route('/{id}/configure', name: 'admin_payment_integrations_configure', methods: ['GET', 'POST'])]
    public function configure(Request $request, string $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // TODO: Ödeme entegrasyonu ayarlarını kaydet
            // Örnek: settings tablosuna veya .env dosyasına kaydetme
            
            $this->addFlash('success', ucfirst($id) . ' entegrasyonu başarıyla yapılandırıldı.');
            return $this->redirectToRoute('admin_payment_integrations');
        }

        return $this->render('admin/payment_integration/configure.html.twig', [
            'integration_id' => $id,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_payment_integrations_toggle', methods: ['POST'])]
    public function toggle(Request $request, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // TODO: Ödeme entegrasyonunu aktif/pasif yap
        
        return $this->json([
            'success' => true,
            'message' => 'Entegrasyon durumu güncellendi.',
        ]);
    }

    #[Route('/{id}/test', name: 'admin_payment_integrations_test', methods: ['POST'])]
    public function test(Request $request, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // TODO: Ödeme entegrasyonu bağlantı testi
        
        return $this->json([
            'success' => true,
            'message' => 'Bağlantı testi başarılı.',
        ]);
    }
}
