FROM php:8.2-cli

# تثبيت الحزم المطلوبة
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# نسخ الملفات
COPY . /var/www/html
WORKDIR /var/www/html

# تثبيت الحزم
RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# سكربت التشغيل
RUN echo '#!/bin/bash\n\
set -e\n\
PORT="${PORT:-8080}"\n\
echo ">>> Using port: $PORT"\n\
echo ">>> Clearing config cache"\n\
php artisan config:clear\n\
echo ">>> Running migrations"\n\
php artisan migrate --force || echo ">>> MIGRATION FAILED, continuing anyway"\n\
echo ">>> Starting PHP built-in server"\n\
exec php -S 0.0.0.0:$PORT -t public\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]