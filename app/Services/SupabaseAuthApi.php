<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SupabaseAuthInterface;
use App\Enums\JwtAlgorithm;
use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Override;
use Psr\Log\LoggerInterface;

use function count;
use function explode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function strlen;

final class SupabaseAuthApi implements SupabaseAuthInterface
{
    public function __construct(
        private readonly SupabaseClient $client,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $authLogger,
        private readonly LoggerInterface $adminLogger,
    ) {
    }

    // ============================================================
    // Admin Authentication
    // ============================================================

    #[Override]
    public function adminSignInApiCall(string $email, string $password): array
    {
        $this->authLogger->info('Admin login attempt', ['email' => $this->maskEmail($email)]);

        try {
            $response = $this->client->request('POST', '/auth/v1/token', [
                'json' => ['email' => $email, 'password' => $password],
                'query' => ['grant_type' => 'password'],
            ]);

            if (isset($response['user']['id'])) {
                $this->cache->cacheUserData($response['user']['id'], $response['user']);

                $this->authLogger->info('Admin login successful', [
                    'email' => $this->maskEmail($email),
                    'user_id' => $response['user']['id'],
                ]);
            }

            if (isset($response['access_token'])) {
                // Supabase invalidates previous refresh tokens after login
                $this->revokeOtherSessions($response['access_token']);
            }

            return $response;

        } catch (Exception $e) {
            $this->authLogger->error('Admin login failed', [
                'email' => $this->maskEmail($email),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    #[Override]
    public function verifyAdminCredentialsApiCall(string $email, string $password): array
    {
        // Re-authenticate an admin by attempting a Supabase password login.
        try {
            $response = $this->client->request('POST', '/auth/v1/token', [
                'json' => ['email' => $email, 'password' => $password],
                'query' => ['grant_type' => 'password'],
            ]);

            $this->authLogger->info('Admin password verification successful', [
                'email' => $this->maskEmail($email),
            ]);

            return $response;

        } catch (Exception $e) {
            $this->authLogger->warning('Admin password verification failed', [
                'email' => $this->maskEmail($email),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    #[Override]
    public function refreshAdminAccessTokenApiCall(string $refreshToken): array
    {
        $this->authLogger->info('Admin token refresh attempt');

        try {
            $response = $this->client->request('POST', '/auth/v1/token', [
                'json' => ['refresh_token' => $refreshToken],
                'query' => ['grant_type' => 'refresh_token'],
            ]);

            if (isset($response['user']['id'])) {
                $this->cache->cacheUserData($response['user']['id'], $response['user']);
            }

            $this->authLogger->info('Admin token refresh successful');

            return $response;

        } catch (Exception $e) {
            $this->authLogger->error('Admin token refresh failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    #[Override]
    public function adminSignOutApiCall(string $accessToken): array
    {
        try {
            $response = $this->client->request('POST', '/auth/v1/logout', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
            ]);

            $this->authLogger->info('Supabase logout', [
                'action' => 'revoke_access_token',
                'status' => 'success',
            ]);

            return $response;

        } catch (Exception $e) {
            $this->authLogger->error('Admin logout failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ============================================================
    // Admin Account
    // ============================================================

    #[Override]
    public function getAuthenticatedAdminApiCall(string $accessToken): array
    {
        try {
            $response = $this->client->request('GET', '/auth/v1/user', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
            ]);

            if (isset($response['id'])) {
                $this->cache->cacheUserData($response['id'], $response);
            }

            return $response;

        } catch (Exception $e) {
            $this->authLogger->error('Get admin failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    #[Override]
    public function updateAuthenticatedAdminApiCall(string $accessToken, array $data): array
    {
        $this->authLogger->info('Admin update attempt', ['fields' => array_keys($data)]);

        try {
            $response = $this->client->request('PUT', '/auth/v1/user', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
                'json' => $data,
            ]);

            if (isset($response['id'])) {
                $this->cache->invalidateUserCache($response['id']);
                $this->cache->cacheUserData($response['id'], $response);
                $this->authLogger->info('Admin update successful', ['user_id' => $response['id']]);
            }

            return $response;

        } catch (Exception $e) {
            $this->authLogger->error('Admin update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    #[Override]
    public function updateAdminPasswordApiCall(string $accessToken, string $newPassword): array
    {
        $this->authLogger->info('Admin password update attempt');

        try {
            $response = $this->client->request('PUT', '/auth/v1/user', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
                'json' => ['password' => $newPassword],
            ]);

            $this->authLogger->info('Admin password update successful');

            return $response;

        } catch (Exception $e) {
            $this->authLogger->error('Admin password update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    #[Override]
    public function deleteAdminAccountApiCall(string $userId): array
    {
        $this->adminLogger->info('Admin account deletion attempt', ['user_id' => $userId]);

        try {
            $response = $this->client->request('DELETE', "/auth/v1/admin/users/{$userId}", [], useServiceKey: true);

            $this->cache->invalidateUserCache($userId);
            $this->adminLogger->info('Admin account deletion successful', ['user_id' => $userId]);

            return $response;

        } catch (Exception $e) {
            $this->adminLogger->error('Admin account deletion failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ============================================================
    // Student Account Management
    // ============================================================

    #[Override]
    public function deleteAuthUser(string $authUserId): array
    {
        $this->adminLogger->info('Auth user deletion attempt', [
            'auth_user_id' => $authUserId
        ]);

        try {
            $response = $this->client->request('DELETE', "/auth/v1/admin/users/{$authUserId}", [], useServiceKey: true);

            $this->cache->invalidateUserCache($authUserId);
            $this->adminLogger->info('Auth user deletion successful', ['auth_user_id' => $authUserId]);

            return $response;

        } catch (Exception $e) {
            $this->adminLogger->error('Auth user deletion failed', [
                'auth_user_id' => $authUserId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    #[Override]
    public function createAuthUser(string $email, string $password, array $data = [], bool $emailConfirm = true): array
    {
        $this->adminLogger->info('Auth user creation attempt', [
            'email' => $this->maskEmail($email),
            'email_confirm' => $emailConfirm,
        ]);

        try {
            $payload = [
                'email' => $email,
                'password' => $password,
                'email_confirm' => $emailConfirm,
            ];

            if (!empty($data)) {
                $payload['user_metadata'] = $data;
            }

            $response = $this->client->request('POST', '/auth/v1/admin/users', ['json' => $payload], useServiceKey: true);

            $this->adminLogger->info('Auth user creation successful', [
                'email' => $this->maskEmail($email),
                'auth_user_id' => $response['id'] ?? null,
            ]);

            return $response;

        } catch (Exception $e) {
            $this->adminLogger->error('Auth user creation failed', [
                'email' => $this->maskEmail($email),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ============================================================
    // JWT Token Validation
    // ============================================================

    /**
     * @return array{valid: bool, payload?: array, error?: string, expires_at?: mixed}
     */
    #[Override]
    public function verifyJwtTokenApiCall(string $token): array
    {
        try {
            $tokenHash = hash('sha256', $token);
            $cached = $this->cache->getCachedJwtValidation($tokenHash);

            if ($cached !== null) {
                return $cached;
            }

            $leeway = config('supabase-auth.jwt.leeway');
            if (!is_int($leeway) || $leeway < 0) {
                throw new RuntimeException('supabase-auth jwt.leeway config must be a non-negative integer.');
            }
            JWT::$leeway = $leeway; // allow clock skew between servers

            $decoded = $this->decodeToken($token);

            $result = [
                'valid' => true,
                'payload' => (array) $decoded,
                'expires_at' => $decoded->exp ?? null,
            ];

            $this->cache->cacheJwtValidation($tokenHash, $result);

            return $result;

        } catch (Exception $e) {
            $this->authLogger->warning('JWT token validation failed', ['error' => $e->getMessage()]);

            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    // ============================================================
    // Private Helpers
    // ============================================================

    private function revokeOtherSessions(string $accessToken): void
    {
        // Invalidate all other active sessions while keeping the current session alive.
        try {
            $this->client->request('POST', '/auth/v1/logout', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
                'query' => ['scope' => 'others'],
            ]);

            $this->authLogger->info('Supabase sessions revoked after login', [
                'action' => 'revoke_other_sessions',
            ]);
        } catch (Exception $e) {
            $this->authLogger->warning('Failed to revoke other Supabase sessions after login', [
                'action' => 'revoke_other_sessions',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify a token by selecting the algorithm from its own header, so tokens
     * signed with any algorithm Supabase issues (symmetric HS or asymmetric
     * ES/RS via JWKS) validate — including a mix during a key migration.
     *
     * @throws RuntimeException when the token header is malformed or the
     *                          algorithm is not in the configured allow-list.
     */
    private function decodeToken(string $token): object
    {
        $algorithm = $this->algorithmFromToken($token);

        if ($algorithm->isAsymmetric()) {
            // JWKS may hold several keys/algorithms; php-jwt picks by header kid.
            return JWT::decode($token, $this->fetchPublicKeys());
        }

        $secret = config('supabase-auth.jwt.secret');
        if (!is_string($secret) || $secret === '') {
            throw new RuntimeException('supabase-auth jwt.secret must be set to verify HS* tokens.');
        }

        return JWT::decode($token, new Key($secret, $algorithm->value));
    }

    /**
     * Read the (unverified) JWT header, resolve its algorithm, and confirm it is
     * allowed. The signature is still verified afterwards by JWT::decode.
     *
     * @throws RuntimeException on a malformed header or a disallowed algorithm.
     */
    private function algorithmFromToken(string $token): JwtAlgorithm
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new RuntimeException('Malformed JWT: expected three segments.');
        }

        $decodedHeader = base64_decode(strtr($segments[0], '-_', '+/'), true);
        $header = $decodedHeader !== false ? json_decode($decodedHeader, true) : null;

        if (!is_array($header)) {
            throw new RuntimeException('Malformed JWT: unreadable header.');
        }

        $algorithm = JwtAlgorithm::tryFromHeader($header['alg'] ?? null);
        if ($algorithm === null) {
            throw new RuntimeException('Unsupported JWT algorithm in token header.');
        }

        /** @var array<int, string> $allowed */
        $allowed = config('supabase-auth.jwt.allowed_algorithms', []);
        if (!in_array($algorithm->value, $allowed, true)) {
            throw new RuntimeException("JWT algorithm {$algorithm->value} is not allowed.");
        }

        return $algorithm;
    }

    /**
     * @return array<string, Key>
     */
    private function fetchPublicKeys(): array
    {
        // Fetch and cache Supabase JWKS keys used to verify JWT signatures.
        $cacheKey = hash('sha256', '__supabase_jwks__'); // stable cache key for the JWKS endpoint

        $raw = $this->cache->getCachedJwks($cacheKey);

        if ($raw === null) {
            $raw = $this->client->request('GET', '/auth/v1/.well-known/jwks.json');
            $ttl = config('supabase-auth.jwt.ttl');
            if (!is_int($ttl) || $ttl <= 0) {
                throw new RuntimeException('supabase-auth jwt.ttl config must be a positive integer.');
            }
            $this->cache->cacheJwks($cacheKey, $raw, $ttl);
        }

        return JWK::parseKeySet($raw);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return 'invalid@email.com';
        }

        $masked = substr($parts[0], 0, 2) . str_repeat('*', max(0, strlen($parts[0]) - 2));

        return "{$masked}@{$parts[1]}";
    }
}