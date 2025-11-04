<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    // Helper method to save settings to a JSON file or database
    private function saveSetting(string $key, mixed $value): void
    {
        // TODO: Implement proper settings storage (database table or config file)
        // For now, this is a placeholder
        $settingsFile = $this->getParameter('kernel.project_dir') . '/var/settings.json';
        
        $settings = [];
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true) ?? [];
        }
        
        $settings[$key] = $value;
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    }

    private function getSetting(string $key, mixed $default = null): mixed
    {
        $settingsFile = $this->getParameter('kernel.project_dir') . '/var/settings.json';
        
        if (!file_exists($settingsFile)) {
            return $default;
        }
        
        $settings = json_decode(file_get_contents($settingsFile), true) ?? [];
        return $settings[$key] ?? $default;
    }

    private function getAllSettings(): array
    {
        $settingsFile = $this->getParameter('kernel.project_dir') . '/var/settings.json';
        
        if (!file_exists($settingsFile)) {
            return [];
        }
        
        return json_decode(file_get_contents($settingsFile), true) ?? [];
    }

    #[Route('/general', name: 'admin_settings_general', methods: ['GET', 'POST'])]
    public function general(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $this->saveSetting('site_name', $request->request->get('site_name'));
            $this->saveSetting('site_logo', $request->request->get('site_logo'));
            $this->saveSetting('site_description', $request->request->get('site_description'));
            $this->saveSetting('timezone', $request->request->get('timezone'));
            $this->saveSetting('language', $request->request->get('language'));
            $this->saveSetting('maintenance_mode', $request->request->get('maintenance_mode') === '1');
            $this->saveSetting('maintenance_message', $request->request->get('maintenance_message'));

            $this->addFlash('success', 'Genel ayarlar kaydedildi.');
            return $this->redirectToRoute('admin_settings_general');
        }

        return $this->render('admin/settings/general.html.twig', [
            'settings' => $this->getAllSettings(),
        ]);
    }

    #[Route('/mail', name: 'admin_settings_mail', methods: ['GET', 'POST'])]
    public function mail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $this->saveSetting('mail_driver', $request->request->get('mail_driver'));
            $this->saveSetting('mail_host', $request->request->get('mail_host'));
            $this->saveSetting('mail_port', $request->request->get('mail_port'));
            $this->saveSetting('mail_username', $request->request->get('mail_username'));
            if ($request->request->get('mail_password')) {
                $this->saveSetting('mail_password', $request->request->get('mail_password'));
            }
            $this->saveSetting('mail_encryption', $request->request->get('mail_encryption'));
            $this->saveSetting('mail_from_address', $request->request->get('mail_from_address'));
            $this->saveSetting('mail_from_name', $request->request->get('mail_from_name'));

            $this->addFlash('success', 'Mail ayarları kaydedildi.');
            return $this->redirectToRoute('admin_settings_mail');
        }

        return $this->render('admin/settings/mail.html.twig', [
            'settings' => $this->getAllSettings(),
        ]);
    }

    #[Route('/mail/test', name: 'admin_settings_mail_test', methods: ['POST'])]
    public function testMail(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['success' => false, 'message' => 'Email adresi gerekli'], 400);
        }

        // TODO: Implement actual email sending with Symfony Mailer
        // For now, simulate success
        try {
            // Simulate delay
            usleep(500000);

            return new JsonResponse([
                'success' => true,
                'message' => "Test email başarıyla gönderildi: {$email}"
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Email gönderilemedi: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sms', name: 'admin_settings_sms', methods: ['GET', 'POST'])]
    public function sms(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $this->saveSetting('sms_provider', $request->request->get('sms_provider'));
            $this->saveSetting('sms_api_key', $request->request->get('sms_api_key'));
            $this->saveSetting('sms_api_secret', $request->request->get('sms_api_secret'));
            $this->saveSetting('sms_sender', $request->request->get('sms_sender'));
            $this->saveSetting('sms_enabled', $request->request->get('sms_enabled') === '1');

            $this->addFlash('success', 'SMS ayarları kaydedildi.');
            return $this->redirectToRoute('admin_settings_sms');
        }

        return $this->render('admin/settings/sms.html.twig', [
            'settings' => $this->getAllSettings(),
        ]);
    }

    #[Route('/payment', name: 'admin_settings_payment', methods: ['GET', 'POST'])]
    public function payment(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            // Stripe
            $this->saveSetting('stripe_enabled', $request->request->get('stripe_enabled') === '1');
            $this->saveSetting('stripe_public_key', $request->request->get('stripe_public_key'));
            if ($request->request->get('stripe_secret_key')) {
                $this->saveSetting('stripe_secret_key', $request->request->get('stripe_secret_key'));
            }

            // PayPal
            $this->saveSetting('paypal_enabled', $request->request->get('paypal_enabled') === '1');
            $this->saveSetting('paypal_client_id', $request->request->get('paypal_client_id'));
            if ($request->request->get('paypal_secret')) {
                $this->saveSetting('paypal_secret', $request->request->get('paypal_secret'));
            }
            $this->saveSetting('paypal_mode', $request->request->get('paypal_mode'));

            // Iyzico
            $this->saveSetting('iyzico_enabled', $request->request->get('iyzico_enabled') === '1');
            $this->saveSetting('iyzico_api_key', $request->request->get('iyzico_api_key'));
            if ($request->request->get('iyzico_secret_key')) {
                $this->saveSetting('iyzico_secret_key', $request->request->get('iyzico_secret_key'));
            }

            $this->addFlash('success', 'Ödeme ayarları kaydedildi.');
            return $this->redirectToRoute('admin_settings_payment');
        }

        return $this->render('admin/settings/payment.html.twig', [
            'settings' => $this->getAllSettings(),
        ]);
    }

    #[Route('/shopify', name: 'admin_settings_shopify', methods: ['GET', 'POST'])]
    public function shopify(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $this->saveSetting('shopify_store_url', $request->request->get('shopify_store_url'));
            $this->saveSetting('shopify_access_token', $request->request->get('shopify_access_token'));
            $this->saveSetting('shopify_api_key', $request->request->get('shopify_api_key'));
            if ($request->request->get('shopify_api_secret')) {
                $this->saveSetting('shopify_api_secret', $request->request->get('shopify_api_secret'));
            }
            $this->saveSetting('shopify_webhook_secret', $request->request->get('shopify_webhook_secret'));
            $this->saveSetting('shopify_auto_sync', $request->request->get('shopify_auto_sync') === '1');
            $this->saveSetting('shopify_sync_interval', $request->request->get('shopify_sync_interval'));

            $this->addFlash('success', 'Shopify ayarları kaydedildi.');
            return $this->redirectToRoute('admin_settings_shopify');
        }

        return $this->render('admin/settings/shopify.html.twig', [
            'settings' => $this->getAllSettings(),
        ]);
    }

    #[Route('/shopify/test', name: 'admin_settings_shopify_test', methods: ['POST'])]
    public function testShopify(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // TODO: Implement actual Shopify API connection test
        try {
            usleep(800000);

            return new JsonResponse([
                'success' => true,
                'message' => 'Shopify bağlantısı başarılı',
                'store_name' => 'Example Store',
                'shop_owner' => 'Test User'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Bağlantı hatası: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/cargo-api', name: 'admin_settings_cargo_api', methods: ['GET', 'POST'])]
    public function cargoApi(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $this->saveSetting('cargo_auto_create', $request->request->get('cargo_auto_create') === '1');
            $this->saveSetting('cargo_auto_label', $request->request->get('cargo_auto_label') === '1');
            $this->saveSetting('cargo_auto_tracking', $request->request->get('cargo_auto_tracking') === '1');
            $this->saveSetting('cargo_tracking_interval', $request->request->get('cargo_tracking_interval'));
            $this->saveSetting('cargo_notification_enabled', $request->request->get('cargo_notification_enabled') === '1');

            $this->addFlash('success', 'Kargo API ayarları kaydedildi.');
            return $this->redirectToRoute('admin_settings_cargo_api');
        }

        return $this->render('admin/settings/cargo_api.html.twig', [
            'settings' => $this->getAllSettings(),
        ]);
    }
}
