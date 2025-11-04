<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HomeController extends AbstractController
{
    /**
     * Homepage - redirect based on authentication status
     */
    public function index(): Response
    {
        // If user is authenticated, redirect based on role
        if ($this->getUser()) {
            // Check if user has super admin or admin role
            if ($this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted('ROLE_SUPER_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            
            // Normal users - redirect to user dashboard
            return $this->redirectToRoute('user_dashboard');
        }

        // If not authenticated, redirect to login
        return $this->redirectToRoute('app_login');
    }
}
