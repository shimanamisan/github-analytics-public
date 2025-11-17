# 本番環境デプロイガイド

このドキュメントは簡易版です。**詳細な手順は [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) を参照してください。**

---

## 📁 ディレクトリ構成

```
deploy/
├── prod/                           # 本番環境設定
│   ├── docker-compose.yml          # 本番用Compose（レジストリからpull）
│   ├── docker-compose.import.yml   # DBインポート用
│   ├── env.template                # 環境変数テンプレート
│   ├── README.md                   # このファイル（簡易版）
│   ├── DEPLOYMENT_GUIDE.md         # 詳細デプロイガイド ★推奨★
│   ├── REDIS_MIGRATION.md          # Redis移行ガイド
│   └── IMPLEMENTATION_SUMMARY.md   # 実装完了サマリー
├── scripts/                        # デプロイスクリプト
│   └── deploy.sh                   # 自動デプロイスクリプト
└── docker/                         # 本番用Dockerfile
    ├── php/
    │   ├── Dockerfile              # PHP-FPM + Redis拡張
    │   └── php-fpm.conf
    ├── nginx/
    │   ├── Dockerfile
    │   └── nginx.conf
    └── mysql/
        ├── Dockerfile
        └── my.cnf
```

---

## 🚀 デプロイ方法

### **GitHub Actionsによる自動デプロイ（推奨）**

```bash
# ローカルで開発後、mainブランチにpush
git add .
git commit -m "feat: 新機能追加"
git push origin main
```

以下が自動実行されます：
1. ✅ Dockerイメージビルド
2. ✅ GitHub Container Registry (ghcr.io) へpush
3. ✅ Self-hosted Runnerがデプロイスクリプト実行
4. ✅ コンテナ更新・マイグレーション実行
5. ✅ デプロイ完了

**詳細**: [DEPLOYMENT_GUIDE.md - 自動デプロイ](DEPLOYMENT_GUIDE.md#🔄-運用手順)

---

## 🔧 初回セットアップ

### 前提条件

- Docker & Docker Compose インストール済み
- GitHub Personal Access Token（Fine-grained または Classic）
- Self-hosted Runner インストール済み

### 手順

#### 1. 環境変数設定

```bash
# 本番サーバーでデプロイディレクトリ作成
mkdir -p ~/deploy/github-analytics
cd ~/deploy/github-analytics

# テンプレートから.envを作成
# （リポジトリからコピーするか、手動で作成）
nano .env
```

**必須設定項目**:
```bash
REGISTRY_URL=ghcr.io/YOUR_GITHUB_USERNAME/github-analytics-laravel
IMAGE_TAG=latest

MYSQL_PASSWORD=your_secure_password
MYSQL_ROOT_PASSWORD=your_secure_root_password
REDIS_PASSWORD=your_redis_password

APP_URL=https://your-domain.com
APP_KEY=  # 後で生成

# その他の設定は env.template 参照
```

**詳細**: [DEPLOYMENT_GUIDE.md - 初回デプロイ](DEPLOYMENT_GUIDE.md#🚀-初回デプロイ)

#### 2. ネットワーク作成

```bash
# Nginx Proxy Manager用の外部ネットワークを作成
docker network create nginx-proxy-manager-network
```

#### 3. GitHub Container Registry へログイン

```bash
# Personal Access Tokenを使用してログイン
echo YOUR_PAT_TOKEN | docker login ghcr.io -u YOUR_GITHUB_USERNAME --password-stdin
```

#### 4. 初回デプロイ

GitHub Actionsで手動実行するか、`main`ブランチにpushして自動デプロイを実行

**手動での確認**:
```bash
cd ~/deploy/github-analytics

# イメージをpull
docker compose pull

# コンテナ起動
docker compose up -d

# ステータス確認
docker compose ps

# ログ確認
docker compose logs -f
```

---

## 🐳 コンテナ構成

本番環境では以下のコンテナが稼働します：

| コンテナ | 説明 | イメージソース |
|---------|------|--------------|
| **app** | Laravel アプリケーション（PHP-FPM） | GitHub Container Registry |
| **web** | Nginx Webサーバー | GitHub Container Registry |
| **db** | MySQL データベース | GitHub Container Registry |
| **redis** | Redis（セッション/キャッシュ/キュー） | Docker Hub（公式） |
| **scheduler** | Laravel スケジューラー（Cron） | GitHub Container Registry |
| **worker** | Laravel キューワーカー | GitHub Container Registry |

---

## 📊 Redis統合

本番環境では以下がRedisで管理されます：

- **セッション**: データベース → Redis（約10倍高速化）
- **キャッシュ**: データベース → Redis（約10倍高速化）
- **キュー**: データベース → Redis（約5倍高速化）

**詳細**: [REDIS_MIGRATION.md](REDIS_MIGRATION.md)

---

## 🔄 運用手順

### ログ確認

```bash
cd ~/deploy/github-analytics

# 全サービスのログ
docker compose logs -f

# 特定サービスのログ
docker compose logs -f app      # アプリケーション
docker compose logs -f web      # Nginx
docker compose logs -f db       # MySQL
docker compose logs -f redis    # Redis
docker compose logs -f scheduler # スケジューラー
docker compose logs -f worker   # キューワーカー
```

### コンテナの再起動

```bash
# 全コンテナ再起動
docker compose restart

# 特定コンテナのみ
docker compose restart app
docker compose restart worker
```

### データベースバックアップ

```bash
# バックアップ
docker compose exec db mysqldump -u root -p github_traffic_api > backup_$(date +%Y%m%d).sql

# リストア
docker compose exec -T db mysql -u root -p github_traffic_api < backup_20250101.sql
```

### 手動デプロイ

GitHub Actionsを使わず手動でデプロイする場合：

```bash
cd ~/deploy/github-analytics

# 最新イメージをpull
docker compose pull

# コンテナを停止・削除（データは保持）
docker compose down

# コンテナ起動
docker compose up -d

# マイグレーション実行
docker compose exec app php artisan migrate --force

# キャッシュ最適化
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

---

## 🐛 トラブルシューティング

### コンテナが起動しない

```bash
# ログ確認
docker compose logs app

# .envファイルの確認
cat .env | grep -v "^#" | grep -v "^$"

# ヘルスチェック確認
docker compose ps
```

### イメージがpullできない

```bash
# 再ログイン
echo YOUR_PAT_TOKEN | docker login ghcr.io -u YOUR_USERNAME --password-stdin

# パッケージの公開設定を確認
# GitHub → Packages → パッケージ名 → Package settings
```

### データベース接続エラー

```bash
# データベースコンテナのステータス確認
docker compose ps db

# データベースログ確認
docker compose logs db

# 接続テスト
docker compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Redis接続エラー

```bash
# Redisコンテナ確認
docker compose ps redis

# Redis接続テスト
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD ping

# Redisログ確認
docker compose logs redis
```

### 502 Bad Gateway

```bash
# PHP-FPMソケット確認
docker compose exec web ls -la /var/run/php-fpm/

# appコンテナ確認
docker compose ps app
docker compose logs app

# 再起動
docker compose restart app web
```

**詳細なトラブルシューティング**: [DEPLOYMENT_GUIDE.md - トラブルシューティング](DEPLOYMENT_GUIDE.md#🐛-トラブルシューティング)

---

## 📚 関連ドキュメント

- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - 詳細なデプロイガイド（★推奨★）
- **[REDIS_MIGRATION.md](REDIS_MIGRATION.md)** - Redis移行ガイド
- **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - 実装サマリー
- **[env.template](env.template)** - 環境変数テンプレート

---

## 🔐 セキュリティ

### 推奨事項

1. **強力なパスワード使用**
   - MySQL、Redis、APP_KEY は全て異なる強力なパスワードを設定

2. **定期的なアップデート**
   ```bash
   # パッケージ更新
   sudo apt update && sudo apt upgrade

   # Dockerイメージ更新
   docker compose pull
   docker compose up -d
   ```

3. **ファイアウォール設定**
   ```bash
   # 必要なポートのみ開放
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```

4. **定期的なバックアップ**
   - データベースバックアップを自動化
   - Redisデータの定期保存

---

## 📞 サポート

問題が解決しない場合：

1. [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) の詳細手順を確認
2. [REDIS_MIGRATION.md](REDIS_MIGRATION.md) でRedis関連を確認
3. GitHub Issuesで報告（ログファイルを添付）

---

**Happy Deploying! 🚀**
