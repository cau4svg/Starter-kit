#!/bin/sh
set -e

# Espera DB e roda migrate + seed
until php artisan migrate --force; do
  echo "Waiting for database to be ready..."
  sleep 5
done

exec "$@"