<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/account')]
class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'admin_account', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $user->setFirstName($data['first_name'] ?? '');
            $user->setLastName($data['last_name'] ?? '');
            $user->setPhone($data['phone'] ?? null);

            $this->entityManager->flush();

            $this->addFlash('success', 'Profil bilgileriniz güncellendi.');
            return $this->redirectToRoute('admin_account');
        }

        return $this->render('admin/account/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/password', name: 'admin_account_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $data['current_password'])) {
                $this->addFlash('error', 'Mevcut şifreniz yanlış.');
                return $this->redirectToRoute('admin_account_password');
            }

            // Check if new passwords match
            if ($data['new_password'] !== $data['confirm_password']) {
                $this->addFlash('error', 'Yeni şifreler eşleşmiyor.');
                return $this->redirectToRoute('admin_account_password');
            }

            // Hash and update password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $data['new_password']
            );
            $user->setPassword($hashedPassword);
            $this->entityManager->flush();

            $this->addFlash('success', 'Şifreniz başarıyla değiştirildi.');
            return $this->redirectToRoute('admin_account');
        }

        return $this->render('admin/account/password.html.twig');
    }
}
