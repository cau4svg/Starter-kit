#!/bin/sh
set -e

# Run database migrations automatically
until php artisan migrate --force; do
  echo "Waiting for database to be ready..."
  sleep 5
done

exec "$@"
