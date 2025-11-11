<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Custom Authentication Success Handler.
 *
 * Extends the default JWT authentication success handler to include
 * a refresh token in the response along with user information.
 */
readonly class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EventDispatcherInterface $dispatcher,
        private RefreshTokenService $refreshTokenService
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JWTAuthenticationSuccessResponse
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            throw new \InvalidArgumentException('Token must contain a valid user');
        }

        return $this->handleAuthenticationSuccess($user);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleAuthenticationSuccess(UserInterface $user, array $payload = []): JWTAuthenticationSuccessResponse
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User must be an instance of App\Entity\User');
        }

        // Generate JWT access token
        $jwt = $this->jwtManager->create($user);

        // Generate refresh token
        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        // Prepare response data
        $responseData = array_merge($payload, [
            'access_token' => $jwt,
            'refresh_token' => $refreshToken->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => 900, // 15 minutes
            'user' => [
                'id' => $user->getId()?->toRfc4122(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'full_name' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'tenant_id' => $user->getTenant()?->getId()?->toRfc4122(),
            ],
        ]);

        // Dispatch authentication success event
        $event = new AuthenticationSuccessEvent($responseData, $user, new JWTAuthenticationSuccessResponse($jwt));
        $this->dispatcher->dispatch($event, Events::AUTHENTICATION_SUCCESS);

        $responseData = array_merge($responseData, $event->getData());

        return new JWTAuthenticationSuccessResponse($jwt, $responseData);
    }
}
