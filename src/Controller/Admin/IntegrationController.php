<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Sistem Entegrasyonları Yönetimi
 * Kargo firmaları, API'ler ve diğer harici servislerin yönetimi
 */
#[Route('/admin/integrations')]
class IntegrationController extends AbstractController
{
    #[Route('/', name: 'admin_integrations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // Sistem entegrasyonları
        $integrations = [
            [
                'id' => 1,
                'category' => 'Kargo Firmaları',
                'name' => 'Aras Kargo',
                'description' => 'Aras Kargo API entegrasyonu',
                'logo' => '/assets/media/cargo/aras.png',
                'isActive' => true,
                'lastSync' => new \DateTime('2024-11-01 10:30:00'),
            ],
            [
                'id' => 2,
                'category' => 'Kargo Firmaları',
                'name' => 'Yurtiçi Kargo',
                'description' => 'Yurtiçi Kargo API entegrasyonu',
                'logo' => '/assets/media/cargo/yurtici.png',
                'isActive' => true,
                'lastSync' => new \DateTime('2024-11-01 09:15:00'),
            ],
            [
                'id' => 3,
                'category' => 'Kargo Firmaları',
                'name' => 'MNG Kargo',
                'description' => 'MNG Kargo API entegrasyonu',
                'logo' => '/assets/media/cargo/mng.png',
                'isActive' => true,
                'lastSync' => new \DateTime('2024-11-01 11:45:00'),
            ],
            [
                'id' => 4,
                'category' => 'Kargo Firmaları',
                'name' => 'PTT Kargo',
                'description' => 'PTT Kargo API entegrasyonu',
                'logo' => '/assets/media/cargo/ptt.png',
                'isActive' => false,
                'lastSync' => null,
            ],
            [
                'id' => 5,
                'category' => 'E-Ticaret',
                'name' => 'Shopify',
                'description' => 'Shopify mağaza entegrasyonu',
                'logo' => '/assets/media/ecommerce/shopify.svg',
                'isActive' => true,
                'lastSync' => new \DateTime('2024-11-01 12:00:00'),
            ],
            [
                'id' => 6,
                'category' => 'Güvenlik',
                'name' => 'Cloudflare',
                'description' => 'CDN ve güvenlik hizmetleri',
                'logo' => '/assets/media/services/cloudflare.svg',
                'isActive' => true,
                'lastSync' => new \DateTime('2024-11-01 08:30:00'),
            ],
            [
                'id' => 7,
                'category' => 'Bildirim',
                'name' => 'SMS Entegrasyonu',
                'description' => 'Toplu SMS gönderimi',
                'logo' => '/assets/media/services/sms.svg',
                'isActive' => true,
                'lastSync' => new \DateTime('2024-11-01 10:00:00'),
            ],
            [
                'id' => 8,
                'category' => 'Bildirim',
                'name' => 'Email Servisi',
                'description' => 'Transactional email gönderimi',
                'logo' => '/assets/media/services/email.svg',
                'isActive' => true,
                'lastSync' => new \DateTime('2024-11-01 09:30:00'),
            ]
        ];

        return $this->render('admin/integration/index.html.twig', [
            'integrations' => $integrations,
        ]);
    }

    #[Route('/{id}/configure', name: 'admin_integrations_configure', methods: ['GET', 'POST'])]
    public function configure(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // TODO: Entegrasyon ayarlarını kaydet
            
            $this->addFlash('success', 'Entegrasyon başarıyla yapılandırıldı.');
            return $this->redirectToRoute('admin_integrations');
        }

        return $this->render('admin/integration/configure.html.twig', [
            'integration_id' => $id,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_integrations_toggle', methods: ['POST'])]
    public function toggle(Request $request, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // TODO: Entegrasyonu aktif/pasif yap
        
        return $this->json([
            'success' => true,
            'message' => 'Entegrasyon durumu güncellendi.',
        ]);
    }

    #[Route('/{id}/sync', name: 'admin_integrations_sync', methods: ['POST'])]
    public function sync(Request $request, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // TODO: Entegrasyonu senkronize et
        
        return $this->json([
            'success' => true,
            'message' => 'Senkronizasyon başlatıldı.',
            'lastSync' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/{id}/logs', name: 'admin_integrations_logs', methods: ['GET'])]
    public function logs(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // TODO: Entegrasyon loglarını göster
        
        return $this->render('admin/integration/logs.html.twig', [
            'integration_id' => $id,
            'logs' => []
        ]);
    }
}
