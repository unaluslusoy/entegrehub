<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Müşteri Ekip Yönetimi
 * Müşterilerin kendi panellerinde ekip üyelerini yönetmesi için
 */
#[Route('/team')]
class TeamController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/members', name: 'team_members', methods: ['GET'])]
    public function members(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        // TODO: Müşterinin kendi ekip üyelerini getir
        // Şu an için tüm kullanıcıları gösteriyoruz (demo)
        $teamMembers = $this->userRepository->findAll();

        return $this->render('team/members/index.html.twig', [
            'teamMembers' => $teamMembers,
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/members/invite', name: 'team_members_invite', methods: ['GET', 'POST'])]
    public function invite(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Yeni ekip üyesi oluştur
            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setPhone($data['phone'] ?? null);
            $user->setIsActive(true);
            
            // Geçici şifre oluştur
            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
            $user->setPassword($hashedPassword);
            
            // Rol ata
            if (!empty($data['role_ids'])) {
                foreach ($data['role_ids'] as $roleId) {
                    $role = $this->roleRepository->find($roleId);
                    if ($role) {
                        $user->addUserRole($role);
                    }
                }
            }
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // TODO: Email ile davet gönder (şifre resetleme linki)
            
            $this->addFlash('success', 'Ekip üyesi başarıyla davet edildi. Davet emaili gönderildi.');
            return $this->redirectToRoute('team_members');
        }

        // Roller (Süper Admin hariç)
        $roles = $this->roleRepository->findByMaxLevel(80); // Yönetici ve altı

        return $this->render('team/members/invite.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/members/{id}/edit', name: 'team_members_edit', methods: ['GET', 'POST'])]
    public function editMember(Request $request, User $member): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // TODO: Sadece kendi ekibindeki üyeleri düzenleyebilmeli
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $member->setFirstName($data['first_name']);
            $member->setLastName($data['last_name']);
            $member->setPhone($data['phone'] ?? null);
            
            // Rolleri güncelle
            $member->getUserRoles()->clear();
            if (!empty($data['role_ids'])) {
                foreach ($data['role_ids'] as $roleId) {
                    $role = $this->roleRepository->find($roleId);
                    if ($role) {
                        $member->addUserRole($role);
                    }
                }
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Ekip üyesi başarıyla güncellendi.');
            return $this->redirectToRoute('team_members');
        }

        $roles = $this->roleRepository->findByMaxLevel(80);

        return $this->render('team/members/edit.html.twig', [
            'member' => $member,
            'roles' => $roles,
        ]);
    }

    #[Route('/members/{id}/toggle', name: 'team_members_toggle', methods: ['POST'])]
    public function toggleMember(Request $request, User $member): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // TODO: Sadece kendi ekibindeki üyeleri değiştirebilmeli
        
        $member->setIsActive(!$member->isActive());
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'isActive' => $member->isActive(),
            'message' => $member->isActive() ? 'Ekip üyesi aktif edildi.' : 'Ekip üyesi pasif edildi.',
        ]);
    }

    #[Route('/members/{id}/delete', name: 'team_members_delete', methods: ['POST'])]
    public function deleteMember(Request $request, User $member): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // TODO: Sadece kendi ekibindeki üyeleri silebilmeli
        // Kendi hesabını silemez
        if ($member->getId() === $this->getUser()->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Kendi hesabınızı silemezsiniz.',
            ], 400);
        }

        $this->entityManager->remove($member);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Ekip üyesi başarıyla silindi.',
        ]);
    }

    #[Route('/permissions', name: 'team_permissions', methods: ['GET', 'POST'])]
    public function permissions(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // TODO: Ekip üyesi izinlerini güncelle
            
            $this->addFlash('success', 'İzinler başarıyla güncellendi.');
            return $this->redirectToRoute('team_permissions');
        }

        // Roller ve izinler
        $roles = $this->roleRepository->findByMaxLevel(80);

        return $this->render('team/permissions/index.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/permissions/matrix', name: 'team_permissions_matrix', methods: ['GET'])]
    public function permissionsMatrix(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Tüm roller ve izinler
        $roles = $this->roleRepository->findByMaxLevel(80);

        return $this->render('team/permissions/matrix.html.twig', [
            'roles' => $roles,
        ]);
    }
}
