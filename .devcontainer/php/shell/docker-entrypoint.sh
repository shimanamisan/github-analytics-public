#!/bin/bash

echo "=== Container Starting ==="

# 現在のユーザーを確認
CURRENT_USER=$(whoami)
echo "Current user: $CURRENT_USER"

# Laravel パーミッション修正
# root所有のファイルも処理できるように、rootで実行することを前提とする
if [ -f "/usr/local/bin/fix-laravel-permissions.sh" ]; then
    echo "Running Laravel permission fix..."
    # root所有のファイルも処理できるように、エラーを無視して続行
    /usr/local/bin/fix-laravel-permissions.sh || echo "Warning: Some permission fixes may have failed"
fi

# artisan キャッシュクリア（check_artisan.shの処理を統合）
ARTISAN_PATH="/workspace/src/artisan"
if [ -f "$ARTISAN_PATH" ]; then
    echo "Clearing Laravel caches..."
    php "$ARTISAN_PATH" cache:clear 2>/dev/null || echo "Cache clear skipped (not yet available)"
    php "$ARTISAN_PATH" view:clear 2>/dev/null || echo "View clear skipped (not yet available)"
else
    echo "Warning: artisan not found at $ARTISAN_PATH"
fi

echo "Starting PHP-FPM..."
# 引数（$@）を渡してPHP-FPMまたは他のコマンドを実行
exec docker-php-entrypoint "$@"