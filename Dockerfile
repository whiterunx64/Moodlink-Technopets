FROM php:8.4-apache

# Install dependencies + Node.js
RUN apt-get update && apt-get install -y \
  git curl zip unzip libpng-dev libonig-dev libxml2-dev libpq-dev nodejs npm \
  && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer clear-cache && \
  composer config --global process-timeout 2000 && \
  for i in 1 2 3; do \
  composer install \
  --no-dev \
  --optimize-autoloader \
  --prefer-source \
  --no-interaction \
  --no-progress \
  && break || { \
  echo "Composer install failed. Retrying in 5 seconds..."; \
  sleep 5; \
  }; \
  done

# Install Node dependencies and build assets
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Apache config
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Apache Hardening

# Hide PHP version (removes X-Powered-By header)
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini

RUN a2enmod rewrite headers reqtimeout
RUN a2dismod -f autoindex status || true

# Hide Apache version
RUN echo "ServerTokens Prod\nServerSignature Off" \
  > /etc/apache2/conf-available/security.conf \
  && a2enconf security

# Disable directory listing
RUN printf '%s\n' \
  '<Directory /var/www/html/public>' \
  '    Options -Indexes' \
  '    AllowOverride All' \
  '    Require all granted' \
  '</Directory>' \
  > /etc/apache2/conf-available/no-indexes.conf \
  && a2enconf no-indexes

# Disable TRACE
RUN echo "TraceEnable Off" \
  > /etc/apache2/conf-available/trace.conf \
  && a2enconf trace

# Remove ETag header
RUN printf '%s\n' \
  'FileETag None' \
  'Header unset ETag' \
  > /etc/apache2/conf-available/etag.conf \
  && a2enconf etag

# Block access to hidden files (.env, .git, etc.)
RUN printf '%s\n' \
  '<FilesMatch "^\.">' \
  '    Require all denied' \
  '</FilesMatch>' \
  > /etc/apache2/conf-available/hide-dotfiles.conf \
  && a2enconf hide-dotfiles

# Disable Apache information pages
RUN printf '%s\n' \
  '<Location "/server-status">' \
  '    Require all denied' \
  '</Location>' \
  '<Location "/server-info">' \
  '    Require all denied' \
  '</Location>' \
  > /etc/apache2/conf-available/server-restrictions.conf \
  && a2enconf server-restrictions

# Limit upload/request size (10 MB)
RUN echo "LimitRequestBody 10485760" \
  > /etc/apache2/conf-available/request-limit.conf \
  && a2enconf request-limit

# Allow only required HTTP methods
RUN printf '%s\n' \
  '<Location "/">' \
  '    <LimitExcept GET POST PATCH PUT DELETE HEAD OPTIONS>' \
  '        Require all denied' \
  '    </LimitExcept>' \
  '</Location>' \
  > /etc/apache2/conf-available/http-methods.conf \
  && a2enconf http-methods

# Slowloris protection
RUN printf '%s\n' \
  'RequestReadTimeout header=20-40,minrate=500 body=20,minrate=500' \
  > /etc/apache2/conf-available/request-timeout.conf \
  && a2enconf request-timeout

# Limit oversized headers
RUN printf '%s\n' \
  'LimitRequestFields 50' \
  'LimitRequestFieldSize 8190' \
  > /etc/apache2/conf-available/request-headers.conf \
  && a2enconf request-headers

# Defense in depth: duplicate static security headers
RUN printf '%s\n' \
  'Header always unset X-Powered-By' \
  'Header always set X-Content-Type-Options "nosniff"' \
  'Header always set X-Frame-Options "SAMEORIGIN"' \
  'Header always set Referrer-Policy "strict-origin-when-cross-origin"' \
  'Header always set Permissions-Policy "camera=(), microphone=(), geolocation=(), payment=(), usb=()"' \
  'Header always set Cross-Origin-Opener-Policy "same-origin"' \
  'Header always set Cross-Origin-Resource-Policy "same-origin"' \
  'Header always set X-Permitted-Cross-Domain-Policies "none"' \
  > /etc/apache2/conf-available/security-headers.conf \
  && a2enconf security-headers


EXPOSE 80