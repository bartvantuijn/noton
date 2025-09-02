ARG COMPOSER_VERSION=2.7
ARG NODE_VERSION=24
ARG PHP_VERSION=8.3

FROM composer:${COMPOSER_VERSION} AS composer_build

WORKDIR /srv/www

# Install Composer dependencies
COPY composer.json composer.lock ./
RUN composer install \
        --ignore-platform-reqs \
        --no-ansi \
        --no-autoloader \
        --no-dev \
        --no-interaction \
        --no-plugins \
        --no-progress \
        --no-scripts \
        --prefer-dist

FROM node:${NODE_VERSION} AS node_build

WORKDIR /srv/www

COPY --from=composer_build /srv/www/vendor/filament vendor/filament

# Install Node dependencies
COPY package.json package-lock.json ./
RUN npm install && npm cache clean --force

# Compile assets
COPY vite.config.js ./
COPY app app/
COPY resources resources/
RUN npm run build

FROM php:${PHP_VERSION}-fpm AS php_build

ARG UID=1000
ARG GID=1000

# Configure user
RUN set -eux; \
    groupadd --gid ${GID} noton; \
    useradd --uid ${UID} --gid ${GID} --create-home noton; \
    mkdir -p /srv/www; chown noton:noton /srv/www

# Install extensions
RUN apt-get update; apt-get install --no-install-recommends -y \
    acl gosu ssh git nano netcat-traditional libpq-dev libicu-dev libzip-dev caddy supervisor

# Enable extensions
RUN docker-php-ext-install bcmath pdo_mysql pdo_pgsql intl exif zip

COPY --from=composer_build /usr/bin/composer /usr/bin/composer

USER noton

WORKDIR /srv/www

# Prepare application
COPY --chown=$UID:$GID .env.example ./
COPY --chown=$UID:$GID artisan ./
COPY --chown=$UID:$GID app app/
COPY --chown=$UID:$GID bootstrap bootstrap/
COPY --chown=$UID:$GID config config/
COPY --chown=$UID:$GID database database/
COPY --chown=$UID:$GID lang lang/
COPY --chown=$UID:$GID public public/
COPY --chown=$UID:$GID resources resources/
COPY --chown=$UID:$GID routes routes/
COPY --chown=$UID:$GID storage storage/

COPY --chown=$UID:$GID --from=composer_build /srv/www/ ./
COPY --chown=$UID:$GID --from=node_build /srv/www/public public/

# Copy configuration
COPY --chown=$UID:$GID docker docker/
RUN chmod +x /srv/www/docker/entrypoint.sh /srv/www/docker/process/*.sh

# Generate autoloader
RUN composer dump-autoload --classmap-authoritative --no-ansi --no-interaction

# Finish build
EXPOSE 6686
USER root
ENTRYPOINT ["/srv/www/docker/entrypoint.sh"]
