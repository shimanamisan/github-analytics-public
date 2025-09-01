#!/bin/bash

# Laravelスケジューラーを起動するスクリプト
# このスクリプトはcronで毎分実行されることを想定しています

cd /workspace/src

# 環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Laravelスケジューラーを実行
php artisan schedule:run >> /dev/null 2>&1
