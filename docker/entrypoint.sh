#!/bin/sh

# Waiting for database to be ready and reachable
until nc -z "$DB_HOST" "$DB_PORT"; do
  echo "Waiting for $DB_HOST..."
  sleep 2
done

# Generate .env if not mounted
if [ ! -f .env ]; then
  echo "Generating environment file..."
  cp .env.example .env
  php artisan key:generate --force
fi

# Create storage symlink
php artisan storage:link

# Set up storage permissions
setfacl -R -m u:noton:rwX storage/app/public bootstrap/cache
setfacl -dR -m u:noton:rwX storage/app/public bootstrap/cache

# Run migrations
php artisan migrate --force

# Clear cache
php artisan optimize:clear
php artisan filament:optimize-clear

# Cache application
if [ "$APP_ENV" != "local" ]; then
  php artisan optimize
  php artisan filament:optimize
fi

# Start supervisord
exec gosu noton supervisord -c /srv/www/docker/config/supervisord.conf
