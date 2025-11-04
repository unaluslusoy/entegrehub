<?php

namespace App\Controller\User;

use App\Entity\UserLabelTemplate;
use App\Repository\UserLabelTemplateRepository;
use App\Service\Cargo\CargoLabelGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/label-designer')]
#[IsGranted('ROLE_USER')]
class LabelDesignerController extends AbstractController
{
    public function __construct(
        private UserLabelTemplateRepository $templateRepository,
        private EntityManagerInterface $entityManager,
        private CargoLabelGenerator $labelGenerator
    ) {}

    /**
     * Label designer main page - list all templates
     */
    #[Route('', name: 'user_label_designer', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $templates = $this->templateRepository->findActiveByUser($user);

        return $this->render('user/label-designer/index.html.twig', [
            'templates' => $templates,
        ]);
    }

    /**
     * Create new template page
     */
    #[Route('/create', name: 'user_label_designer_create', methods: ['GET'])]
    public function create(): Response
    {
        $availableFields = UserLabelTemplate::getAvailableFields();

        return $this->render('user/label-designer/editor.html.twig', [
            'template' => null,
            'availableFields' => $availableFields,
            'mode' => 'create',
        ]);
    }

    /**
     * Edit existing template
     */
    #[Route('/{id}/edit', name: 'user_label_designer_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        $user = $this->getUser();
        $template = $this->templateRepository->find($id);

        if (!$template || $template->getUser() !== $user) {
            throw $this->createAccessDeniedException('Bu şablona erişim yetkiniz yok.');
        }

        $availableFields = UserLabelTemplate::getAvailableFields();

        return $this->render('user/label-designer/editor.html.twig', [
            'template' => $template,
            'availableFields' => $availableFields,
            'mode' => 'edit',
        ]);
    }

    /**
     * Save template (create or update)
     */
    #[Route('/save', name: 'user_label_designer_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $user = $this->getUser();

        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Geçersiz veri formatı.'
                ], 400);
            }

            // Validate required fields
            if (empty($data['name']) || empty($data['designConfig'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Şablon adı ve tasarım gereklidir.'
                ], 400);
            }

            // Create or update template
            if (isset($data['id']) && $data['id']) {
                $template = $this->templateRepository->find($data['id']);

                if (!$template || $template->getUser() !== $user) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Şablon bulunamadı.'
                    ], 404);
                }
            } else {
                $template = new UserLabelTemplate();
                $template->setUser($user);
            }

            // Set template data
            $template->setName($data['name']);
            $template->setDescription($data['description'] ?? null);
            $template->setDesignConfig($data['designConfig']);
            $template->setWidth($data['width'] ?? 100.0);
            $template->setHeight($data['height'] ?? 150.0);
            $template->setOrientation($data['orientation'] ?? 'portrait');
            $template->setCategory($data['category'] ?? 'custom');

            if (isset($data['isDefault']) && $data['isDefault']) {
                // Unset other default templates
                $this->templateRepository->setAsDefault($template);
            }

            $this->entityManager->persist($template);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Şablon başarıyla kaydedildi.',
                'template_id' => $template->getId()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Şablon kaydedilirken hata: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get template data as JSON
     */
    #[Route('/{id}/data', name: 'user_label_designer_get_data', methods: ['GET'])]
    public function getData(int $id): JsonResponse
    {
        $user = $this->getUser();
        $template = $this->templateRepository->find($id);

        if (!$template || $template->getUser() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Şablon bulunamadı.'
            ], 404);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $template->getId(),
                'name' => $template->getName(),
                'description' => $template->getDescription(),
                'designConfig' => $template->getDesignConfig(),
                'width' => $template->getWidth(),
                'height' => $template->getHeight(),
                'orientation' => $template->getOrientation(),
                'category' => $template->getCategory(),
                'isDefault' => $template->isIsDefault(),
            ]
        ]);
    }

    /**
     * Delete template
     */
    #[Route('/{id}/delete', name: 'user_label_designer_delete', methods: ['POST', 'DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        $template = $this->templateRepository->find($id);

        if (!$template || $template->getUser() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Şablon bulunamadı.'
            ], 404);
        }

        if ($template->isIsDefault()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Varsayılan şablon silinemez. Önce başka bir şablonu varsayılan yapın.'
            ], 400);
        }

        $this->entityManager->remove($template);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Şablon başarıyla silindi.'
        ]);
    }

    /**
     * Duplicate template
     */
    #[Route('/{id}/duplicate', name: 'user_label_designer_duplicate', methods: ['POST'])]
    public function duplicate(int $id): JsonResponse
    {
        $user = $this->getUser();
        $template = $this->templateRepository->find($id);

        if (!$template || $template->getUser() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Şablon bulunamadı.'
            ], 404);
        }

        try {
            $newTemplate = new UserLabelTemplate();
            $newTemplate->setUser($user);
            $newTemplate->setName($template->getName() . ' (Kopya)');
            $newTemplate->setDescription($template->getDescription());
            $newTemplate->setDesignConfig($template->getDesignConfig());
            $newTemplate->setWidth($template->getWidth());
            $newTemplate->setHeight($template->getHeight());
            $newTemplate->setOrientation($template->getOrientation());
            $newTemplate->setCategory($template->getCategory());
            $newTemplate->setIsDefault(false);

            $this->entityManager->persist($newTemplate);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Şablon başarıyla kopyalandı.',
                'template_id' => $newTemplate->getId()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Şablon kopyalanırken hata: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set template as default
     */
    #[Route('/{id}/set-default', name: 'user_label_designer_set_default', methods: ['POST'])]
    public function setDefault(int $id): JsonResponse
    {
        $user = $this->getUser();
        $template = $this->templateRepository->find($id);

        if (!$template || $template->getUser() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Şablon bulunamadı.'
            ], 404);
        }

        try {
            $this->templateRepository->setAsDefault($template);

            return new JsonResponse([
                'success' => true,
                'message' => 'Şablon varsayılan olarak ayarlandı.'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Şablon ayarlanırken hata: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview template with sample data
     */
    #[Route('/{id}/preview', name: 'user_label_designer_preview', methods: ['GET', 'POST'])]
    public function preview(int $id, Request $request): Response
    {
        $user = $this->getUser();
        $template = $this->templateRepository->find($id);

        if (!$template || $template->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // For POST requests, accept temporary design config
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            if (isset($data['designConfig'])) {
                $tempTemplate = clone $template;
                $tempTemplate->setDesignConfig($data['designConfig']);
                $template = $tempTemplate;
            }
        }

        return $this->render('user/label-designer/preview.html.twig', [
            'template' => $template,
            'sampleData' => $this->getSampleData(),
        ]);
    }

    /**
     * Export template as JSON file
     */
    #[Route('/{id}/export', name: 'user_label_designer_export', methods: ['GET'])]
    public function export(int $id): Response
    {
        $user = $this->getUser();
        $template = $this->templateRepository->find($id);

        if (!$template || $template->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $exportData = [
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'designConfig' => $template->getDesignConfig(),
            'width' => $template->getWidth(),
            'height' => $template->getHeight(),
            'orientation' => $template->getOrientation(),
            'category' => $template->getCategory(),
            'exportedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = sprintf('label-template-%s.json', $template->getId());

        return new Response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Import template from JSON file
     */
    #[Route('/import', name: 'user_label_designer_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $user = $this->getUser();

        try {
            $file = $request->files->get('template_file');

            if (!$file) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Dosya yüklenmedi.'
                ], 400);
            }

            $content = file_get_contents($file->getPathname());
            $data = json_decode($content, true);

            if (!$data || !isset($data['designConfig'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Geçersiz şablon dosyası.'
                ], 400);
            }

            $template = new UserLabelTemplate();
            $template->setUser($user);
            $template->setName($data['name'] ?? 'İçe Aktarılan Şablon');
            $template->setDescription($data['description'] ?? null);
            $template->setDesignConfig($data['designConfig']);
            $template->setWidth($data['width'] ?? 100.0);
            $template->setHeight($data['height'] ?? 150.0);
            $template->setOrientation($data['orientation'] ?? 'portrait');
            $template->setCategory($data['category'] ?? 'custom');

            $this->entityManager->persist($template);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Şablon başarıyla içe aktarıldı.',
                'template_id' => $template->getId()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Şablon içe aktarılırken hata: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sample data for preview
     */
    private function getSampleData(): array
    {
        return [
            'shipment' => [
                'trackingNumber' => 'TRK1234567890',
                'serviceType' => 'express',
                'weight' => 2.5,
                'isCOD' => true,
                'codAmount' => 199.90,
                'createdAt' => new \DateTime(),
            ],
            'order' => [
                'orderNumber' => 'ORD-2024-00123',
            ],
            'address' => [
                'firstName' => 'Ahmet',
                'lastName' => 'Yılmaz',
                'company' => 'Örnek Şirket A.Ş.',
                'address1' => 'Atatürk Cad. No: 123',
                'address2' => 'Daire: 4',
                'city' => 'Kadıköy',
                'province' => 'İstanbul',
                'zip' => '34710',
                'country' => 'Türkiye',
                'phone' => '+90 555 123 4567',
            ],
            'company' => [
                'firstName' => 'Kargo',
                'lastName' => 'Entegre',
            ],
            'cargoCompany' => [
                'name' => 'MNG Kargo',
            ],
            'qrCode' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ];
    }
}
