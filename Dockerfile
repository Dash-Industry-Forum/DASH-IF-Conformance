FROM ubuntu:24.04
ENV TZ=Etc/GMT
ENV DEBIAN_FRONTEND=noninteractive
ARG SHORT_SHA=unknown
ENV SHORT_SHA=$SHORT_SHA

EXPOSE 80

RUN apt-get update -y
RUN apt -y install \
  g++ \
  make \
  python3 \
  python-matplotlib-data  \
  openjdk-8-jdk \ 
  apache2 \
  php \
  php-xml \
  php-curl \
  libapache2-mod-php \ 
  php-xdebug \
  sudo \ 
  gpac
RUN update-alternatives --set java /usr/lib/jvm/java-8-openjdk-amd64/jre/bin/java

# let apache sudo this one specific script which filters the access logs
RUN echo >> /etc/sudoers "www-data ALL=NOPASSWD: /var/www/html/Conformance-Frontend/src/get-access.sh"

RUN rm /var/www/html/index.html
COPY --chown=www-data:www-data . /var/www/html/

RUN cd /var/www/html/ISOSegmentValidator/public/linux && make clean && make -j


CMD apachectl -D FOREGROUND
