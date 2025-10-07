FROM dunglas/frankenphp

RUN apt-get update && apt install -y  openjdk-21-jre-headless

RUN install-php-extensions \
    pcntl

ENV SERVER_NAME=:80
COPY . /app


