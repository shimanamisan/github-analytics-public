#!/bin/bash

# Laravel権限修正スクリプト
# 起動時にLaravelプロジェクトの権限を自動修正

echo "=== Laravel Permission Fix Script Started ==="

# Laravelプロジェクトのパス
LARAVEL_PATH="/workspace/src"

# Laravelプロジェクトが存在するかチェック
if [ ! -d "$LARAVEL_PATH" ]; then
    echo "Laravel project not found at: $LARAVEL_PATH"
    echo "Skipping permission fix..."
    exit 0
fi

echo "Laravel project found at: $LARAVEL_PATH"

# storageディレクトリの権限修正
if [ -d "$LARAVEL_PATH/storage" ]; then
    echo "Fixing storage directory permissions..."
    chown -R h-nishihara:www-data "$LARAVEL_PATH/storage"
    chmod -R 775 "$LARAVEL_PATH/storage"
    
    # ログディレクトリが存在しない場合は作成
    if [ ! -d "$LARAVEL_PATH/storage/logs" ]; then
        echo "Creating logs directory..."
        mkdir -p "$LARAVEL_PATH/storage/logs"
    fi
    
    # ログディレクトリの権限設定
    chown -R h-nishihara:www-data "$LARAVEL_PATH/storage/logs"
    chmod -R 775 "$LARAVEL_PATH/storage/logs"
    
    # SGIDビットを設定（新しく作成されるファイルがwww-dataグループになるように）
    chmod g+s "$LARAVEL_PATH/storage/logs"
    
    echo "Storage permissions fixed successfully"
else
    echo "Storage directory not found, creating it..."
    mkdir -p "$LARAVEL_PATH/storage/logs"
    chown -R h-nishihara:www-data "$LARAVEL_PATH/storage"
    chmod -R 775 "$LARAVEL_PATH/storage"
    
    # SGIDビットを設定（新しく作成されるファイルがwww-dataグループになるように）
    chmod g+s "$LARAVEL_PATH/storage/logs"
fi

# bootstrap/cacheディレクトリの権限修正
if [ -d "$LARAVEL_PATH/bootstrap/cache" ]; then
    echo "Fixing bootstrap/cache directory permissions..."
    chown -R h-nishihara:www-data "$LARAVEL_PATH/bootstrap/cache"
    chmod -R 775 "$LARAVEL_PATH/bootstrap/cache"
    echo "Bootstrap/cache permissions fixed successfully"
else
    echo "Bootstrap/cache directory not found, creating it..."
    mkdir -p "$LARAVEL_PATH/bootstrap/cache"
    chown -R h-nishihara:www-data "$LARAVEL_PATH/bootstrap/cache"
    chmod -R 775 "$LARAVEL_PATH/bootstrap/cache"
fi

# より厳密な権限設定（セキュリティ重視）
echo "Applying strict file permissions..."
if [ -d "$LARAVEL_PATH/storage" ]; then
    find "$LARAVEL_PATH/storage" -type f -exec chmod 664 {} \;
    find "$LARAVEL_PATH/storage" -type d -exec chmod 775 {} \;
fi

if [ -d "$LARAVEL_PATH/bootstrap/cache" ]; then
    find "$LARAVEL_PATH/bootstrap/cache" -type f -exec chmod 664 {} \;
    find "$LARAVEL_PATH/bootstrap/cache" -type d -exec chmod 775 {} \;
fi

echo "=== Laravel Permission Fix Script Completed ==="

# 権限確認のための情報表示
echo "=== Permission Summary ==="
echo "Storage directory:"
ls -la "$LARAVEL_PATH/storage/" 2>/dev/null || echo "Storage directory not accessible"
echo "Bootstrap/cache directory:"
ls -la "$LARAVEL_PATH/bootstrap/" 2>/dev/null || echo "Bootstrap directory not accessible"
echo "=========================="
