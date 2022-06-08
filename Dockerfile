FROM php:8.1-fpm-buster

RUN apt update && \
    apt install -y ffmpeg nginx \
    	libmagickwand-dev python3-distutils zip libzip-dev \
    	libpng-dev libwebp-dev \
    	${PHPIZE_DEPS} && \
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install zip gd && \
    pecl install -o -f imagick && \
    docker-php-ext-enable imagick && \
    docker-php-ext-enable gd

COPY --chown=www-data:www-data . /var/www/html
COPY ./nginx.conf /etc/nginx/sites-available/default
COPY ./php.ini /usr/local/etc/php/conf.d/uniq.ini

RUN rm -rf /var/www/html/nginx.conf && \
    mkdir -p /var/www/html/files && \
    chown -R www-data:www-data /var/www/html

CMD nginx; php-fpm;
