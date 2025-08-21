#!/bin/sh
set -e

# Gera arquivo .env caso não exista
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate
fi

# Espera DB e roda migrate + seed
until php artisan migrate --force; do
  echo "Waiting for database to be ready..."
  sleep 5
done

php artisan db:seed --force

exec "$@"
