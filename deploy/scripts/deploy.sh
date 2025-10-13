#!/bin/bash

###############################################################################
# GitHub Traffic API - Production Deployment Script
###############################################################################

set -e  # エラーで停止

# カラー出力用の定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ログ出力関数
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# デプロイディレクトリへ移動
DEPLOY_DIR="/home/$(whoami)/deploy/github-traffic-api"
COMPOSE_FILE="$DEPLOY_DIR/docker-compose.yml"

log_info "Deployment started at $(date '+%Y-%m-%d %H:%M:%S')"

# デプロイディレクトリが存在しない場合は作成
if [ ! -d "$DEPLOY_DIR" ]; then
    log_warning "Deploy directory does not exist. Creating: $DEPLOY_DIR"
    mkdir -p "$DEPLOY_DIR"
fi

# docker-compose.ymlをコピー
log_info "Copying docker-compose.yml to $DEPLOY_DIR"
cp -f ../prod/docker-compose.yml "$DEPLOY_DIR/"

# .envファイルの存在確認（初回デプロイ時の警告）
if [ ! -f "$DEPLOY_DIR/.env" ]; then
    log_error ".env file not found in $DEPLOY_DIR"
    log_error "Please create .env file based on env.template before deployment"
    log_error "Run: cp ../prod/env.template $DEPLOY_DIR/.env"
    exit 1
fi

# 現在のディレクトリを変更
cd "$DEPLOY_DIR"

# GitHub Container Registryにログイン
log_info "Logging in to GitHub Container Registry..."
if [ -n "$GHCR_TOKEN" ]; then
    echo "$GHCR_TOKEN" | docker login ghcr.io -u "$GHCR_USERNAME" --password-stdin
    log_success "Logged in to ghcr.io"
else
    log_warning "GHCR_TOKEN not set. Attempting to use cached credentials..."
fi

# 最新のイメージをPull
log_info "Pulling latest Docker images from registry..."
docker compose pull

# コンテナの停止と削除（データベースボリュームは保持）
log_info "Stopping existing containers..."
docker compose down --remove-orphans

# コンテナの起動
log_info "Starting containers..."
docker compose up -d

# ヘルスチェック待機
log_info "Waiting for services to be healthy..."
MAX_WAIT=60
WAIT_COUNT=0
while [ $WAIT_COUNT -lt $MAX_WAIT ]; do
    DB_HEALTHY=$(docker compose ps db --format json | grep -o '"Health":"[^"]*"' | cut -d'"' -f4)
    APP_HEALTHY=$(docker compose ps app --format json | grep -o '"Health":"[^"]*"' | cut -d'"' -f4)

    if [ "$DB_HEALTHY" = "healthy" ] && [ "$APP_HEALTHY" = "healthy" ]; then
        log_success "All services are healthy!"
        break
    fi

    WAIT_COUNT=$((WAIT_COUNT + 1))
    log_info "Waiting for services... ($WAIT_COUNT/$MAX_WAIT) - DB: $DB_HEALTHY, App: $APP_HEALTHY"
    sleep 2
done

if [ $WAIT_COUNT -eq $MAX_WAIT ]; then
    log_error "Services did not become healthy in time!"
    docker compose ps
    docker compose logs --tail=50 app
    docker compose logs --tail=50 db
    exit 1
fi

# データベース接続確認（追加の安全チェック）
log_info "Verifying database connection..."
MAX_DB_RETRIES=10
DB_RETRY_COUNT=0
while [ $DB_RETRY_COUNT -lt $MAX_DB_RETRIES ]; do
    if docker compose exec -T app php artisan db:show > /dev/null 2>&1; then
        log_success "Database connection verified!"
        break
    fi
    DB_RETRY_COUNT=$((DB_RETRY_COUNT + 1))
    log_info "Database connection check... retry $DB_RETRY_COUNT/$MAX_DB_RETRIES"
    sleep 2
done

if [ $DB_RETRY_COUNT -eq $MAX_DB_RETRIES ]; then
    log_error "Could not establish database connection!"
    docker compose logs --tail=50 app
    exit 1
fi

# データベースマイグレーション実行
log_info "Running database migrations..."
if docker compose exec -T app php artisan migrate --force; then
    log_success "Database migrations completed successfully"
else
    log_error "Database migrations failed!"
    log_info "Checking app container logs..."
    docker compose logs --tail=50 app
    exit 1
fi

# 管理者ユーザーの作成（初回デプロイ時 or 未作成時）
log_info "Running AdminUserSeeder..."
if docker compose exec -T app php artisan db:seed --class=AdminUserSeeder --force; then
    log_success "AdminUserSeeder completed successfully"
else
    log_error "AdminUserSeeder failed!"
    log_info "Checking app container logs..."
    docker compose logs --tail=50 app
    exit 1
fi

# キャッシュクリア＆最適化
log_info "Clearing and optimizing caches..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan optimize

# コンテナステータス確認
log_info "Checking container status..."
docker compose ps

# ログ確認（最後の20行）
log_info "Recent logs:"
docker compose logs --tail=20

log_success "Deployment completed successfully at $(date '+%Y-%m-%d %H:%M:%S')"
log_info "Application is running at the configured domain"

# クリーンアップ（古いイメージの削除）
log_info "Cleaning up old Docker images..."
docker image prune -f

log_success "All done! 🚀"
