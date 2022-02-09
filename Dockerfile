FROM ubuntu:20.04
ENV TZ=Etc/GMT
ENV DEBIAN_FRONTEND=noninteractive

EXPOSE 80

RUN apt update
RUN apt -y install \
  apache2 apache2-doc php php-dev php-xml php-curl php-xdebug libapache2-mod-php \
  python2.7 \
  openjdk-8-jdk ant
RUN curl https://bootstrap.pypa.io/pip/2.7/get-pip.py --output get-pip.py
RUN python2.7 get-pip.py
RUN pip2 install matplotlib
RUN update-alternatives --set java /usr/lib/jvm/java-8-openjdk-amd64/jre/bin/java

COPY --chown=www-data:www-data . /var/www/html/

CMD apachectl -D FOREGROUND
