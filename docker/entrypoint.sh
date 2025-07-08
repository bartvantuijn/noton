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

php artisan config:cache
php artisan migrate --force

exec supervisord -c /srv/www/docker/config/supervisord.conf
