# FrankenPHP (Dunglas) - PHP 8.5 Alpine
# https://frankenphp.dev/docs/docker/
# https://hub.docker.com/r/dunglas/frankenphp
FROM dunglas/frankenphp:1-php8.5-alpine

# Install system dependencies (including Node.js and pnpm for asset builds)
RUN apk add --no-cache \
    git \
    unzip \
    bash \
    nodejs \
    npm

# Install pnpm for asset builds
RUN npm install -g pnpm@10.29.2

# Install PHP extensions (install-php-extensions is provided by the base image)
RUN install-php-extensions \
    zip \
    pcov

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure git safe directory
RUN git config --global --add safe.directory /app

# Set working directory (FrankenPHP uses /app by default)
WORKDIR /app

# Set environment
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="/app/vendor/bin:${PATH}"
ENV XDEBUG_MODE=coverage
