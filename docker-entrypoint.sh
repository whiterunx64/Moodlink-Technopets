#!/usr/bin/env bash
set -e

php artisan config:cache
php artisan route:cache

exec "$@"
