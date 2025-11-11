<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\JwtTokenBlacklistService;
use App\Service\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly JwtTokenBlacklistService $blacklistService
    ) {
    }

    /**
     * Login endpoint - handled by json_login firewall.
     * This is just for documentation purposes.
     *
     * The actual authentication is handled by security.yaml's json_login.
     */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This method won't be called as the firewall handles it
        // But we keep it for route documentation
        return new JsonResponse([
            'message' => 'Login endpoint',
        ]);
    }

    /**
     * Refresh token endpoint.
     *
     * Exchange a valid refresh token for a new access token and refresh token.
     */
    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['refresh_token'])) {
                return new JsonResponse([
                    'error' => 'Refresh token is required',
                ], Response::HTTP_BAD_REQUEST);
            }

            $refreshTokenString = $data['refresh_token'];

            // Validate refresh token
            $refreshToken = $this->refreshTokenService->getValidRefreshToken($refreshTokenString);

            if ($refreshToken === null) {
                return new JsonResponse([
                    'error' => 'Invalid or expired refresh token',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user = $refreshToken->getUser();

            // Ensure user exists and is active
            if ($user === null || !$user->isActive()) {
                return new JsonResponse([
                    'error' => 'User account is inactive or not found',
                ], Response::HTTP_FORBIDDEN);
            }

            // Revoke old refresh token
            $this->refreshTokenService->revokeRefreshToken($refreshToken);

            // Generate new tokens
            $newAccessToken = $this->jwtManager->create($user);
            $newRefreshToken = $this->refreshTokenService->createRefreshToken($user);

            return new JsonResponse([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken->getToken(),
                'token_type' => 'Bearer',
                'expires_in' => 900, // 15 minutes
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred during token refresh',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout endpoint.
     *
     * Blacklist the current access token and optionally revoke refresh token.
     */
    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if ($user === null) {
                return new JsonResponse([
                    'error' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Get JWT token from Authorization header
            $authHeader = $request->headers->get('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $jwtToken = substr($authHeader, 7);

                // Decode token to get JTI
                try {
                    $tokenParts = explode('.', $jwtToken);
                    if (count($tokenParts) === 3) {
                        $payload = json_decode(base64_decode($tokenParts[1]), true);
                        if (isset($payload['jti'], $payload['exp'])) {
                            $this->blacklistService->blacklist($payload['jti'], (int) $payload['exp']);
                        }
                    }
                } catch (\Exception) {
                    // Ignore token parsing errors during logout
                }
            }

            // Revoke refresh token if provided
            $data = json_decode($request->getContent(), true);
            if (isset($data['refresh_token'])) {
                $refreshToken = $this->refreshTokenService->getValidRefreshToken($data['refresh_token']);
                if ($refreshToken !== null) {
                    $this->refreshTokenService->revokeRefreshToken($refreshToken);
                }
            }

            return new JsonResponse([
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred during logout',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
