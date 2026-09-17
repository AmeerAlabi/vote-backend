#!/bin/sh
# Prepare the Laravel app, then hand off to the container's command
# (the web server by default; the worker/scheduler in the background service).
set -e

cd /app

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
