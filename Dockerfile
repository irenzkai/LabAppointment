FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install OPcache for faster PHP execution
RUN docker-php-ext-install opcache

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Point Apache to Laravel's public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Expose port 80
EXPOSE 80

# Final Start Command: Optimize -> Migrate -> Seed -> Start Apache
# We use 'sh -c' to ensure the shell handles the '&&' operators correctly
CMD sh -c "php artisan optimize && php artisan migrate --force && php artisan db:seed --class=AdminSeeder --force && apache2-foreground"