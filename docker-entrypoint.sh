#!/bin/sh
set -e

php artisan migrate --force
php artisan config:cache

php artisan queue:work --tries=3 --sleep=3 &

while true; do
    php artisan schedule:run
    sleep 60
done &

exec php artisan serve --host 0.0.0.0 --port "${PORT:-10000}"
