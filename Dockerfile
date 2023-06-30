FROM portalimage
RUN echo 'DocumentRoot /var/www/html/public\n<Directory /var/www/html>\nAllowOverride All\n</Directory>' > /etc/apache2/sites-available/000-default.conf
RUN echo '10.2.14.248 mntecidos.local' > /etc/hosts