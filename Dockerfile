FROM ubuntu:20.04
ENV TZ=Etc/GMT
ENV DEBIAN_FRONTEND=noninteractive

EXPOSE 80

RUN apt-get update -y
RUN apt -y install \
  apache2 apache2-doc php php-dev php-xml php-curl php-xdebug libapache2-mod-php \
  python2.7 \
  openjdk-8-jdk ant \
  sudo \
  g++
RUN curl https://bootstrap.pypa.io/pip/2.7/get-pip.py --output get-pip.py
RUN python2.7 get-pip.py
RUN pip2 install matplotlib
RUN update-alternatives --set java /usr/lib/jvm/java-8-openjdk-amd64/jre/bin/java

# let apache sudo this one specific script which filters the access logs
RUN echo >> /etc/sudoers "www-data ALL=NOPASSWD: /var/www/html/Conformance-Frontend/src/get-access.sh"

RUN rm /var/www/html/index.html
COPY --chown=www-data:www-data . /var/www/html/

COPY --chown=www-data:www-data ISOSegmentValidator /var/www/html/ISOSegmentValidator
RUN cd /var/www/html/ISOSegmentValidator/public/linux && make clean && make -j

COPY --chown=www-data:www-data Conformance-Frontend /var/www/html/Conformance-Frontend

CMD apachectl -D FOREGROUND
