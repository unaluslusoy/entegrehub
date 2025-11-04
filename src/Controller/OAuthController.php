<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OAuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * Google OAuth connect - Redirect to Google
     */
    public function connectGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    /**
     * Google OAuth callback - Handle the response from Google
     */
    public function googleCallback(
        ClientRegistry $clientRegistry,
        Request $request
    ): Response {
        try {
            // Get the OAuth client
            $client = $clientRegistry->getClient('google');
            
            // Get the access token
            $accessToken = $client->getAccessToken();
            
            // Get user info from Google
            /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
            $googleUser = $client->fetchUserFromToken($accessToken);
            
            $email = $googleUser->getEmail();
            $firstName = $googleUser->getFirstName() ?? '';
            $lastName = $googleUser->getLastName() ?? '';
            $googleId = $googleUser->getId();
            
            // Check if user exists
            $user = $this->userRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setGoogleId($googleId);
                
                // Set a random password (user won't use it)
                $randomPassword = bin2hex(random_bytes(32));
                $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
                $user->setPassword($hashedPassword);
                
                // Set default role
                $user->setRoles(['ROLE_SUPER_ADMIN']);
                
                // Enable the account
                $user->setIsActive(true);
                
                $this->entityManager->persist($user);
                $this->addFlash('success', 'Hesabınız başarıyla oluşturuldu! Hoş geldiniz.');
            } else {
                // Update Google ID if not set
                if (!$user->getGoogleId()) {
                    $user->setGoogleId($googleId);
                }
                
                // Update last login
                $user->setLastLoginAt(new \DateTime());
                
                $this->addFlash('success', 'Google ile giriş başarılı! Hoş geldiniz.');
            }
            
            $this->entityManager->flush();
            
            // Create authentication token
            $token = new UsernamePasswordToken(
                $user,
                'main', // firewall name
                $user->getRoles()
            );
            
            // Store token in token storage
            $this->tokenStorage->setToken($token);
            
            // Save token to session
            $request->getSession()->set('_security_main', serialize($token));
            
            // Redirect to homepage, it will handle role-based redirects
            return $this->redirectToRoute('homepage');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Google ile giriş yapılırken bir hata oluştu: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * Apple OAuth connect
     */
    public function connectApple(Request $request): Response
    {
        // TODO: Implement Apple OAuth integration
        $this->addFlash('info', 'Apple ile giriş özelliği yakında aktif olacak. Şu an için e-posta veya Google ile giriş yapabilirsiniz.');
        return $this->redirectToRoute('app_login');
    }
}
