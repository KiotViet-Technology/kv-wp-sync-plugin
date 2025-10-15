FROM wordpress:latest

ARG XDEBUG_INI='/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini'

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo 'xdebug.mode=debug' >> ${XDEBUG_INI} \
    && echo 'xdebug.client_host=host.docker.internal' >> ${XDEBUG_INI} \
    && echo "xdebug.client_port=9003" >> ${XDEBUG_INI}
