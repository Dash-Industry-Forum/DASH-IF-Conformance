FROM ubuntu:16.04

EXPOSE 80

RUN apt update
RUN apt -y install apache2 apache2-doc php php-dev php-xml php-curl php-xdebug libapache2-mod-php
RUN apt -y install python2.7 python-pip python-matplotlib
RUN apt -y install openjdk-8-jdk ant

CMD apachectl -D FOREGROUND
