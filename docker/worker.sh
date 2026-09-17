#!/bin/sh
# Runs the queue worker (email) and the scheduler (pruning) in one background service.
set -e
cd /app

php artisan schedule:work &
exec php artisan queue:work --tries=3 --max-time=3600
