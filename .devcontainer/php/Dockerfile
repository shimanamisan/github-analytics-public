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

EXPOSE 5173

RUN apt-get update && \
  # cron と supervisor を追加
  apt-get -y install git cron supervisor libicu-dev libonig-dev libzip-dev unzip locales && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/* && \
  locale-gen en_US.UTF-8 && \
  localedef -f UTF-8 -i en_US en_US.UTF-8 && \
  mkdir /var/run/php-fpm && \
  mkdir /var/log/php && \
  docker-php-ext-install intl pdo_mysql mbstring zip bcmath && \
  pecl install xdebug && \
  # Xdebug を有効化する
  docker-php-ext-enable xdebug

COPY ./php-fpm.d/zzz-www.conf /usr/local/etc/php-fpm.d/zzz-www.conf
COPY ./php.ini /usr/local/etc/php/php.ini
# cronの設定内容が書かれたファイルをコンテナ内にコピーする
COPY ./cron/h-nishihara /etc/cron.d/cron
# supervisordの設定ファイルをコピーする
COPY ./supervisord/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
# スクリプトをコンテナにコピー
COPY ./shell/check_artisan.sh /usr/local/bin/check_artisan
COPY ./shell/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY ./shell/fix-laravel-permissions.sh /usr/local/bin/fix-laravel-permissions.sh

WORKDIR /workspace

RUN useradd -m -s /bin/bash h-nishihara && \
    # cronファイルの権限を変更
    chmod 0644 /etc/cron.d/cron && \
    # cronで使用するログファイルの作成とファイルへのアクセス権限を付与する
    touch /var/log/cron.log && \
    chmod 0644 /var/log/cron.log && \
    chown h-nishihara:h-nishihara /var/log/cron.log && \
    # Xebugで使用するログファイルの作成とファイルへのアクセス権限を付与する
    touch /var/log/xdebug.log && \
    chown h-nishihara:h-nishihara /var/log/xdebug.log && \
    chmod 644 /var/log/xdebug.log && \
    # check_artisanの権限を変更
    chown h-nishihara:h-nishihara /usr/local/bin/check_artisan && \
    chmod +x /usr/local/bin/check_artisan && \
    chown h-nishihara:h-nishihara /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown h-nishihara:h-nishihara /usr/local/bin/fix-laravel-permissions.sh && \
    chmod +x /usr/local/bin/fix-laravel-permissions.sh && \

    # h-nishiharaユーザーをwww-dataグループに追加
    usermod -a -G www-data h-nishihara
    
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
# supervisordを起動
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]