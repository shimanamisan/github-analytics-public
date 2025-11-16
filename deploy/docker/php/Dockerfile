# アセットビルド用ステージ
FROM node:20-alpine AS assets
WORKDIR /app
# 依存関係のみ先にコピー（キャッシュ最適化）
COPY src/package*.json ./src/
RUN cd ./src && npm ci
COPY src ./src
RUN cd ./src && npm run build

# PHP基本ステージ
FROM php:8.2.29-fpm-alpine AS base
ENV TZ=Asia/Tokyo
RUN apk add --no-cache \
    # ビルドツール（PHP拡張コンパイルに必要）
    autoconf g++ make linux-headers \
    # ランタイムライブラリ
    git icu-dev icu-libs oniguruma-dev libzip-dev unzip tzdata \
  && docker-php-ext-install intl pdo_mysql mbstring zip bcmath \
  && pecl install redis \
  && docker-php-ext-enable redis

# 本番用ステージ
FROM base AS prod
ENV APP_ENV=production APP_DEBUG=0
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# アプリケーションコードをコピー
COPY src /var/www/html
# ビルド済みアセットをコピー
COPY --from=assets /app/src/public/build /var/www/html/public/build

# Composer本番インストール
RUN composer install --no-dev --optimize-autoloader --no-interaction

# PHP-FPM設定ファイルをコピー（Unixソケット用）
COPY deploy/docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-www.conf

# PHP-FPMソケット用ディレクトリ作成
RUN mkdir -p /var/run/php-fpm \
  && chown -R www-data:www-data /var/run/php-fpm

# 既存のキャッシュを削除（実行時に再生成）
RUN rm -rf bootstrap/cache/*.php

# 権限設定
RUN chown -R www-data:www-data /var/www/html
USER www-data

CMD ["php-fpm"]
