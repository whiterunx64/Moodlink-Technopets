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
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Apache config
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN a2enmod rewrite
RUN echo "<Directory /var/www/html/public>\nOptions -Indexes\n</Directory>" > /etc/apache2/conf-available/no-indexes.conf \
  && a2enconf no-indexes

RUN echo "TraceEnable Off" > /etc/apache2/conf-available/trace.conf \
  && a2enconf trace

RUN echo "ServerTokens Prod\nServerSignature Off" > /etc/apache2/conf-available/security.conf \
  && a2enconf security

EXPOSE 80