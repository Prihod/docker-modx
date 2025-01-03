FROM php:8.1-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libmongoc-1.0-0 \
    libssl-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/timezone.ini

RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" > /usr/local/etc/php/conf.d/error-reporting.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

RUN git clone https://github.com/perftools/xhgui.git . \
    && chmod -R 0777 cache \
    && composer install --no-dev


RUN a2enmod rewrite
COPY apache.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

COPY config.php /var/www/html/config/config.default.php

COPY templates/runs/view.twig /var/www/html/templates/custom/runs/view.twig