#!/bin/sh
set -eu

: "${PORT:=10000}"
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf
php artisan config:cache
php-fpm -D
exec nginx -g 'daemon off;'
