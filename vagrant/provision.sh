set -o errexit
set -o pipefail
set -o nounset
shopt -s failglob
set -o xtrace

export DEBIAN_FRONTEND=noninteractive

# ref https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/blob/master/Doc/Conformance%20Software%20Installation%20Guide.pdf

apt-get update
apt-get install -y php php-dev php-xml php-curl php-xdebug libapache2-mod-php \
                   default-jdk apache2 apache2-doc python2.7 python-pip \
                   python-matplotlib ant git libstdc++6:i386 gcc-multilib \
                   g++-multilib


java -version
javac -version


echo '' >  /etc/sudoers.d/dash
echo '# the conformance software requires sudo permission to run' >>  /etc/sudoers.d/dash
echo '%www-data ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers.d/dash
echo '' >>  /etc/sudoers.d/dash
chmod 0440 /etc/sudoers.d/dash
cat /etc/sudoers.d/dash


service apache2 restart

usermod -a -G www-data vagrant

cd /var/www

git clone --recurse-submodules https://github.com/Dash-Industry-Forum/DASH-IF-Conformance

MAKE_PARALLEL_JOBS=10
MAKEFLAGS=-j$MAKE_PARALLEL_JOBS

cd DASH-IF-Conformance/ISOSegmentValidator/public/linux/
make $MAKEFLAGS

chmod -R 0777 /var/www/


echo '' >  /etc/apache2/sites-available/000-dash.conf
echo 'Listen 8090' >>  /etc/apache2/sites-available/000-dash.conf
echo '<VirtualHost *:8090>' >>  /etc/apache2/sites-available/000-dash.conf
echo '        ServerAdmin webmaster@localhost' >>  /etc/apache2/sites-available/000-dash.conf
echo '        DocumentRoot /var/www' >>  /etc/apache2/sites-available/000-dash.conf
echo '        ErrorLog ${APACHE_LOG_DIR}/error.log' >>  /etc/apache2/sites-available/000-dash.conf
echo '        CustomLog ${APACHE_LOG_DIR}/access.log combined' >>  /etc/apache2/sites-available/000-dash.conf
echo '</VirtualHost>' >>  /etc/apache2/sites-available/000-dash.conf
echo '' >>  /etc/apache2/sites-available/000-dash.conf

a2ensite 000-dash
service apache2 reload

echo "Success! Use your browser to visit http://localhost:8090/DASH-IF-Conformance/Conformance-Frontend/index.html"
