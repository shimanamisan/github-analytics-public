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
    
    # ログディレクトリが存在しない場合は作成
    if [ ! -d "$LARAVEL_PATH/storage/logs" ]; then
        echo "Creating logs directory..."
        mkdir -p "$LARAVEL_PATH/storage/logs"
    fi
    
    # root所有のファイルも含めて、すべてのファイルの所有者を変更
    # エラーを無視して続行（一部のファイルが変更できない場合でも続行）
    chown -R h-nishihara:www-data "$LARAVEL_PATH/storage" 2>/dev/null || true
    
    # 特にログディレクトリ内のroot所有ファイルを明示的に処理
    if [ -d "$LARAVEL_PATH/storage/logs" ]; then
        echo "Fixing log files ownership (including root-owned files)..."
        find "$LARAVEL_PATH/storage/logs" -type f -user root -exec chown h-nishihara:www-data {} \; 2>/dev/null || true
        find "$LARAVEL_PATH/storage/logs" -type d -user root -exec chown h-nishihara:www-data {} \; 2>/dev/null || true
    fi
    
    # 権限を設定
    chmod -R 775 "$LARAVEL_PATH/storage"
    
    # ログディレクトリの権限設定
    chown -R h-nishihara:www-data "$LARAVEL_PATH/storage/logs" 2>/dev/null || true
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
    # root所有のファイルも含めて権限を設定
    find "$LARAVEL_PATH/storage" -type f -exec chmod 664 {} \; 2>/dev/null || true
    find "$LARAVEL_PATH/storage" -type d -exec chmod 775 {} \; 2>/dev/null || true
    # root所有のファイルの所有者も変更
    find "$LARAVEL_PATH/storage" -type f -user root -exec chown h-nishihara:www-data {} \; 2>/dev/null || true
    find "$LARAVEL_PATH/storage" -type d -user root -exec chown h-nishihara:www-data {} \; 2>/dev/null || true
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
