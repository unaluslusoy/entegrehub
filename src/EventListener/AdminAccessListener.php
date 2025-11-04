<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * Admin Access Control Listener
 * Prevents ROLE_USER from accessing admin panel
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 9)]
class AdminAccessListener
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Check if request is for admin area
        if (!str_starts_with($path, '/admin')) {
            return;
        }

        // Allow profiler and web debug toolbar
        if (str_starts_with($path, '/admin/_') || 
            str_starts_with($path, '/_profiler') || 
            str_starts_with($path, '/_wdt')) {
            return;
        }

        // Get current user token
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            // No user logged in - let security firewall handle it
            return;
        }

        $user = $token->getUser();

        // User is logged in, check if they have ROLE_ADMIN
        if (!$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            // User only has ROLE_USER - redirect to user dashboard
            $this->logger->warning('Unauthorized admin access attempt', [
                'user_id' => method_exists($user, 'getId') ? $user->getId() : null,
                'user_email' => method_exists($user, 'getEmail') ? $user->getEmail() : null,
                'path' => $path,
                'ip' => $request->getClientIp(),
            ]);

            // Redirect to user dashboard
            $response = new RedirectResponse(
                $this->urlGenerator->generate('user_dashboard')
            );
            
            $event->setResponse($response);
        }
    }
}
