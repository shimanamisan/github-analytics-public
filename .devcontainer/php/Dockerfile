FROM php:8.2.29-fpm-alpine

# Composer Install
# 公式の Composer イメージを利用
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# rootユーザー実行した際に警告が出ないようにする
ENV COMPOSER_ALLOW_SUPERUSER 1
# Composerのホームディレクトリを変更
ENV COMPOSER_HOME /composer
# コマンドが実行できるようにパスを通す
ENV PATH $PATH:/composer/vendor/bin
# タイムゾーンを日本に設定
ENV TZ Asia/Tokyo

RUN apk add --no-cache \
    # ビルドツール（PHP拡張コンパイルに必要）
    autoconf g++ make linux-headers \
    # ランタイムライブラリ
    git icu-dev icu-libs oniguruma-dev libzip-dev unzip tzdata bash shadow && \
  mkdir /var/run/php-fpm && \
  mkdir /var/log/php && \
  docker-php-ext-install intl pdo_mysql mbstring zip bcmath && \
  pecl install xdebug redis && \
  # Xdebug と Redis を有効化する
  docker-php-ext-enable xdebug redis

COPY ./php-fpm.d/zzz-www.conf /usr/local/etc/php-fpm.d/zzz-www.conf
COPY ./php.ini /usr/local/etc/php/php.ini
# スクリプトをコンテナにコピー
COPY ./shell/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY ./shell/fix-laravel-permissions.sh /usr/local/bin/fix-laravel-permissions.sh

WORKDIR /workspace

RUN adduser -D -s /bin/bash h-nishihara && \
    # Xebugで使用するログファイルの作成とファイルへのアクセス権限を付与する
    touch /var/log/xdebug.log && \
    chown h-nishihara:h-nishihara /var/log/xdebug.log && \
    chmod 644 /var/log/xdebug.log && \
    # スクリプトの権限を変更
    chown h-nishihara:h-nishihara /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown h-nishihara:h-nishihara /usr/local/bin/fix-laravel-permissions.sh && \
    chmod +x /usr/local/bin/fix-laravel-permissions.sh && \
    # h-nishiharaユーザーをwww-dataグループに追加
    addgroup h-nishihara www-data
    
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
# PHP-FPMを直接起動（フォアグラウンドモード）
CMD ["php-fpm", "-F"]