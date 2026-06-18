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

final class SupabaseAuthApi implements SupabaseAuthInterface
{
    public function __construct(
        private readonly SupabaseClient $client,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

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

            if (isset($response['access_token'])) {
                // Supabase invalidates previous refresh tokens after login
                $this->revokeOtherSessions($response['access_token']);
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

    #[Override]
    public function signOut(string $accessToken): array
    {
        try {
            $response = $this->client->request('POST', '/auth/v1/logout', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
            ]);

            $this->logger->info('Supabase logout', [
                'action' => 'revoke_access_token',
                'status' => 'success',
            ]);

            return $response;

        } catch (Exception $e) {
            $this->logger->error('User logout failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function revokeOtherSessions(string $accessToken): void
    {
        try {
            $this->client->request('POST', '/auth/v1/logout', [
                'headers' => ['Authorization' => "Bearer {$accessToken}"],
                'query' => ['scope' => 'others'],
            ]);

            $this->logger->info('Supabase sessions revoked after login', [
                'action' => 'revoke_other_sessions',
            ]);
        } catch (Exception $e) {
            $this->logger->warning('Failed to revoke other Supabase sessions after login', [
                'action' => 'revoke_other_sessions',
                'error' => $e->getMessage(),
            ]);
        }
    }

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
            JWT::$leeway = $leeway; // allow clock skew between servers

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

            $response = $this->client->request('POST', '/auth/v1/admin/users', ['json' => $payload], true); // true = use service role key

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
     * @return array<string, Key>
     */
    private function fetchPublicKeys(): array
    {
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