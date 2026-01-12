#!/bin/sh

set -e

echo "=== Production Container Starting ==="

# 必要なディレクトリを作成
echo "Creating required directories..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# 既存のキャッシュを削除（実行時に再生成）
rm -rf /var/www/html/bootstrap/cache/*.php

# 権限を設定（www-dataユーザーで実行できるように）
echo "Setting directory permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/run/php-fpm
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chmod g+s /var/www/html/storage/logs

echo "Permissions set successfully"

# PHP-FPMをrootで起動（ワーカープロセスは設定ファイルでwww-dataに設定）
exec php-fpm "$@"

