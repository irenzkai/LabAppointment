#!/bin/sh

# Exit if any command fails
set -e

echo "Optimizing Laravel..."
php artisan optimize

echo "Linking Storage..."
# Creates the shortcut for X-rays and Scans
php artisan storage:link --force

echo "Running Migrations..."
# NOTE: migrate:fresh DELETES DATA. 
# Use 'migrate --force' for production so you don't lose patient records.
php artisan migrate:fresh --force

echo "Running Seeders..."
php artisan db:seed --class=AdminSeeder --force
php artisan db:seed --class=ServiceSeeder --force

echo "Starting Apache..."
exec apache2-foreground