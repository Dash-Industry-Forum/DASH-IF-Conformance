FROM dunglas/frankenphp AS base

RUN apt-get update && apt install -y  openjdk-21-jre-headless supervisor


RUN install-php-extensions \
    pcntl

FROM base AS builder

RUN apt-get update && apt install -y  build-essential pkg-config g++ git cmake yasm zlib1g-dev

RUN cd / && git clone https://github.com/gpac/gpac.git && cd /gpac && ./configure && make -j


FROM base AS jccp

COPY --from=builder /gpac/bin/gcc/* /usr/bin/


ENV SERVER_NAME=:80
COPY . /app
COPY laravel-queue-worker.conf /etc/supervisor/conf.d/laravel-queue-worker.conf

CMD /bin/bash /app/queue_wrapper.sh

