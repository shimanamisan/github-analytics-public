#!/bin/bash

echo "=== Container Starting ==="

# Laravel パーミッション修正
if [ -f "/usr/local/bin/fix-laravel-permissions.sh" ]; then
    echo "Running Laravel permission fix..."
    /usr/local/bin/fix-laravel-permissions.sh
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