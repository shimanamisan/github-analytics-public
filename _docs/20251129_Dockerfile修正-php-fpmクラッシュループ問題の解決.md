# Dockerfile修正 - php-fpmクラッシュループ問題の解決

**日付**: 2025年11月29日

## 問題

appコンテナが**クラッシュループ**しています（`Restarting (64)`）。

ログを見ると：

```
github-analytics-backend  | Storage permissions set successfully
github-analytics-backend  | Usage: php [-n] [-e] [-h] [-i] [-m] [-v] [-t] [-p <prefix>] ...
```

`php-fpm` のヘルプが表示されている = **不正な引数が渡されている**

## 原因

Dockerfileの `ENTRYPOINT` と `CMD` の組み合わせに問題があります。

**問題のあったDockerfile:**

```dockerfile
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
```

**docker-entrypoint.sh:**

```bash
exec php-fpm "$@"
```

この組み合わせだと、実際に実行されるコマンドは：

```bash
docker-entrypoint.sh php-fpm

# ↓ スクリプト内で

exec php-fpm php-fpm  # ← "php-fpm" が引数として渡される！
```

`php-fpm php-fpm` は無効なコマンドなのでエラーになります。

## 修正

**修正後のDockerfile:**

```dockerfile
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD []
```

`docker-entrypoint.sh` 内で `exec php-fpm "$@"` が実行されるので、CMDは空配列にする必要があります。これにより、`php-fpm` が引数なしで正しく実行されます。

## 修正版Dockerfile（完全版）

```dockerfile
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

# ディレクトリ準備と権限設定
RUN mkdir -p /var/run/php-fpm \
      storage/logs storage/framework/cache \
      storage/framework/sessions storage/framework/views \
      bootstrap/cache \
  && rm -rf bootstrap/cache/*.php \
  && chown -R www-data:www-data /var/www/html /var/run/php-fpm \
  && chmod -R 775 storage bootstrap/cache

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD pgrep php-fpm > /dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD []
```

## 変更点

| 変更前 | 変更後 |
|:---|:---|
| `CMD ["php-fpm"]` | `CMD []` |

`docker-entrypoint.sh` 内で `exec php-fpm "$@"` が実行されるので、CMDは空配列にする必要があります。

## 参考

- `docker-entrypoint.sh` は `/usr/local/bin/docker-entrypoint.sh` に配置
- スクリプト内で `exec php-fpm "$@"` を実行しているため、CMDで `php-fpm` を指定すると引数として渡されてしまう
- 空配列 `[]` にすることで、`php-fpm` が引数なしで正しく実行される

