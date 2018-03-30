ARG BASE_IMAGE_VERSION=${BASE_IMAGE_VERSION}
ARG BASE_IMAGE_PATH=${APP_BASE_IMAGE_PATH}
FROM ${BASE_IMAGE_PATH}:${BASE_IMAGE_VERSION}

# Add php configs
COPY build/php/etc/main/php.ini /usr/local/etc/php/
COPY build/php/etc/main/www.conf /usr/local/etc/php-fpm.d/
COPY build/php/etc/common_configs/logs/logs.conf /usr/local/etc/php-fpm.d/

COPY ./src/ /var/www/html/
