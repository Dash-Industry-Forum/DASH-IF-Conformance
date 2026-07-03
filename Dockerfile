FROM dunglas/frankenphp AS base

RUN apt-get update && apt install -y  openjdk-21-jre-headless supervisor nodejs npm


RUN install-php-extensions \
    pcntl zip

FROM base AS builder

RUN apt-get update && apt install -y  build-essential pkg-config g++ git cmake yasm zlib1g-dev

RUN cd / && git clone https://github.com/gpac/gpac.git && cd /gpac && ./configure && make -j


FROM base AS jccp

COPY --from=builder /gpac/bin/gcc/* /usr/bin/

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV SERVER_NAME=:80
COPY . /app

WORKDIR /app

RUN composer install --no-dev --optimize-autoloader --no-interaction && \
    npm install && \
    npm run build

RUN php artisan migrate --force

COPY laravel-queue-worker.conf /etc/supervisor/conf.d/laravel-queue-worker.conf

CMD ["/bin/bash", "/app/queue_wrapper.sh"]
