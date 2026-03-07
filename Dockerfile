FROM php:8.2-apache

# 1. Install system dependencies & PHP extensions in one layer
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && a2enmod rewrite

# 2. Set working directory
WORKDIR /var/www/html

# 3. Optimized Composer Step (Speed up deployment)
# Copy only composer files first so this layer is cached
COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 4. Copy the rest of the project
COPY . .

# 5. Finalize Composer & Permissions
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Prepare Start Script
RUN chmod +x /var/www/html/start.sh

# 7. Configure Apache
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

# 8. Start the app using the script
CMD ["/var/www/html/start.sh"]