#!/bin/sh

set -e

echo "=== Production Container Starting ==="

# storageディレクトリが存在する場合、権限を設定
if [ -d "/var/www/html/storage" ]; then
    echo "Setting storage directory permissions..."
    # ディレクトリが存在しない場合は作成
    mkdir -p /var/www/html/storage/logs
    mkdir -p /var/www/html/storage/framework/cache
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/views
    mkdir -p /var/www/html/bootstrap/cache
    
    # 権限を設定（www-dataユーザーで実行できるように）
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
    chmod g+s /var/www/html/storage/logs
    
    echo "Storage permissions set successfully"
fi

# www-dataユーザーに切り替えてPHP-FPMを起動
exec su-exec www-data php-fpm "$@"

