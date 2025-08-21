#!/bin/sh
set -e

# Install dependencies if not already present
if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Espera DB e roda migrate + seed
until php artisan migrate --force; do
  echo "Waiting for database to be ready..."
  sleep 5
done

exec "$@"
