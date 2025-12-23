# ===========================================
# アセットビルド用ステージ
# ===========================================
FROM node:20-alpine AS assets
WORKDIR /app
COPY src/package*.json ./src/
RUN cd ./src && npm ci --ignore-scripts
COPY src ./src
RUN cd ./src && npm run build

# ===========================================
# PHP拡張ビルド用ステージ
# ===========================================
FROM php:8.2.29-fpm-alpine AS php-builder
RUN apk add --no-cache --virtual .build-deps \
      autoconf g++ make linux-headers \
      icu-dev oniguruma-dev libzip-dev \
  && docker-php-ext-install intl pdo_mysql mbstring zip bcmath \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apk del .build-deps

# ===========================================
# Composer依存関係インストール用ステージ
# ===========================================
FROM composer:2 AS composer-deps
WORKDIR /app
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

COPY src .
RUN composer dump-autoload --optimize --classmap-authoritative --ignore-platform-reqs

# ===========================================
# 本番用ステージ
# ===========================================
FROM php:8.2.29-fpm-alpine AS prod

ENV TZ=Asia/Tokyo \
    APP_ENV=production \
    APP_DEBUG=0

# ランタイム依存のみインストール
RUN apk add --no-cache icu-libs libzip oniguruma tzdata

# PHP拡張をコピー
COPY --from=php-builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

WORKDIR /var/www/html

# アプリケーションコードとvendorをコピー
COPY --from=composer-deps /app /var/www/html
COPY --from=assets /app/src/public/build /var/www/html/public/build

# PHP-FPM設定
COPY deploy/docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-www.conf

# エントリーポイントスクリプトをコピー
COPY deploy/docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# PHP-FPMソケット用ディレクトリのみ作成（権限設定はentrypointで実行時に行う）
RUN mkdir -p /var/run/php-fpm

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD pgrep php-fpm > /dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD []