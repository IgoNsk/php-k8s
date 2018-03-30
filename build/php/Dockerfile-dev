ARG BASE_IMAGE_PATH=${BASE_IMAGE_PATH}
ARG BASE_IMAGE_VERSION=${BASE_IMAGE_VERSION}
FROM ${BASE_IMAGE_PATH}:${BASE_IMAGE_VERSION}

USER root

# Install php-extentions and apk packages
RUN set -xe \
    && git clone https://github.com/longxinH/xhprof.git /tmp/xhprof \
    && docker-php-ext-configure /tmp/xhprof/extension \
    && docker-php-ext-install /tmp/xhprof/extension \
    && rm -r /tmp/xhprof \
    && pecl install -o -f \
        xdebug-2.5.5 \
    && docker-php-ext-enable \
        xdebug \
        xhprof \
    && rm -rf /tmp/pear

COPY build/php/etc/modules/xdebug.ini /usr/local/etc/php/conf.d/

VOLUME ["/tmp"]

# Add php configs
COPY build/php/etc/main/php.ini /usr/local/etc/php/
COPY build/php/etc/main/www.conf /usr/local/etc/php-fpm.d/
COPY build/php/etc/common_configs/logs/logs.conf /usr/local/etc/php-fpm.d/

COPY ./src/ /var/www/html/
