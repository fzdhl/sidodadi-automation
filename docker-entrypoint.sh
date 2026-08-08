#!/bin/sh
set -e

echo "==> Starting Sidodadi Document Generator..."

# Create .env from .env.example if not exists
if [ ! -f /app/.env ]; then
    echo "==> Creating .env from .env.example..."
    cp /app/.env.example /app/.env
fi

# Inject Railway environment variables into .env
# Railway provides these as real OS environment variables
echo "==> Configuring environment..."

# APP_KEY is required
if [ -n "$APP_KEY" ]; then
    sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" /app/.env
else
    echo "WARNING: APP_KEY is not set! Generating a temporary one..."
    php artisan key:generate --force
fi

# APP_URL - use Railway-provided URL or default
if [ -n "$RAILWAY_PUBLIC_DOMAIN" ]; then
    sed -i "s|^APP_URL=.*|APP_URL=https://$RAILWAY_PUBLIC_DOMAIN|" /app/.env
elif [ -n "$APP_URL" ]; then
    sed -i "s|^APP_URL=.*|APP_URL=$APP_URL|" /app/.env
fi

# APP_ENV
if [ -n "$APP_ENV" ]; then
    sed -i "s|^APP_ENV=.*|APP_ENV=$APP_ENV|" /app/.env
fi

# APP_DEBUG
if [ -n "$APP_DEBUG" ]; then
    sed -i "s|^APP_DEBUG=.*|APP_DEBUG=$APP_DEBUG|" /app/.env
fi

# Ensure storage directories exist and are writable
echo "==> Setting up storage directories..."
mkdir -p /app/storage/framework/sessions
mkdir -p /app/storage/framework/views
mkdir -p /app/storage/framework/cache
mkdir -p /app/storage/logs
mkdir -p /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Ensure database directory and file exist
echo "==> Ensuring database exists..."
mkdir -p /app/database
if [ ! -f /app/database/database.sqlite ]; then
    touch /app/database/database.sqlite
fi
chmod 664 /app/database/database.sqlite

# Clear any cached config (important!)
echo "==> Clearing old cache..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force

# Cache for performance (AFTER .env is fully configured)
echo "==> Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
