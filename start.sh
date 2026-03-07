#!/usr/bin/env bash

# Optimize Laravel for speed
php artisan optimize

# Run migrations and seeders
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force

# Start Apache in the foreground
exec apache2-foreground