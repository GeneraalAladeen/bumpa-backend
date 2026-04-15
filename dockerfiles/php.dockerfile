FROM php:8.4-fpm-alpine

ARG UID
ARG GID
ENV UID=${UID}
ENV GID=${GID}
ENV PROJECT_ROOT=/var/www/html/project

# Install system dependencies
RUN apk update && apk upgrade && \
    apk add --no-cache \
    icu-dev \
    openssl \
    bash \
    nodejs \
    npm \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    git \
    openssh-client \
    autoconf \
    gcc \
    g++ \
    make \
    automake \
    libtool \
    file \
    nasm \
    mysql-client \
    mariadb-connector-c \
    gettext \
    linux-headers

# Install core PHP extensions
RUN docker-php-ext-configure gd --with-jpeg && \
    docker-php-ext-install zip gd bcmath opcache exif pdo pdo_mysql intl ftp \
    pcntl

# Install PECL extensions
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    rm -rf /tmp/pear && \
    apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Configure user
RUN delgroup dialout && \
    addgroup -g ${GID} --system laravel && \
    adduser -G laravel --system -D -s /bin/sh -u ${UID} laravel && \
    echo "[client]" > /home/laravel/.my.cnf && \
    echo "skip-ssl = true" >> /home/laravel/.my.cnf

RUN echo "memory_limit = 128M" > /usr/local/etc/php/conf.d/memory-limit.ini

USER laravel
WORKDIR $PROJECT_ROOT

CMD ["php", "-v"]
