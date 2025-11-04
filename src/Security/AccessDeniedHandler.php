<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Get the current path
        $currentPath = $request->getPathInfo();

        // If trying to access admin area without permission
        if (str_starts_with($currentPath, '/admin')) {
            // Check if user is authenticated
            if ($this->isUserAuthenticated($request)) {
                // Authenticated but not authorized - redirect to user dashboard with message
                $request->getSession()->getFlashBag()->add(
                    'error',
                    '⛔ Bu sayfaya erişim yetkiniz bulunmamaktadır. Admin yetkisi gereklidir.'
                );
                return new RedirectResponse($this->urlGenerator->generate('user_dashboard'));
            } else {
                // Not authenticated - redirect to login
                $request->getSession()->getFlashBag()->add(
                    'warning',
                    '🔒 Bu sayfayı görüntülemek için lütfen giriş yapın.'
                );
                return new RedirectResponse($this->urlGenerator->generate('app_login'));
            }
        }

        // If trying to access user area without authentication
        if (str_starts_with($currentPath, '/user')) {
            $request->getSession()->getFlashBag()->add(
                'warning',
                '🔒 Bu sayfayı görüntülemek için lütfen giriş yapın.'
            );
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        // Default: redirect to home
        $request->getSession()->getFlashBag()->add(
            'error',
            '⛔ Bu sayfaya erişim yetkiniz bulunmamaktadır.'
        );
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    private function isUserAuthenticated(Request $request): bool
    {
        return $request->getSession()->has('_security_main');
    }
}
