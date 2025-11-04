<?php

namespace App\Controller\User;

use App\Entity\CargoProviderConfig;
use App\Repository\CargoProviderRepository;
use App\Repository\CargoProviderConfigRepository;
use App\Service\Cargo\CargoApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/cargo-integrations')]
class CargoIntegrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CargoProviderRepository $providerRepository,
        private CargoProviderConfigRepository $configRepository,
        private CargoApiService $cargoApiService
    ) {}

    #[Route('', name: 'user_cargo_integrations', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $activeProviders = $this->providerRepository->findActive();
        $userConfigs = $this->configRepository->findAllByUserWithProvider($user);

        // Map configs by provider ID for easy access
        $configsByProvider = [];
        foreach ($userConfigs as $config) {
            $configsByProvider[$config->getProvider()->getId()] = $config;
        }

        return $this->render('user/cargo_integration/index.html.twig', [
            'providers' => $activeProviders,
            'configsByProvider' => $configsByProvider,
        ]);
    }

    #[Route('/{id}/configure', name: 'user_cargo_integration_configure', methods: ['GET', 'POST'])]
    public function configure(Request $request, int $id): Response
    {
        $user = $this->getUser();
        $provider = $this->providerRepository->find($id);

        if (!$provider || !$provider->isActive()) {
            $this->addFlash('error', 'Kargo firması bulunamadı veya aktif değil.');
            return $this->redirectToRoute('user_cargo_integrations');
        }

        $config = $this->configRepository->findByUserAndProvider($user, $provider);

        if ($request->isMethod('POST')) {
            if (!$config) {
                $config = new CargoProviderConfig();
                $config->setUser($user);
                $config->setProvider($provider);
            }

            // Collect credentials from form
            $credentials = [];
            foreach ($provider->getConfigFields() ?? [] as $field) {
                $fieldName = $field['name'];
                $credentials[$fieldName] = $request->request->get($fieldName);
            }

            $config->setCredentials($credentials);
            $config->setIsTestMode($request->request->has('is_test_mode'));
            $config->setIsActive($request->request->has('is_active'));
            $config->setWebhookSecret($request->request->get('webhook_secret'));

            $this->entityManager->persist($config);
            $this->entityManager->flush();

            $this->addFlash('success', 'Kargo entegrasyonu başarıyla kaydedildi!');
            return $this->redirectToRoute('user_cargo_integrations');
        }

        return $this->render('user/cargo_integration/configure.html.twig', [
            'provider' => $provider,
            'config' => $config,
        ]);
    }

    #[Route('/{id}/test', name: 'user_cargo_integration_test', methods: ['POST'])]
    public function testConnection(int $id): JsonResponse
    {
        $user = $this->getUser();
        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return $this->json(['success' => false, 'message' => 'Kargo firması bulunamadı'], 404);
        }

        $config = $this->configRepository->findByUserAndProvider($user, $provider);

        if (!$config) {
            return $this->json(['success' => false, 'message' => 'Entegrasyon yapılandırması bulunamadı'], 404);
        }

        // Use CargoApiService to test provider connection
        $result = $this->cargoApiService->testProviderConnection(
            $provider,
            $config->getCredentials() ?? []
        );

        // Update config with test results
        $config->setTestConnectionStatus($result['success'] ? 'success' : 'failed');
        $config->setTestConnectionMessage($result['message']);
        $config->setLastTestAt(new \DateTime());

        $this->entityManager->flush();

        return $this->json($result);
    }

    #[Route('/{id}/toggle', name: 'user_cargo_integration_toggle', methods: ['POST'])]
    public function toggle(int $id): JsonResponse
    {
        $user = $this->getUser();
        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return $this->json(['success' => false], 404);
        }

        $config = $this->configRepository->findByUserAndProvider($user, $provider);

        if (!$config) {
            return $this->json(['success' => false, 'message' => 'Entegrasyon bulunamadı'], 404);
        }

        $config->setIsActive(!$config->isActive());
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'isActive' => $config->isActive(),
        ]);
    }
}
