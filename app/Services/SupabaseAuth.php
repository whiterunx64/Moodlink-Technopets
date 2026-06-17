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
use function is_int;
use function strlen;

/**
 * Supabase authentication service handling all auth operations:
 * - sign up / sign in / sign out
 * - token refresh
 * - JWT validation
 * - user CRUD operations via Supabase Auth API
 */
final class SupabaseAuth implements SupabaseAuthInterface
{
    public function __construct(
        private readonly SupabaseClient $client,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Register a new user in Supabase.
     *
     * @param string $email
     * @param string $password
     * @param array $data Optional user metadata
     * @return array API response
     * @throws Exception
     */
    #[Override]


    /**
     * Authenticate user with email and password.
     *
     * @throws Exception
     */
    #[Override]
    public function signIn(string $email, string $password): array
    {
        $this->logger->info('User login attempt', ['email' => $this->maskEmail($email)]);

        try {
            $response = $this->client->request('POST', '/auth/v1/token', [
                'json' => ['email' => $email, 'password' => $password],
                'query' => ['grant_type' => 'password'],
            ]);

            if (isset($response['user']['id'])) {
                $this->cache->cacheUserData($response['user']['id'], $response['user']);

                $this->logger->info('User login successful', [
                    'email' => $this->maskEmail($email),
                    'user_id' => $response['user']['id'],
                ]);
            }

            return $response;

        } catch (Exception $e) {
            $this->logger->error('User login failed', [
                'email' => $this->maskEmail($email),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sign out user from Supabase.
     *
     * @throws Exception
     */
    #[Override]
    public function signOut(string $accessToken): array
    {
        try {
            $response = $this->client->request('POST', '/auth/v1/logout', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
            ]);

            $this->logger->info('User logout successful');

            return $response;

        } catch (Exception $e) {
            $this->logger->error('User logout failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Refresh authentication token.
     *
     * @throws Exception
     */
    #[Override]
    public function refreshToken(string $refreshToken): array
    {
        $this->logger->info('Token refresh attempt');

        try {
            $response = $this->client->request('POST', '/auth/v1/token', [
                'json' => ['refresh_token' => $refreshToken],
                'query' => ['grant_type' => 'refresh_token'],
            ]);

            if (isset($response['user']['id'])) {
                $this->cache->cacheUserData($response['user']['id'], $response['user']);
            }

            $this->logger->info('Token refresh successful');

            return $response;

        } catch (Exception $e) {
            $this->logger->error('Token refresh failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get authenticated user from Supabase.
     *
     * @throws Exception
     */
    #[Override]
    public function getUser(string $accessToken): array
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
            $this->logger->error('Get user failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update user metadata or profile.
     *
     * @throws Exception
     */
    #[Override]
    public function updateUser(string $accessToken, array $data): array
    {
        $this->logger->info('User update attempt', ['fields' => array_keys($data)]);

        try {
            $response = $this->client->request('PUT', '/auth/v1/user', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
                'json' => $data,
            ]);

            if (isset($response['id'])) {
                $this->cache->invalidateUserCache($response['id']);
                $this->cache->cacheUserData($response['id'], $response);
                $this->logger->info('User update successful', ['user_id' => $response['id']]);
            }

            return $response;

        } catch (Exception $e) {
            $this->logger->error('User update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Send password reset email.
     *
     * @throws Exception
     */
    #[Override]
    public function resetPasswordForEmail(string $email, ?string $redirectTo = null): array
    {
        $this->logger->info('Password reset request', ['email' => $this->maskEmail($email)]);

        try {
            $data = ['email' => $email];

            if ($redirectTo !== null) {
                $data['redirectTo'] = $redirectTo;
            }

            $response = $this->client->request('POST', '/auth/v1/recover', ['json' => $data]);

            $this->logger->info('Password reset email sent', ['email' => $this->maskEmail($email)]);

            return $response;

        } catch (Exception $e) {
            $this->logger->error('Password reset failed', [
                'email' => $this->maskEmail($email),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update user password.
     *
     * @throws Exception
     */
    #[Override]
    public function updatePassword(string $accessToken, string $newPassword): array
    {
        $this->logger->info('Password update attempt');

        try {
            $response = $this->client->request('PUT', '/auth/v1/user', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
                'json' => ['password' => $newPassword],
            ]);

            $this->logger->info('Password update successful');

            return $response;

        } catch (Exception $e) {
            $this->logger->error('Password update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify and decode JWT token.
     *
     * @return array{valid: bool, payload?: array, error?: string, expires_at?: mixed}
     */
    #[Override]
    public function verifyToken(string $token): array
    {
        try {
            $tokenHash = hash('sha256', $token);
            $cached = $this->cache->getCachedJwtValidation($tokenHash);

            if ($cached !== null) {
                return $cached;
            }

            $algorithm = JwtAlgorithm::fromConfig();
            $leeway = config('supabase-auth.jwt.leeway');
            if (!is_int($leeway) || $leeway < 0) {
                throw new RuntimeException('supabase-auth jwt.leeway config must be a non-negative integer.');
            }
            JWT::$leeway = $leeway;

            $decoded = $algorithm->isAsymmetric()
                ? JWT::decode($token, $this->fetchPublicKeys())
                : JWT::decode($token, new Key(config('supabase-auth.jwt.secret'), $algorithm->value));

            $result = [
                'valid' => true,
                'payload' => (array) $decoded,
                'expires_at' => $decoded->exp ?? null,
            ];

            $this->cache->cacheJwtValidation($tokenHash, $result);

            return $result;

        } catch (Exception $e) {
            $this->logger->warning('JWT token validation failed', ['error' => $e->getMessage()]);

            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a user via the admin endpoint with the email already confirmed.
     *
     * Unlike signUp() (the public /signup flow, which leaves the email
     * unconfirmed and blocks sign-in until verification), this provisions a
     * ready-to-use account — matching how the Supabase dashboard creates users.
     *
     * @param string $email
     * @param string $password
     * @param array $data Optional user metadata
     * @param bool $emailConfirm Mark the email as confirmed on creation
     * @return array API response (user object at top level)
     * @throws Exception
     */
    #[Override]
    public function createUser(string $email, string $password, array $data = [], bool $emailConfirm = true): array
    {
        $this->logger->info('Admin user creation attempt', [
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

            $response = $this->client->request('POST', '/auth/v1/admin/users', ['json' => $payload], true);

            $this->logger->info('Admin user creation successful', [
                'email' => $this->maskEmail($email),
                'user_id' => $response['id'] ?? 'unknown',
            ]);

            return $response;

        } catch (Exception $e) {
            $this->logger->error('Admin user creation failed', [
                'email' => $this->maskEmail($email),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get user by ID (admin endpoint).
     */
    #[Override]
    public function getUserById(string $userId): array
    {
        try {
            $cached = $this->cache->getCachedUserData($userId);

            if ($cached !== null) {
                return $cached;
            }

            $response = $this->client->request('GET', "/auth/v1/admin/users/{$userId}", [], true);

            $this->cache->cacheUserData($userId, $response);

            return $response;

        } catch (Exception $e) {
            $this->logger->error('Get user by ID failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete user (admin endpoint).
     */
    #[Override]
    public function deleteUser(string $userId): array
    {
        $this->logger->info('User deletion attempt', ['user_id' => $userId]);

        try {
            $response = $this->client->request('DELETE', "/auth/v1/admin/users/{$userId}", [], true);

            $this->cache->invalidateUserCache($userId);
            $this->logger->info('User deletion successful', ['user_id' => $userId]);

            return $response;

        } catch (Exception $e) {
            $this->logger->error('User deletion failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch JWKS public keys for JWT validation.
     *
     * @return array<string, Key>
     */
    private function fetchPublicKeys(): array
    {
        $cacheKey = hash('sha256', '__supabase_jwks__');
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

    /**
     * Mask email for safe logging.
     */
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