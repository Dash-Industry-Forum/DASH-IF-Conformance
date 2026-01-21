#!/bin/bash

set -m

frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile &

/usr/bin/supervisord

fg %1
