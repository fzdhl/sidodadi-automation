#!/bin/sh
set -e

# Copy .env.example to .env if .env doesn't exist
if [ ! -f /app/.env ]; then
    cp /app/.env.example /app/.env
fi

# Set APP_KEY from environment variable if provided
if [ -n "$APP_KEY" ]; then
    sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" /app/.env
fi

# Set APP_URL from environment variable if provided
if [ -n "$APP_URL" ]; then
    sed -i "s|^APP_URL=.*|APP_URL=$APP_URL|" /app/.env
fi

# Set APP_ENV from environment variable if provided
if [ -n "$APP_ENV" ]; then
    sed -i "s|^APP_ENV=.*|APP_ENV=$APP_ENV|" /app/.env
fi

# Run migrations
php artisan migrate --force

# Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start server
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
