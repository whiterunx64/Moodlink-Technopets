<?php

declare(strict_types=1);

namespace App\Contracts;

interface SupabaseAuthInterface
{
    // Authentication
    public function adminSignInApiCall(string $email, string $password): array;

    public function verifyAdminCredentialsApiCall(string $email, string $password): array;

    public function refreshAdminAccessTokenApiCall(string $refreshToken): array;

    // Admin Session
    public function getAuthenticatedAdminApiCall(string $accessToken): array;

    public function updateAuthenticatedAdminApiCall(string $accessToken, array $data): array;

    public function updateAdminPasswordApiCall(string $accessToken, string $newPassword): array;

    public function adminSignOutApiCall(string $accessToken): array;

    public function deleteAdminAccountApiCall(string $userId): array;

    public function createAuthUser(string $email, string $password, array $data = [], bool $emailConfirm = true): array;

    public function deleteAuthUser(string $userId): array;

    // Token Validation
    public function verifyJwtTokenApiCall(string $token): array;
}