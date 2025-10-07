FROM dunglas/frankenphp AS base

RUN apt-get update && apt install -y  openjdk-21-jre-headless


RUN install-php-extensions \
    pcntl

FROM base AS builder

RUN apt-get update && apt install -y  build-essential pkg-config g++ git cmake yasm

RUN cd / && git clone https://github.com/gpac/gpac.git

RUN apt-get update && apt install -y zlib1g-dev
RUN cd /gpac && ./configure && make


FROM base AS jccp

COPY --from=builder /gpac/bin/gcc/* /usr/bin/


ENV SERVER_NAME=:80
COPY . /app


