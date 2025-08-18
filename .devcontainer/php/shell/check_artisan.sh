#!/bin/bash

# artisanファイルのパス
ARTISAN_PATH="/workspace/src/artisan"

# ファイルが存在しているかどうかをチェック
if [ -f "$ARTISAN_PATH" ]; then
    # ファイルが実行可能かどうかをチェック
    if [ -x "$ARTISAN_PATH" ]; then
        echo "artisanがコマンドが見つかりました。コマンドを実行します。"
        # Artisanコマンドを実行
        php $ARTISAN_PATH cache:clear
        php $ARTISAN_PATH view:clear
    else
        echo "Error: artisanがコマンドは存在しますが実行可能ではありません。ファイルのパーミッションを確認してください。"
    fi
else
    echo "Error: artisanコマンドが $ARTISAN_PATH に存在しません。ファイルパスを確認してください。"
fi