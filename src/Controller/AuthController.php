<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect based on role
        if ($this->getUser()) {
            $roles = $this->getUser()->getRoles();

            // Check for SUPER_ADMIN, otherwise redirect to user dashboard
            if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_dashboard');
            }

            return $this->redirectToRoute('user_dashboard');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('pages/auth/signin.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method should be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        // If user is already logged in, redirect based on role
        if ($this->getUser()) {
            $roles = $this->getUser()->getRoles();

            if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('user_dashboard');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            // Validate input
            if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
                $this->addFlash('error', 'Lütfen zorunlu alanları doldurun. Ad, soyad, e-posta ve şifre gereklidir.');
                return $this->render('pages/auth/signup.html.twig', ['data' => $data]);
            }

            // Check if email already exists
            if ($this->userRepository->findOneBy(['email' => $data['email']])) {
                $this->addFlash('error', 'Bu e-posta adresi zaten kayıtlı. Giriş yapmayı deneyin veya farklı bir e-posta kullanın.');
                return $this->render('pages/auth/signup.html.twig', ['data' => $data]);
            }

            // Check password confirmation
            if ($data['password'] !== $data['password_confirm']) {
                $this->addFlash('error', 'Girdiğiniz şifreler eşleşmiyor. Lütfen aynı şifreyi iki kez girin.');
                return $this->render('pages/auth/signup.html.twig', ['data' => $data]);
            }

            try {
                // Create new user
                $user = new User();
                $user->setEmail($data['email']);
                $user->setFirstName($data['first_name']);
                $user->setLastName($data['last_name']);
                $user->setPhone($data['phone'] ?? null);
                $user->setRoles(['ROLE_USER']);
                
                // Hash password
                $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);

                // Validate entity
                $errors = $this->validator->validate($user);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    return $this->render('pages/auth/signup.html.twig', ['data' => $data]);
                }

                // Save user
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('success', '🎉 Hesabınız başarıyla oluşturuldu! Şimdi giriş yaparak EntegreHub\'a başlayabilirsiniz.');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->logger->error('Kullanıcı kaydı sırasında hata oluştu', [
                    'email' => $data['email'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $this->addFlash('error', 'Kayıt işlemi sırasında bir sorun oluştu. Lütfen daha sonra tekrar deneyin.');
                return $this->render('pages/auth/signup.html.twig', ['data' => $data]);
            }
        }

        return $this->render('pages/auth/signup.html.twig');
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email and password are required.'
            ], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        if (!$user->isActive()) {
            return $this->json([
                'success' => false,
                'message' => 'Account is inactive.'
            ], 403);
        }

        // Update last login
        $user->setLastLoginAt(new \DateTime());
        $this->entityManager->flush();

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function apiRegister(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate input
        if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
            return $this->json([
                'success' => false,
                'message' => 'All fields are required.'
            ], 400);
        }

        // Check if email already exists
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json([
                'success' => false,
                'message' => 'Email already exists.'
            ], 409);
        }

        try {
            // Create new user
            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setPhone($data['phone'] ?? null);
            $user->setRoles(['ROLE_USER']);
            
            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Validate entity
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $errorMessages
                ], 422);
            }

            // Save user
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            return $this->json([
                'success' => true,
                'message' => 'Registration successful.',
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'roles' => $user->getRoles()
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'Not authenticated.'
            ], 401);
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'roles' => $user->getRoles(),
                'is_2fa_enabled' => $user->is2faEnabled(),
                'locale' => $user->getLocale(),
                'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/reset-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()) {
            $roles = $this->getUser()->getRoles();

            if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('user_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            // Validate CSRF token
            $submittedToken = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('reset_password', $submittedToken)) {
                $this->addFlash('error', 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.');
                return $this->render('pages/auth/reset-password.html.twig');
            }

            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Generate reset token
                $resetToken = bin2hex(random_bytes(32));
                $user->setResetToken($resetToken);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                
                $this->entityManager->flush();
                
                // Generate reset URL
                $resetUrl = $this->generateUrl('app_reset_password_token', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);
                
                try {
                    // Send email
                    $email = (new TemplatedEmail())
                        ->from(new Address('yonetici@entegrehub.com', 'EntegreHub Kargo'))
                        ->to($user->getEmail())
                        ->subject('Şifre Sıfırlama Talebi - EntegreHub')
                        ->htmlTemplate('emails/reset_password.html.twig')
                        ->context([
                            'user' => $user,
                            'resetUrl' => $resetUrl,
                            'expiresAt' => $user->getResetTokenExpiresAt()
                        ]);
                    
                    $this->mailer->send($email);
                    
                    $this->addFlash('success', '✉️ Şifre sıfırlama bağlantısı e-posta adresinize gönderildi. Bağlantı 1 saat geçerlidir.');
                } catch (\Exception $e) {
                    $this->logger->error('Failed to send reset password email', [
                        'email' => $user->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                    $this->addFlash('error', 'E-posta gönderilirken bir hata oluştu. Lütfen tekrar deneyin.');
                }
            } else {
                // Don't reveal if email exists or not (security best practice)
                $this->addFlash('info', 'Eğer bu e-posta sistemimizde kayıtlıysa, şifre sıfırlama bağlantısı gönderilmiştir.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/auth/reset-password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password_token', methods: ['GET', 'POST'])]
    public function resetPasswordWithToken(Request $request, string $token): Response
    {
        if ($this->getUser()) {
            $roles = $this->getUser()->getRoles();

            if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('user_dashboard');
        }

        $user = $this->userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', '❌ Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş. Lütfen yeni bir bağlantı talep edin.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            // Validate CSRF token
            $submittedToken = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('reset_password', $submittedToken)) {
                $this->addFlash('error', 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.');
                return $this->render('pages/auth/new-password.html.twig', ['token' => $token]);
            }

            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            if (empty($password) || empty($passwordConfirm)) {
                $this->addFlash('error', 'Lütfen şifre alanlarını doldurun.');
                return $this->render('pages/auth/new-password.html.twig', ['token' => $token]);
            }

            if ($password !== $passwordConfirm) {
                $this->addFlash('error', 'Girdiğiniz şifreler eşleşmiyor.');
                return $this->render('pages/auth/new-password.html.twig', ['token' => $token]);
            }

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Şifreniz en az 8 karakter olmalıdır.');
                return $this->render('pages/auth/new-password.html.twig', ['token' => $token]);
            }

            try {
                // Update password
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                
                $this->entityManager->flush();

                $this->addFlash('success', '🎉 Şifreniz başarıyla güncellendi! Artık yeni şifrenizle giriş yapabilirsiniz.');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->logger->error('Password reset failed', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage()
                ]);
                $this->addFlash('error', '❌ Şifre güncellenirken bir hata oluştu. Lütfen tekrar deneyin.');
                return $this->render('pages/auth/new-password.html.twig', ['token' => $token]);
            }
        }

        return $this->render('pages/auth/new-password.html.twig', ['token' => $token]);
    }
}