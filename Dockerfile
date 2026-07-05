# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --prefer-dist --optimize-autoloader \
    --no-interaction --no-progress --no-scripts \
    --classmap-authoritative
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM node:22-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

FROM php:8.4-apache AS production

RUN apt-get update \
    && apt-get upgrade -y --no-install-recommends \
    && apt-get install -y --no-install-recommends \
       libpng-dev libonig-dev libxml2-dev libpq-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
       pdo_pgsql pgsql mbstring exif pcntl bcmath gd opcache zip \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
  } > /usr/local/etc/php/conf.d/opcache.ini

RUN { \
    echo 'expose_php = Off'; \
    echo 'display_errors = Off'; \
    echo 'display_startup_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    echo 'allow_url_include = Off'; \
    echo 'session.cookie_httponly = On'; \
    echo 'session.cookie_secure = On'; \
    echo 'session.cookie_samesite = Lax'; \
    echo 'session.use_strict_mode = On'; \
  } > /usr/local/etc/php/conf.d/security.ini

RUN { \
    echo 'upload_max_filesize = 8M'; \
    echo 'post_max_size = 10M'; \
    echo 'max_execution_time = 60'; \
    echo 'memory_limit = 256M'; \
  } > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN chown -R root:root /var/www/html \
    && chown -R www-data:www-data storage bootstrap/cache \
    && find storage bootstrap/cache -type d -exec chmod 775 {} \; \
    && find storage bootstrap/cache -type f -exec chmod 664 {} \;

RUN php artisan view:cache && php artisan event:cache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

RUN a2enmod rewrite headers reqtimeout expires brotli \
    && a2dismod -f autoindex status info userdir || true

RUN printf '%s\n' \
    'ServerTokens Prod' \
    'ServerSignature Off' \
    > /etc/apache2/conf-available/hardening-tokens.conf \
    && a2enconf hardening-tokens

RUN printf '%s\n' \
    '<Directory /var/www/html/public/build>' \
    '    Header set Cache-Control "public, max-age=31536000, immutable"' \
    '</Directory>' \
    '<IfModule mod_expires.c>' \
    '    ExpiresActive On' \
    '    ExpiresByType image/x-icon "access plus 7 days"' \
    '    ExpiresByType image/svg+xml "access plus 7 days"' \
    '    ExpiresByType image/png "access plus 7 days"' \
    '    ExpiresByType image/webp "access plus 7 days"' \
    '</IfModule>' \
    > /etc/apache2/conf-available/asset-caching.conf \
    && a2enconf asset-caching

RUN printf '%s\n' \
    '<Directory /var/www/html/public>' \
    '    Options -Indexes -Includes -ExecCGI' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    '<DirectoryMatch "/var/www/html/(app|bootstrap|config|database|routes|storage|vendor|tests)">' \
    '    Require all denied' \
    '</DirectoryMatch>' \
    > /etc/apache2/conf-available/dir-policy.conf \
    && a2enconf dir-policy

RUN printf '%s\n' \
    'TraceEnable Off' \
    'FileETag None' \
    'Header unset ETag' \
    '<FilesMatch "^\.">' \
    '    Require all denied' \
    '</FilesMatch>' \
    '<Directory /var/www/html/public/.well-known>' \
    '    Require all granted' \
    '</Directory>' \
    '<Location "/server-status">' \
    '    Require all denied' \
    '</Location>' \
    '<Location "/server-info">' \
    '    Require all denied' \
    '</Location>' \
    > /etc/apache2/conf-available/lockdown.conf \
    && a2enconf lockdown

RUN printf '%s\n' \
    '<Directory /var/www/html/public>' \
    '    <FilesMatch "\.php$">' \
    '        Require all denied' \
    '    </FilesMatch>' \
    '    <FilesMatch "^index\.php$">' \
    '        Require all granted' \
    '    </FilesMatch>' \
    '</Directory>' \
    > /etc/apache2/conf-available/php-lockdown.conf \
    && a2enconf php-lockdown

RUN printf '%s\n' \
    'LimitRequestBody 10485760' \
    'LimitRequestFields 50' \
    'LimitRequestFieldSize 8190' \
    'LimitRequestLine 8190' \
    'RequestReadTimeout header=20-40,minrate=500 body=20,minrate=500' \
    'Timeout 60' \
    'KeepAliveTimeout 5' \
    '<Location "/">' \
    '    <LimitExcept GET POST PATCH PUT DELETE HEAD OPTIONS>' \
    '        Require all denied' \
    '    </LimitExcept>' \
    '</Location>' \
    > /etc/apache2/conf-available/request-limits.conf \
    && a2enconf request-limits

RUN printf '%s\n' \
    'Header always unset X-Powered-By' \
    'Header always unset Server' \
    'Header always unset X-Content-Type-Options' \
    'Header always set X-Content-Type-Options "nosniff"' \
    'Header always unset X-Frame-Options' \
    'Header always set X-Frame-Options "SAMEORIGIN"' \
    'Header always unset Cross-Origin-Resource-Policy' \
    'Header always set Cross-Origin-Resource-Policy "same-origin"' \
    'Header always unset X-Permitted-Cross-Domain-Policies' \
    'Header always set X-Permitted-Cross-Domain-Policies "none"' \
    'Header always set X-Download-Options "noopen"' \
    'Header always set X-DNS-Prefetch-Control "off"' \
    > /etc/apache2/conf-available/security-headers.conf \
    && a2enconf security-headers

RUN printf '%s\n' \
    '<IfModule mod_brotli.c>' \
    '    AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/css text/javascript' \
    '    AddOutputFilterByType BROTLI_COMPRESS application/javascript application/json application/xml' \
    '    AddOutputFilterByType BROTLI_COMPRESS image/svg+xml' \
    '    BrotliCompressionQuality 5' \
    '</IfModule>' \
    'Header append Vary Accept-Encoding' \
    > /etc/apache2/conf-available/brotli.conf \
    && a2enconf brotli

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]