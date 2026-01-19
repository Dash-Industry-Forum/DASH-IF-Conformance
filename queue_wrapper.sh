#!/bin/bash

set -m

frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile &

frankenphp php-cli /app/artisan queue:work


fg %1
