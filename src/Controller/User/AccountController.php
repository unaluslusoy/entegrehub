<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/account')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Account overview page
     */
    #[Route('', name: 'user_account', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('user/account/index.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Profile settings page
     */
    #[Route('/profile', name: 'user_account_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $user->setEmail($request->request->get('email'));
                $user->setFirstName($request->request->get('first_name'));
                $user->setLastName($request->request->get('last_name'));
                $user->setPhone($request->request->get('phone'));

                // Optional fields
                if ($request->request->get('company')) {
                    $user->setCompany($request->request->get('company'));
                }

                if ($request->request->get('address')) {
                    $user->setAddress($request->request->get('address'));
                }

                $this->entityManager->flush();

                $this->addFlash('success', 'Profil bilgileriniz güncellendi.');
                return $this->redirectToRoute('user_account_profile');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Profil güncellenirken hata oluştu: ' . $e->getMessage());
            }
        }

        return $this->render('user/account/profile.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Change password page
     */
    #[Route('/password', name: 'user_account_password', methods: ['GET', 'POST'])]
    public function password(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validate current password
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Mevcut şifreniz yanlış.');
                return $this->redirectToRoute('user_account_password');
            }

            // Validate new password
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Yeni şifreler eşleşmiyor.');
                return $this->redirectToRoute('user_account_password');
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Yeni şifre en az 6 karakter olmalıdır.');
                return $this->redirectToRoute('user_account_password');
            }

            // Update password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $this->entityManager->flush();

            $this->addFlash('success', 'Şifreniz başarıyla değiştirildi.');
            return $this->redirectToRoute('user_account_password');
        }

        return $this->render('user/account/password.html.twig');
    }

    /**
     * Notification settings
     */
    #[Route('/notifications', name: 'user_account_notifications', methods: ['GET', 'POST'])]
    public function notifications(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $settings = [
                'email_on_new_order' => $request->request->get('email_on_new_order') === '1',
                'email_on_shipment_update' => $request->request->get('email_on_shipment_update') === '1',
                'email_on_delivery' => $request->request->get('email_on_delivery') === '1',
                'email_weekly_report' => $request->request->get('email_weekly_report') === '1',
                'sms_on_delivery' => $request->request->get('sms_on_delivery') === '1',
            ];

            $user->setNotificationSettings($settings);
            $this->entityManager->flush();

            $this->addFlash('success', 'Bildirim ayarlarınız güncellendi.');
            return $this->redirectToRoute('user_account_notifications');
        }

        return $this->render('user/account/notifications.html.twig', [
            'user' => $user,
            'settings' => $user->getNotificationSettings() ?? [],
        ]);
    }

    /**
     * API keys management
     */
    #[Route('/api-keys', name: 'user_account_api_keys', methods: ['GET'])]
    public function apiKeys(): Response
    {
        $user = $this->getUser();

        return $this->render('user/account/api_keys.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Generate new API key
     */
    #[Route('/api-keys/generate', name: 'user_account_api_keys_generate', methods: ['POST'])]
    public function generateApiKey(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $apiKey = bin2hex(random_bytes(32));
        $user->setApiKey($apiKey);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'api_key' => $apiKey,
            'message' => 'Yeni API anahtarı oluşturuldu.'
        ]);
    }

    /**
     * Delete account page
     */
    #[Route('/delete', name: 'user_account_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            /** @var User $user */
            $user = $this->getUser();

            $password = $request->request->get('password');
            $confirmation = $request->request->get('confirmation');

            // Verify password
            if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Şifreniz yanlış.');
                return $this->redirectToRoute('user_account_delete');
            }

            // Verify confirmation
            if ($confirmation !== 'DELETE') {
                $this->addFlash('error', 'Onay metni yanlış. Lütfen "DELETE" yazın.');
                return $this->redirectToRoute('user_account_delete');
            }

            // Mark account as inactive instead of deleting
            $user->setIsActive(false);
            $user->setDeletedAt(new \DateTime());
            $this->entityManager->flush();

            // Logout
            $this->container->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();

            $this->addFlash('success', 'Hesabınız kapatıldı.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/account/delete.html.twig');
    }
}
