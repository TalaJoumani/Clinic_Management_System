FROM php:8.2-fpm

# تثبيت الحزم المطلوبة + nginx + supervisor
RUN apt-get update && apt-get install -y \
    nginx supervisor \
    libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# نسخ الملفات
COPY . /var/www/html
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# إعداد nginx (قالب بيتعوض فيه $PORT وقت التشغيل)
RUN echo 'server {\n\
    listen __PORT__;\n\
    server_name _;\n\
    root /var/www/html/public;\n\
    index index.php;\n\
\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
\n\
    location ~ \.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
        include fastcgi_params;\n\
    }\n\
}\n\
' > /etc/nginx/sites-available/default.template

# إعداد supervisor يشغل php-fpm و nginx مع بعض
RUN echo '[supervisord]\n\
nodaemon=true\n\
\n\
[program:php-fpm]\n\
command=php-fpm -F\n\
autostart=true\n\
autorestart=true\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n\
' > /etc/supervisor/conf.d/supervisord.conf

# سكربت التشغيل
RUN echo '#!/bin/bash\n\
set -e\n\
PORT="${PORT:-8080}"\n\
echo ">>> Using port: $PORT"\n\
sed "s/__PORT__/${PORT}/g" /etc/nginx/sites-available/default.template > /etc/nginx/sites-available/default\n\
echo ">>> Clearing config cache"\n\
php artisan config:clear\n\
echo ">>> Running migrations"\n\
php artisan migrate --force || echo ">>> MIGRATION FAILED, continuing anyway"\n\
echo ">>> Starting supervisor (nginx + php-fpm)"\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]