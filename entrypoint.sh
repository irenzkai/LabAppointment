#!/bin/sh

# Exit if any command fails
set -e

echo "Optimizing Laravel..."
php artisan optimize

echo "Running Migrations..."
php artisan migrate --force

echo "Running Seeders..."
php artisan db:seed --class=AdminSeeder --force

echo "Starting Apache..."
exec apache2-foreground