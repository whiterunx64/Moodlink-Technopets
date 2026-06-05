<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function config;
use function is_int;

final class CacheManager
{
  private readonly string $prefix;

  public function __construct(
    private readonly CacheRepository $cache,
    private readonly LoggerInterface $logger,
    private readonly array $config = [],
  ) {
    $this->prefix = $config['prefix'];
  }

  /**
   * Cache user data.
   */
  public function cacheUserData(string $userId, array $userData, ?int $ttl = null): void
  {
    $this->remember($this->userKey($userId), $userData, $ttl ?? $this->ttl('user_data'));
  }

  /**
   * Get cached user data.
   */
  public function getCachedUserData(string $userId): ?array
  {
    return $this->retrieve($this->userKey($userId));
  }

  /**
   * Cache JWT validation result.
   */
  public function cacheJwtValidation(string $tokenHash, array $result, ?int $ttl = null): void
  {
    $this->remember($this->jwtKey($tokenHash), $result, $ttl ?? $this->ttl('jwt_validation'));
  }

  /**
   * Get cached JWT validation result.
   */
  public function getCachedJwtValidation(string $tokenHash): ?array
  {
    return $this->retrieve($this->jwtKey($tokenHash));
  }

  /**
   * Invalidate cached user data.
   */
  public function invalidateUserCache(string $userId): void
  {
    if (!$this->isEnabled()) {
      return;
    }

    try {
      $this->cache->forget($this->userKey($userId));
      $this->log('User cache invalidated', $userId);
    } catch (Throwable $e) {
      $this->logger->warning('Cache invalidation failed', ['error' => $e->getMessage()]);
    }
  }

  /**
   * Cache the JWKS public key set.
   */
  public function cacheJwks(string $cacheKey, array $keys, int $ttl): void
  {
    $this->remember($this->jwksKey($cacheKey), $keys, $ttl);
  }

  /**
   * Get the cached JWKS public key set.
   */
  public function getCachedJwks(string $cacheKey): ?array
  {
    return $this->retrieve($this->jwksKey($cacheKey));
  }

  /**
   * Write an array to the cache under the given key.
   */
  private function remember(string $key, array $data, int $ttl): void
  {
    if (!$this->isEnabled()) {
      return;
    }

    try {
      $this->cache->put($key, $data, $ttl);
      $this->log('Cached', $key);
    } catch (Throwable $e) {
      $this->logger->warning('Cache write failed', ['key' => $key, 'error' => $e->getMessage()]);
    }
  }

  /**
   * Read an array from the cache, or null on miss.
   */
  private function retrieve(string $key): ?array
  {
    if (!$this->isEnabled()) {
      return null;
    }

    try {
      $data = $this->cache->get($key);
      $this->log($data === null ? 'Cache miss' : 'Cache hit', $key);

      return $data === null ? null : (array) $data;
    } catch (Throwable $e) {
      $this->logger->warning('Cache read failed', ['key' => $key, 'error' => $e->getMessage()]);

      return null;
    }
  }

  /**
   * Determine whether caching is enabled.
   */
  private function isEnabled(): bool
  {
    return $this->config['enabled'] === true;
  }

  /**
   * Get TTL for the specified cache type.
   */
  private function ttl(string $type): int
  {
    $value = $this->config['ttl'][$type] ?? null;

    if (!is_int($value) || $value <= 0) {
      throw new RuntimeException("Invalid cache TTL for type '{$type}'.");
    }

    return $value;
  }

  private function userKey(string $userId): string
  {
    return "{$this->prefix}:user:{$userId}";
  }

  private function jwtKey(string $hash): string
  {
    return "{$this->prefix}:jwt:{$hash}";
  }

  private function jwksKey(string $hash): string
  {
    return "{$this->prefix}:jwks:{$hash}";
  }

  /**
   * Write a debug log entry.
   */
  private function log(string $message, string $key): void
  {
    if (config('supabase-auth.monitoring.logging.level') === 'debug') {
      $this->logger->debug("[CacheManager] {$message}", ['key' => $key]);
    }
  }
}
