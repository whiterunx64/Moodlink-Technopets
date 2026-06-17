<?php

declare(strict_types=1);

return [
  /*
  |--------------------------------------------------------------------------
  | Supabase Configuration
  |--------------------------------------------------------------------------
  |
  | Configuration for Supabase authentication integration
  |
  */

  'url' => env('SUPABASE_URL'),
  'anon_key' => env('SUPABASE_ANON_KEY'),
  'service_key' => env('SUPABASE_SERVICE_KEY'),

  /*
  |--------------------------------------------------------------------------
  | Storage Configuration
  |--------------------------------------------------------------------------
  |
  | Buckets used for file uploads. The avatar bucket must exist in Supabase
  | Storage and be marked public so uploaded images resolve via public URL.
  |
  */

  'storage' => [
    'avatar_bucket' => env('SUPABASE_AVATAR_BUCKET', 'avatars'),
  ],

  /*
  |--------------------------------------------------------------------------
  | JWT Configuration
  |--------------------------------------------------------------------------
  |
  | Settings for JWT token validation and processing
  |
  */

  'jwt' => [
    'secret' => env('SUPABASE_JWT_SECRET'),
    'algorithm' => env('SUPABASE_JWT_ALGORITHM', 'ES256'),
    'leeway' => filter_var(env('SUPABASE_JWT_LEEWAY', 60), FILTER_VALIDATE_INT), // seconds
    'ttl' => filter_var(env('SUPABASE_JWT_TTL', 3600), FILTER_VALIDATE_INT), // seconds
  ],

  /*
  |--------------------------------------------------------------------------
  | Authentication Settings
  |--------------------------------------------------------------------------
  |
  | Configure authentication behavior and redirects
  |
  */

  'auth' => [
    'provider_redirect' => env('SUPABASE_AUTH_PROVIDER_REDIRECT', '/dashboard'),
    'logout_redirect' => env('SUPABASE_AUTH_LOGOUT_REDIRECT', '/'),
  ],

  /*
  |--------------------------------------------------------------------------
  | Security Configuration
  |--------------------------------------------------------------------------
  |
  | Security-related settings and hardening options
  |
  */

  'security' => [
    'password_policy' => [
      'min_length' => filter_var(env('SUPABASE_PASSWORD_MIN_LENGTH', 8), FILTER_VALIDATE_INT),
      'require_uppercase' => env('SUPABASE_PASSWORD_REQUIRE_UPPERCASE', false),
      'require_lowercase' => env('SUPABASE_PASSWORD_REQUIRE_LOWERCASE', false),
      'require_numbers' => env('SUPABASE_PASSWORD_REQUIRE_NUMBERS', false),
      'require_symbols' => env('SUPABASE_PASSWORD_REQUIRE_SYMBOLS', false),
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Rate Limiting Configuration
  |--------------------------------------------------------------------------
  |
  | Configure rate limiting for authentication endpoints
  |
  */

  'rate_limiting' => [
    'enabled' => env('SUPABASE_RATE_LIMITING_ENABLED', true),
    'login' => [
      'max_attempts' => filter_var(env('SUPABASE_LOGIN_MAX_ATTEMPTS', 5), FILTER_VALIDATE_INT),
      'decay_minutes' => filter_var(env('SUPABASE_LOGIN_DECAY_MINUTES', 15), FILTER_VALIDATE_INT),
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Circuit Breaker Configuration
  |--------------------------------------------------------------------------
  |
  | Configure circuit breaker for API resilience
  |
  */

  'circuit_breaker' => [
    'enabled' => env('SUPABASE_CIRCUIT_BREAKER_ENABLED', true),
    'failure_threshold' => filter_var(env('SUPABASE_CB_FAILURE_THRESHOLD', 5), FILTER_VALIDATE_INT),
    'recovery_timeout' => filter_var(env('SUPABASE_CB_RECOVERY_TIMEOUT', 60), FILTER_VALIDATE_INT), // seconds
  ],

  /*
  |--------------------------------------------------------------------------
  | Caching Configuration
  |--------------------------------------------------------------------------
  |
  | Configure caching for performance optimization
  |
  */

  'cache' => [
    'enabled' => env('SUPABASE_CACHE_ENABLED', true),
    'store' => env('SUPABASE_CACHE_STORE', 'redis'),
    'ttl' => [
      'user_data' => filter_var(env('SUPABASE_CACHE_USER_TTL', 300), FILTER_VALIDATE_INT), // 5 minutes
      'jwt_validation' => filter_var(env('SUPABASE_CACHE_JWT_TTL', 60), FILTER_VALIDATE_INT), // 1 minute
    ],
    'prefix' => env('SUPABASE_CACHE_PREFIX', 'supabase_auth'),
  ],

  /*
  |--------------------------------------------------------------------------
  | HTTP Client Configuration
  |--------------------------------------------------------------------------
  |
  | Configure HTTP client behavior and timeouts
  |
  */

  'client' => [
    'timeout' => filter_var(env('SUPABASE_HTTP_TIMEOUT', 10.0), FILTER_VALIDATE_FLOAT), // seconds
    'connect_timeout' => filter_var(env('SUPABASE_HTTP_CONNECT_TIMEOUT', 5.0), FILTER_VALIDATE_FLOAT), // seconds
    'retry_attempts' => filter_var(env('SUPABASE_HTTP_RETRY_ATTEMPTS', 3), FILTER_VALIDATE_INT),
    'retry_delay' => filter_var(env('SUPABASE_HTTP_RETRY_DELAY', 1000), FILTER_VALIDATE_INT), // milliseconds
    'verify_ssl' => env('SUPABASE_HTTP_VERIFY_SSL', true),
    'user_agent' => env('SUPABASE_HTTP_USER_AGENT', 'Supabase-Laravel-Auth/1.0'),
    'pool_size' => filter_var(env('SUPABASE_HTTP_POOL_SIZE', 10), FILTER_VALIDATE_INT),
  ],

  /*
  |--------------------------------------------------------------------------
  | Monitoring and Logging
  |--------------------------------------------------------------------------
  |
  | Configure monitoring, metrics, and logging
  |
  */

  'monitoring' => [
    'enabled' => env('SUPABASE_MONITORING_ENABLED', true),
    'logging' => [
      'channel' => env('SUPABASE_LOG_CHANNEL', 'stack'),
      'level' => env('SUPABASE_LOG_LEVEL', 'info'),
      'sensitive_fields' => ['password', 'token', 'secret', 'key'],
    ],
    'health_checks' => [
      'enabled' => env('SUPABASE_HEALTH_CHECKS_ENABLED', true),
      'endpoint' => env('SUPABASE_HEALTH_ENDPOINT', '/health/supabase'),
      'interval' => filter_var(env('SUPABASE_HEALTH_INTERVAL', 30), FILTER_VALIDATE_INT), // seconds
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Default Guards and Providers
  |--------------------------------------------------------------------------
  |
  | Default authentication configuration
  |
  */

  'defaults' => [
    'guard' => env('SUPABASE_DEFAULT_GUARD', 'supabase'),
    'provider' => env('SUPABASE_DEFAULT_PROVIDER', 'supabase'),
  ],

  'guards' => [
    'supabase' => [
      'driver' => 'supabase',
      'provider' => 'supabase',
    ],
  ],

  'providers' => [
    'supabase' => [
      'driver' => 'supabase',
      'model' => env('SUPABASE_USER_MODEL', App\Models\User::class),
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Environment Configuration
  |--------------------------------------------------------------------------
  |
  | Environment-specific settings
  |
  */

  'environment' => [
    'production' => [
      'force_https' => true,
      'debug' => false,
      'strict_mode' => true,
    ],
    'staging' => [
      'force_https' => true,
      'debug' => true,
      'strict_mode' => true,
    ],
    'development' => [
      'force_https' => false,
      'debug' => true,
      'strict_mode' => false,
    ],
  ],
];
