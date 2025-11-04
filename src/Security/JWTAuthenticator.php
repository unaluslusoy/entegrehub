<?php

namespace App\Security;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator as BaseAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class JWTAuthenticator extends BaseAuthenticator
{
    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $eventDispatcher,
        TokenExtractorInterface $tokenExtractor,
        UserProviderInterface $userProvider,
        private UserRepository $userRepository
    ) {
        parent::__construct($jwtManager, $eventDispatcher, $tokenExtractor, $userProvider);
    }

    /**
     * Update last login timestamp when user authenticates via JWT
     */
    protected function loadUser(array $payload, string $identity): \Symfony\Component\Security\Core\User\UserInterface
    {
        $user = parent::loadUser($payload, $identity);

        if ($user) {
            // Update last login
            $userEntity = $this->userRepository->find($user->getId());
            if ($userEntity) {
                $userEntity->setLastLoginAt(new \DateTime());
                $this->userRepository->save($userEntity);
            }
        }

        return $user;
    }
}
