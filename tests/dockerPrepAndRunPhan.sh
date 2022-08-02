pecl install ast > /dev/null && \
echo "extension=ast.so" > /usr/local/etc/php/conf.d/ast.ini && \
/var/www/html/extensions/NSFWTag/vendor/bin/phan -d /var/www/html/extensions/NSFWTag --no-progress-bar