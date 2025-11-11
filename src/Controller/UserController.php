<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Get current user profile.
     */
    #[Route('/api/profile', name: 'api_user_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    /**
     * Update current user profile.
     */
    #[Route('/api/profile', name: 'api_user_profile_update', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return $this->json([
                'error' => 'Invalid JSON data',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Allow updating only specific profile fields
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        // Validate
        $errors = $this->validator->validate($user);
        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation failed',
                'violations' => $errorMessages,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    /**
     * Change user password.
     */
    #[Route('/api/users/{id}/change-password', name: 'api_user_change_password', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(User $user, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Users can only change their own password, or admin can change any user's password in their tenant
        if ($user->getId() !== $currentUser->getId() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Access denied',
            ], Response::HTTP_FORBIDDEN);
        }

        // Ensure same tenant
        $userTenant = $user->getTenant();
        $currentTenant = $currentUser->getTenant();

        if (null === $userTenant || null === $currentTenant || $userTenant->getId() !== $currentTenant->getId()) {
            return $this->json([
                'error' => 'Access denied',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['currentPassword']) && $user->getId() === $currentUser->getId()) {
            return $this->json([
                'error' => 'Current password is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['newPassword'])) {
            return $this->json([
                'error' => 'New password is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verify current password if changing own password
        if ($user->getId() === $currentUser->getId()) {
            if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                return $this->json([
                    'error' => 'Current password is incorrect',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate new password strength
        if (\strlen($data['newPassword']) < 8) {
            return $this->json([
                'error' => 'Password must be at least 8 characters long',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Hash and set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Password changed successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Deactivate a user (soft delete).
     */
    #[Route('/api/users/{id}/deactivate', name: 'api_user_deactivate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deactivate(User $user): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Prevent admins from deactivating themselves
        if ($user->getId() === $currentUser->getId()) {
            return $this->json([
                'error' => 'You cannot deactivate your own account',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Ensure same tenant
        $userTenant = $user->getTenant();
        $currentTenant = $currentUser->getTenant();

        if (null === $userTenant || null === $currentTenant || $userTenant->getId() !== $currentTenant->getId()) {
            return $this->json([
                'error' => 'Access denied',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->deactivate();
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User deactivated successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Activate a user.
     */
    #[Route('/api/users/{id}/activate', name: 'api_user_activate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function activate(User $user): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Ensure same tenant
        $userTenant = $user->getTenant();
        $currentTenant = $currentUser->getTenant();

        if (null === $userTenant || null === $currentTenant || $userTenant->getId() !== $currentTenant->getId()) {
            return $this->json([
                'error' => 'Access denied',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->activate();
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User activated successfully',
        ], Response::HTTP_OK);
    }
}
