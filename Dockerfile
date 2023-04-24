FROM php:8-cli-alpine

RUN set -eux ; \
  apk add --no-cache --virtual .composer-rundeps \
    bash \
    coreutils \
    git \
    nodejs \
    npm \
    openssh-client \
    tini \
    unzip \
    zip \
    $([ "$(apk --print-arch)" != "x86" ] && echo mercurial) \
    $([ "$(apk --print-arch)" != "armhf" ] && echo p7zip)

# Install Composer and set up application
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN mkdir /coding-standard
COPY . /coding-standard/

ENV COMPOSER_ALLOW_SUPERUSER 1
RUN echo 'memory_limit = 2G' >> /usr/local/etc/php/conf.d/memory-limit.ini
RUN composer -d /coding-standard install --no-dev

RUN git config --global --add safe.directory /app
RUN git config --global user.email "noone@rewe-digital.com" && \
    git config --global user.name "Coding-Standard container"

WORKDIR /app/

ENTRYPOINT [ "/coding-standard/src/bin/coding-standard" ]
