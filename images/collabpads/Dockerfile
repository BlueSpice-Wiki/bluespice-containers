FROM composer:latest AS builder
COPY bin /usr/src/CollabpadsBackend/bin
COPY src /usr/src/CollabpadsBackend/src
COPY composer.json /usr/src/CollabpadsBackend/
COPY config.docker.php /usr/src/CollabpadsBackend/config.php
RUN cd /usr/src/CollabpadsBackend/ && composer update --no-dev --ignore-platform-req ext-mongodb

FROM php:8.3-cli-alpine
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions mongodb-stable
RUN mkdir -p /usr/src/CollabpadsBackend
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=builder /usr/src/CollabpadsBackend/ /usr/src/CollabpadsBackend/
WORKDIR /usr/src/CollabpadsBackend
# ps is required for the init.sh script
RUN apk add procps
ENTRYPOINT [ "sh", "./bin/init.sh" ]
