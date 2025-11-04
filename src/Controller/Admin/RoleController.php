<?php

namespace App\Controller\Admin;

use App\Entity\Permission;
use App\Entity\Role;
use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/roles', name: 'admin_roles_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class RoleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleRepository $roleRepository,
        private PermissionRepository $permissionRepository
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $roles = $this->roleRepository->getRoleStats();

        return $this->render('admin/roles/list.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    public function view(Role $role): Response
    {
        return $this->render('admin/roles/view.html.twig', [
            'role' => $role,
            'users' => $role->getUsers(),
            'permissions' => $role->getPermissions(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $role = new Role();
            $role->setName($request->request->get('name'));
            $role->setSlug($request->request->get('slug'));
            $role->setDescription($request->request->get('description'));
            $role->setLevel((int)$request->request->get('level', 0));
            $role->setIsSystem(false);

            // Add permissions
            $permissionIds = $request->request->all('permissions') ?? [];
            foreach ($permissionIds as $permissionId) {
                $permission = $this->permissionRepository->find($permissionId);
                if ($permission) {
                    $role->addPermission($permission);
                }
            }

            $this->entityManager->persist($role);
            $this->entityManager->flush();

            $this->addFlash('success', 'Rol başarıyla oluşturuldu.');
            return $this->redirectToRoute('admin_roles_view', ['id' => $role->getId()]);
        }

        $permissions = $this->permissionRepository->findAllGroupedByModule();

        return $this->render('admin/roles/create.html.twig', [
            'permissions' => $permissions,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Role $role): Response
    {
        if ($role->isSystem()) {
            $this->addFlash('error', 'Sistem rolleri düzenlenemez.');
            return $this->redirectToRoute('admin_roles_view', ['id' => $role->getId()]);
        }

        if ($request->isMethod('POST')) {
            $role->setName($request->request->get('name'));
            $role->setSlug($request->request->get('slug'));
            $role->setDescription($request->request->get('description'));
            $role->setLevel((int)$request->request->get('level', 0));

            // Update permissions
            $role->getPermissions()->clear();
            $permissionIds = $request->request->all('permissions') ?? [];
            foreach ($permissionIds as $permissionId) {
                $permission = $this->permissionRepository->find($permissionId);
                if ($permission) {
                    $role->addPermission($permission);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Rol başarıyla güncellendi.');
            return $this->redirectToRoute('admin_roles_view', ['id' => $role->getId()]);
        }

        $permissions = $this->permissionRepository->findAllGroupedByModule();

        return $this->render('admin/roles/edit.html.twig', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Role $role): Response
    {
        if ($role->isSystem()) {
            $this->addFlash('error', 'Sistem rolleri silinemez.');
            return $this->redirectToRoute('admin_roles_view', ['id' => $role->getId()]);
        }

        if ($role->getUserCount() > 0) {
            $this->addFlash('error', 'Bu role atanmış kullanıcılar var. Önce kullanıcıları başka bir role aktarın.');
            return $this->redirectToRoute('admin_roles_view', ['id' => $role->getId()]);
        }

        $this->entityManager->remove($role);
        $this->entityManager->flush();

        $this->addFlash('success', 'Rol başarıyla silindi.');
        return $this->redirectToRoute('admin_roles_index');
    }
}
