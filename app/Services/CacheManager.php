<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use Throwable;

use function base64_decode;
use function base64_encode;
use function config;
use function gzcompress;
use function gzuncompress;
use function is_array;
use function serialize;
use function unserialize;

final class CacheManager
{
  private readonly string $prefix;
  private readonly bool $compressionEnabled;

  public function __construct(
    private readonly CacheRepository $cache,
    private readonly LoggerInterface $logger,
    private readonly array $config = [],
  ) {
    $this->prefix = $config['prefix'];
    $this->compressionEnabled = (bool) $config['compression'];
  }

  /**
   * Cache user data.
   */
  public function cacheUserData(string $userId, array $userData, ?int $ttl = null): void
  {
    if (!$this->isEnabled()) {
      return;
    }

    try {
      $key = "{$this->prefix}:user:{$userId}";
      $this->cache->put($key, $this->pack($userData), $ttl ?? $this->ttl('user_data'));
      $this->log('User data cached', $key);
    } catch (Throwable $e) {
      $this->logger->warning('Cache write failed (cacheUserData)', ['error' => $e->getMessage()]);
    }
  }

  /**
   * Get cached user data.
   */
  public function getCachedUserData(string $userId): ?array
  {
    if (!$this->isEnabled()) {
      return null;
    }

    try {
      $key = "{$this->prefix}:user:{$userId}";
      $data = $this->cache->get($key);

      if ($data === null) {
        $this->log('User data cache miss', $key);
        return null;
      }

      $this->log('User data cache hit', $key);
      return $this->unpack($data);
    } catch (Throwable $e) {
      $this->logger->warning('Cache read failed (getCachedUserData)', ['error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Cache JWT validation result.
   */
  public function cacheJwtValidation(string $tokenHash, array $result, ?int $ttl = null): void
  {
    if (!$this->isEnabled()) {
      return;
    }

    try {
      $key = "{$this->prefix}:jwt:{$tokenHash}";
      $this->cache->put($key, $this->pack($result), $ttl ?? $this->ttl('jwt_validation'));
      $this->log('JWT validation cached', $key);
    } catch (Throwable $e) {
      $this->logger->warning('Cache write failed (cacheJwtValidation)', ['error' => $e->getMessage()]);
    }
  }

  /**
   * Get cached JWT validation result.
   */
  public function getCachedJwtValidation(string $tokenHash): ?array
  {
    if (!$this->isEnabled()) {
      return null;
    }

    try {
      $key = "{$this->prefix}:jwt:{$tokenHash}";
      $data = $this->cache->get($key);

      if ($data === null) {
        $this->log('JWT validation cache miss', $key);
        return null;
      }

      $this->log('JWT validation cache hit', $key);
      return $this->unpack($data);
    } catch (Throwable $e) {
      $this->logger->warning('Cache read failed (getCachedJwtValidation)', ['error' => $e->getMessage()]);
      return null;
    }
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
      $this->cache->forget("{$this->prefix}:user:{$userId}");
      $this->log('User cache invalidated', $userId);
    } catch (Throwable $e) {
      $this->logger->warning('Cache invalidation failed', ['error' => $e->getMessage()]);
    }
  }

  /**
   * Determine whether caching is enabled.
   */
  private function isEnabled(): bool
  {
    return (bool) $this->config['enabled'];
  }

  /**
   * Get TTL for the specified cache type.
   */
  private function ttl(string $type): int
  {
    return (int) $this->config['ttl'][$type];
  }

  /**
   * Pack data for cache storage.
   */
  private function pack(array $data): mixed
  {
    if (!$this->compressionEnabled) {
      return $data;
    }

    return ['compressed' => true, 'data' => base64_encode(gzcompress(serialize($data)))];
  }

  /**
   * Unpack cached data.
   */
  private function unpack(mixed $data): array
  {
    if (!is_array($data) || !($data['compressed'] ?? false)) {
      return (array) $data;
    }

    return unserialize(gzuncompress(base64_decode($data['data'])));
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