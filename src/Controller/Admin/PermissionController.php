<?php

namespace App\Controller\Admin;

use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/permissions', name: 'admin_permissions_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class PermissionController extends AbstractController
{
    public function __construct(
        private PermissionRepository $permissionRepository,
        private RoleRepository $roleRepository
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $permissions = $this->permissionRepository->findAllGroupedByModule();
        $roles = $this->roleRepository->findAllOrdered();

        return $this->render('admin/permissions/index.html.twig', [
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }
}
