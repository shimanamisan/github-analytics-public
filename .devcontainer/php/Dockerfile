FROM php:8.2.29-fpm

# Composer Install
# 公式の Composer イメージを利用
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# rootユーザー実行した際に警告が出ないようにする
ENV COMPOSER_ALLOW_SUPERUSER 1
# Composerのホームディレクトリを変更
ENV COMPOSER_HOME /composer
# コマンドが実行できるようにパスを通す
ENV PATH $PATH:/composer/vendor/bin
# ロケールを日本に設定
ENV LANG ja_JP.UTF-8
ENV LC_ALL ja_JP.UTF-8
# タイムゾーンを日本に設定
ENV TZ Asia/Tokyo

RUN apt-get update && \
  # 必要なパッケージをインストール（supervisorは不要：PHP-FPMを直接起動）
  apt-get -y install git libicu-dev libonig-dev libzip-dev unzip locales tzdata && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/* && \
  locale-gen ja_JP.UTF-8 && \
  localedef -f UTF-8 -i ja_JP ja_JP.UTF-8 && \
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

RUN useradd -m -s /bin/bash h-nishihara && \
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
    usermod -a -G www-data h-nishihara
    
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
# PHP-FPMを直接起動（フォアグラウンドモード）
CMD ["php-fpm", "-F"]