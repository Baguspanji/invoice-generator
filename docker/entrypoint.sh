#!/bin/sh
# Entrypoint single-container: seed volume storage, tunggu MySQL,
# migrate, cache, lalu jalankan supervisord (nginx + php-fpm + queue).
set -e

# 1. Seed volume storage bila masih kosong (mount kosong menutupi bawaan image)
if [ ! -d /var/www/html/storage/app/public/task-attachments ]; then
    echo "[entrypoint] Seeding storage files..."
    cp -a /opt/storage-skeleton/. /var/www/html/storage/
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 2. Tunggu MySQL siap (maks ~60 detik)
echo "[entrypoint] Waiting for database (${DB_HOST:-mysql}:${DB_PORT:-3306})..."
tries=0
until php artisan db:show > /dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "[entrypoint] Database tidak terjangkau, lanjut tanpa migrate."
        break
    fi
    sleep 2
done

# 3. Migrate + cache production
php artisan migrate --force || true
php artisan storage:link || true
php artisan config:cache
php artisan view:cache

exec "$@"
