#!/bin/sh

exec caddy run --config /srv/www/docker/config/Caddyfile --adapter caddyfile
