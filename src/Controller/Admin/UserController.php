<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'admin_users', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        $users = $this->userRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );
        
        $total = $this->userRepository->count([]);
        $pages = ceil($total / $limit);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    #[Route('/create', name: 'admin_users_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Check if email already exists
            if ($this->userRepository->findOneBy(['email' => $data['email']])) {
                $this->addFlash('error', 'Bu e-posta adresi zaten kullanılıyor.');
                return $this->redirectToRoute('admin_users_create');
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['first_name'] ?? '');
            $user->setLastName($data['last_name'] ?? '');
            $user->setPhone($data['phone'] ?? null);
            $user->setRoles([$data['role'] ?? 'ROLE_SUPER_ADMIN']);
            $user->setIsActive($data['is_active'] ?? true);
            
            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $data['password']
            );
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Kullanıcı başarıyla oluşturuldu.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/create.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $user->setFirstName($data['first_name'] ?? '');
            $user->setLastName($data['last_name'] ?? '');
            $user->setPhone($data['phone'] ?? null);
            $user->setRoles([$data['role'] ?? 'ROLE_SUPER_ADMIN']);
            $user->setIsActive(isset($data['is_active']));
            
            // Update password if provided
            if (!empty($data['password'])) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $data['password']
                );
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Kullanıcı başarıyla güncellendi.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'admin_users_toggle_active', methods: ['POST'])]
    public function toggleActive(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user->setIsActive(!$user->isActive());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'is_active' => $user->isActive(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // Prevent deleting own account
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Kendi hesabınızı silemezsiniz.');
            return $this->redirectToRoute('admin_users');
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Kullanıcı başarıyla silindi.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/{id}', name: 'admin_users_view', methods: ['GET'])]
    public function view(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        return $this->render('admin/user/view.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/roles', name: 'admin_users_roles', methods: ['GET', 'POST'])]
    public function manageRoles(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $roleIds = $request->request->all('roles') ?? [];
            
            // Clear existing roles
            foreach ($user->getUserRoles() as $role) {
                $user->removeUserRole($role);
            }
            
            // Add selected roles
            foreach ($roleIds as $roleId) {
                $role = $this->roleRepository->find($roleId);
                if ($role) {
                    $user->addUserRole($role);
                }
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Kullanıcı rolleri başarıyla güncellendi.');
            return $this->redirectToRoute('admin_users_view', ['id' => $user->getId()]);
        }

        $allRoles = $this->roleRepository->findAllOrdered();

        return $this->render('admin/user/roles.html.twig', [
            'user' => $user,
            'allRoles' => $allRoles,
        ]);
    }
}
