ARG PHP_VERSION=7.4

ARG SMTP_PORT=1025
ARG SMTP_HOST=mailhog
ARG ROOT_PATH=/var/www/html

ARG XDEBUG_ENABLE=0
ARG XHPROF_ENABLE=0
ARG BLACKFIRE_ENABLE=0

ARG SSH_ENABLE=0
ARG SSH_LOGIN=dev
ARG SSH_PASSWORD=dev

FROM php:${PHP_VERSION}-fpm

ARG SMTP_HOST
ARG SMTP_PORT
ARG ROOT_PATH

ARG XDEBUG_ENABLE
ARG XHPROF_ENABLE
ARG BLACKFIRE_ENABLE

ARG SSH_ENABLE
ARG SSH_LOGIN
ARG SSH_PASSWORD

ENV ENTRYPOINT_PATH=/usr/local/bin
ENV MODX_TOOLS_PATH=/usr/local/modx/tools
ENV XHPROF_PATH=/usr/local/php/xhprof
ENV MODX_STORAGE_PATH=/usr/local/modx/storage
ENV MODX_BACKUP_PATH=/usr/local/modx/storage/backup
ENV MODX_CACHE_SOURCE_PATH=/usr/local/modx/storage/cache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libmagickwand-dev \
    libmagic-dev \
    imagemagick \
    pkg-config \
    build-essential \
    msmtp \
    msmtp-mta \
    mariadb-client \
    git \
    make \
    zip \
    unzip \
    wget \
    curl \
    locales \
    procps \
    openssl \
    openssh-server \
    telnet \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pcntl gd mysqli pdo pdo_mysql opcache fileinfo \
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis opcache \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN { \
        echo "# msmtp configuration"; \
        echo "defaults"; \
        echo "auth   off"; \
        echo "tls    off"; \
        echo "host ${SMTP_HOST}"; \
        echo "port ${SMTP_PORT}"; \
        echo "logfile /proc/self/fd/2"; \
        echo "account default"; \
        echo "add_missing_date_header on"; \
        echo "from mail@example.local"; \
    } > /etc/msmtprc \
    && chmod 644 /etc/msmtprc

RUN echo "[mail function]\n" \
    "SMTP=${SMTP_HOST}\n" \
    "smtp_port=${SMTP_PORT}\n" \
    "sendmail_path=/usr/bin/msmtp -t -i\n" \
    > /usr/local/etc/php/conf.d/mailhog.ini

RUN if [ "${XDEBUG_ENABLE}" -eq 1 ]; then \
        if [ "${PHP_VERSION}" \< "8.0" ]; then \
            pecl install xdebug-2.9.8; \
        else \
            pecl install xdebug; \
        fi && \
        docker-php-ext-enable xdebug; \
    fi

COPY xhprof/ $XHPROF_PATH/
RUN if [ "${XHPROF_ENABLE}" -eq 1 ]; then \
        pecl install mongodb xhprof \
        && docker-php-ext-enable mongodb xhprof \
        && cd $XHPROF_PATH \
        && composer update \
        && chown -R www-data:www-data $XHPROF_PATH \
        && chmod -R 775 $XHPROF_PATH; \
    fi

RUN if [ "$BLACKFIRE_ENABLE" -eq 1 ]; then \
        version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION.(PHP_ZTS ? '-zts' : '');") \
        && architecture=$(uname -m) \
        && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/$architecture/$version \
        && mkdir -p /tmp/blackfire \
        && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
        && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get ('extension_dir');")/blackfire.so \
        && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8307\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
        && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz; \
    fi

RUN if [ "$SSH_ENABLE" -eq 1 ]; then \
       mkdir /var/run/sshd \
        && sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config \
        && useradd -rm -d ${ROOT_PATH} -s /bin/bash -g root -G www-data -u 1000 ${SSH_LOGIN} \
        && echo "${SSH_LOGIN}:${SSH_PASSWORD}" | chpasswd; \
    fi

COPY sh/modx-*.sh $ENTRYPOINT_PATH/
RUN chmod 777 $ENTRYPOINT_PATH/modx-*.sh

ENTRYPOINT ["/usr/local/bin/modx-docker-start.sh"]

WORKDIR $ROOT_PATH