#!/bin/sh
set -e

# Gera arquivo .env caso não exista
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate
fi

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi


# Espera DB e roda migrate + seed
until php artisan migrate --force; do
  echo "Waiting for database to be ready..."
  sleep 5
done

php artisan db:seed --force

exec "$@"
