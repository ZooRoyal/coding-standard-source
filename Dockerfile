FROM php:8-alpine

RUN set -eux ; \
  apk add --no-cache --virtual .composer-rundeps \
    bash \
    coreutils \
    git \
    make \
    nodejs \
    npm \
    openssh-client \
    patch \
    subversion \
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
RUN echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/memory-limit.ini
RUN composer -d /coding-standard install --no-dev

RUN git config --global --add safe.directory /app
WORKDIR /app/

ENTRYPOINT [ "/coding-standard/src/bin/coding-standard" ]
