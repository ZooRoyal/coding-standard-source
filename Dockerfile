FROM php:8.4-cli-alpine

RUN set -eux ; \
  apk add --update --no-cache --virtual .composer-rundeps \
    bash \
    coreutils \
    git \
    nodejs \
    npm \
    openssh-client \
    sqlite-dev \
    tini \
    unzip \
    zip \
    $([ "$(apk --print-arch)" != "x86" ] && echo mercurial) \
    $([ "$(apk --print-arch)" != "armhf" ] && echo p7zip)

# Config Git
RUN git config --global --add safe.directory /app
RUN git config --global user.email "noone@rewe-digital.com" && \
    git config --global user.name "Coding-Standard container"

# Allow more memory for php processes
RUN echo 'memory_limit = 2G' >> /usr/local/etc/php/conf.d/memory-limit.ini
RUN echo 'error_reporting = E_ALL & ~E_DEPRECATED' >> /usr/local/etc/php/conf.d/error-reporting.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

# Install Coding-Standard-Source
RUN mkdir /coding-standard
COPY . /coding-standard/
WORKDIR /coding-standard/
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader
WORKDIR /app/

ENTRYPOINT [ "/coding-standard/src/bin/coding-standard" ]
