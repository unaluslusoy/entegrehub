<?php

namespace App\Controller\Admin;

use App\Entity\CargoProvider;
use App\Repository\CargoProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/cargo-providers')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CargoProviderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CargoProviderRepository $providerRepository
    ) {}

    #[Route('', name: 'admin_cargo_providers', methods: ['GET'])]
    public function index(): Response
    {
        $providers = $this->providerRepository->findBy([], ['priority' => 'DESC', 'name' => 'ASC']);

        return $this->render('admin/cargo_provider/index.html.twig', [
            'providers' => $providers,
        ]);
    }

    #[Route('/create', name: 'admin_cargo_providers_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $provider = new CargoProvider();
            $provider->setName($request->request->get('name'));
            $provider->setCode($request->request->get('code'));
            $provider->setDescription($request->request->get('description'));
            $provider->setApiEndpoint($request->request->get('api_endpoint'));
            $provider->setWebhookUrl($request->request->get('webhook_url'));
            $provider->setApiDocumentationUrl($request->request->get('api_documentation_url'));
            $provider->setPriority((int) $request->request->get('priority', 0));
            $provider->setIsActive($request->request->has('is_active'));
            $provider->setTestModeAvailable($request->request->has('test_mode_available'));

            // Handle logo upload
            $logoFile = $request->files->get('logo');
            if ($logoFile) {
                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/cargo_logos',
                        $newFilename
                    );
                    $provider->setLogo('/uploads/cargo_logos/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Logo yüklenirken hata oluştu: '.$e->getMessage());
                }
            }

            // Parse config fields JSON
            $configFieldsJson = $request->request->get('config_fields');
            if ($configFieldsJson) {
                try {
                    $configFields = json_decode($configFieldsJson, true, 512, JSON_THROW_ON_ERROR);
                    $provider->setConfigFields($configFields);
                } catch (\JsonException $e) {
                    $this->addFlash('error', 'Config fields JSON formatı hatalı: '.$e->getMessage());
                    return $this->render('admin/cargo_provider/create.html.twig');
                }
            }

            $this->entityManager->persist($provider);
            $this->entityManager->flush();

            $this->addFlash('success', 'Kargo firması başarıyla oluşturuldu!');
            return $this->redirectToRoute('admin_cargo_providers');
        }

        return $this->render('admin/cargo_provider/create.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_cargo_providers_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CargoProvider $provider): Response
    {
        if ($request->isMethod('POST')) {
            $provider->setName($request->request->get('name'));
            $provider->setCode($request->request->get('code'));
            $provider->setDescription($request->request->get('description'));
            $provider->setApiEndpoint($request->request->get('api_endpoint'));
            $provider->setWebhookUrl($request->request->get('webhook_url'));
            $provider->setApiDocumentationUrl($request->request->get('api_documentation_url'));
            $provider->setPriority((int) $request->request->get('priority', 0));
            $provider->setIsActive($request->request->has('is_active'));
            $provider->setTestModeAvailable($request->request->has('test_mode_available'));

            // Handle logo upload
            $logoFile = $request->files->get('logo');
            if ($logoFile) {
                // Delete old logo if exists
                if ($provider->getLogo()) {
                    $oldLogoPath = $this->getParameter('kernel.project_dir').'/public'.$provider->getLogo();
                    if (file_exists($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                }

                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/cargo_logos',
                        $newFilename
                    );
                    $provider->setLogo('/uploads/cargo_logos/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Logo yüklenirken hata oluştu: '.$e->getMessage());
                }
            }

            // Parse config fields JSON
            $configFieldsJson = $request->request->get('config_fields');
            if ($configFieldsJson) {
                try {
                    $configFields = json_decode($configFieldsJson, true, 512, JSON_THROW_ON_ERROR);
                    $provider->setConfigFields($configFields);
                } catch (\JsonException $e) {
                    $this->addFlash('error', 'Config fields JSON formatı hatalı: '.$e->getMessage());
                    return $this->render('admin/cargo_provider/edit.html.twig', [
                        'provider' => $provider,
                    ]);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Kargo firması güncellendi!');
            return $this->redirectToRoute('admin_cargo_providers');
        }

        return $this->render('admin/cargo_provider/edit.html.twig', [
            'provider' => $provider,
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'admin_cargo_providers_toggle_active', methods: ['POST'])]
    public function toggleActive(CargoProvider $provider): JsonResponse
    {
        $provider->setIsActive(!$provider->isActive());
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'isActive' => $provider->isActive(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_cargo_providers_delete', methods: ['POST'])]
    public function delete(CargoProvider $provider): Response
    {
        // Delete logo file if exists
        if ($provider->getLogo()) {
            $logoPath = $this->getParameter('kernel.project_dir').'/public'.$provider->getLogo();
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }
        }

        $this->entityManager->remove($provider);
        $this->entityManager->flush();

        $this->addFlash('success', 'Kargo firması silindi!');
        return $this->redirectToRoute('admin_cargo_providers');
    }
}
