ARG PHPVERSION=8.2

FROM composer:2.5 as composer

FROM php:${PHPVERSION}-zts-bullseye as php

RUN apt-get update && apt-get install -y --no-install-recommends \
      autoconf g++ make libgearman-dev libzip-dev unzip \
      && rm -rf /var/lib/apt/lists/*

RUN pecl install parallel && docker-php-ext-enable parallel

# hadolint ignore=DL3003
RUN cd /tmp \
    && curl -L "https://github.com/php/pecl-networking-gearman/archive/7033013.tar.gz" --output /tmp/gearman.tar.gz \
    && tar -xf gearman.tar.gz \
    && cd pecl-networking-gearman-* \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && cp ./modules/gearman.so /extension \
    && rm -rf /tmp/gearman* \
    && docker-php-ext-enable gearman

RUN docker-php-ext-enable sodium

RUN docker-php-ext-install zip && docker-php-ext-enable zip

COPY --from=composer /usr/bin/composer /usr/bin/

WORKDIR /app