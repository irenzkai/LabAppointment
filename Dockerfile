FROM php:8.2-apache

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && a2enmod rewrite

# 2. Set working directory
WORKDIR /var/www/html

# 3. Cache Composer dependencies
COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 4. Copy the project
COPY . .

# 5. Finish Composer and set permissions
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Prepare the Entrypoint Script
# We give the script execution permissions
RUN chmod +x /var/www/html/entrypoint.sh

# 7. Apache Config
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

# 8. Set the Entrypoint
ENTRYPOINT ["/var/www/html/entrypoint.sh"]