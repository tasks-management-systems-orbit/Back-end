#!/bin/sh
set -e

# 1. Run migrations
# The --force flag is required for production
echo "Running migrations..."
php artisan migrate --force

# 2. Optimize Laravel for production
echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Start the web server
exec "$@"
