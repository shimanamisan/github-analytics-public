# 本番環境デプロイガイド

## 📋 目次

1. [前提条件](#前提条件)
2. [GitHub設定](#github設定)
3. [サーバー設定](#サーバー設定)
4. [初回デプロイ](#初回デプロイ)
5. [運用手順](#運用手順)
6. [トラブルシューティング](#トラブルシューティング)

---

## 🔧 前提条件

### サーバー要件

- **OS**: Ubuntu 20.04 LTS 以上（または同等のLinuxディストリビューション）
- **Docker**: 24.0 以上
- **Docker Compose**: 2.20 以上
- **メモリ**: 最低 2GB（推奨 4GB以上）
- **ディスク**: 最低 20GB の空き容量

### 必要なもの

- GitHubアカウント
- GitHub Personal Access Token（PAT）
- 自宅サーバーまたはVPS

---

## 🐙 GitHub設定

### 1. Personal Access Token (PAT) の作成

GitHub Container Registry へのアクセスに必要です。

#### Fine-grained tokens（推奨）

1. GitHub にログイン
2. **Settings** → **Developer settings** → **Personal access tokens** → **Fine-grained tokens**
3. **Generate new token** をクリック
4. **Repository access**: `Only select repositories` → リポジトリを選択
5. **Repository permissions**:
   - ✅ `Contents`: Read and write
   - ✅ `Packages`: Read and write（重要！）
   - ✅ `Metadata`: Read-only（自動）
6. トークンを生成し、**必ず安全な場所に保存**

#### Classic tokens（代替）

Fine-grained tokensで `Packages` 権限が見つからない場合：

1. **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)**
2. **Generate new token (classic)** をクリック
3. 以下の権限を選択：
   - ✅ `write:packages` - パッケージのアップロード
   - ✅ `read:packages` - パッケージの読み取り
   - ✅ `repo` - プライベートリポジトリの場合
4. トークンを生成し、**必ず安全な場所に保存**

### 2. リポジトリ設定

#### Packages を有効化

通常はデフォルトで有効ですが、念のため確認：

```
リポジトリ → Settings → General → Features
→ "Packages" にチェック
```

#### Secrets の設定（オプション）

Self-hosted Runner で使用する場合、以下のSecretsを設定：

```
リポジトリ → Settings → Secrets and variables → Actions
→ New repository secret
```

- `GHCR_TOKEN`: 作成した Personal Access Token
- `GHCR_USERNAME`: あなたのGitHubユーザー名

---

## 🖥️ サーバー設定

### 1. Docker のインストール

```bash
# Docker公式のインストールスクリプトを使用
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# 現在のユーザーをdockerグループに追加
sudo usermod -aG docker $USER

# 再ログインして適用
newgrp docker

# 確認
docker --version
docker compose version
```

### 2. Self-hosted Runner のインストール

GitHub Actionsを自宅サーバーで実行するために必要です。

#### Runnerトークンの取得

```
リポジトリ → Settings → Actions → Runners
→ "New self-hosted runner" をクリック
```

表示される手順に従ってインストール：

```bash
# 作業ディレクトリ作成
mkdir ~/actions-runner && cd ~/actions-runner

# 最新版をダウンロード（バージョンは適宜変更）
curl -o actions-runner-linux-x64-2.321.0.tar.gz -L \
  https://github.com/actions/runner/releases/download/v2.321.0/actions-runner-linux-x64-2.321.0.tar.gz

# 展開
tar xzf ./actions-runner-linux-x64-2.321.0.tar.gz

# 設定（トークンはGitHubの画面から取得）
./config.sh --url https://github.com/YOUR_USERNAME/github-analytics-laravel \
  --token YOUR_RUNNER_TOKEN_FROM_GITHUB \
  --name production-server \
  --labels self-hosted,linux,x64,production

# サービスとして登録（自動起動設定）
sudo ./svc.sh install
sudo ./svc.sh start

# 状態確認
sudo ./svc.sh status
```

#### Runner用の環境変数設定（オプション）

`~/.bashrc` または `~/.profile` に追加：

```bash
export GHCR_TOKEN="your_personal_access_token"
export GHCR_USERNAME="your_github_username"
```

適用：
```bash
source ~/.bashrc
```

### 3. Nginx Proxy Manager の設定

既にNginx Proxy Managerを使用している場合、外部ネットワークを作成：

```bash
docker network create nginx-proxy-manager-network
```

---

## 🚀 初回デプロイ

### 1. デプロイディレクトリの準備

```bash
# デプロイ用ディレクトリ作成
mkdir -p ~/deploy/github-analytics
cd ~/deploy/github-analytics
```

### 2. 環境変数ファイルの作成

```bash
# env.template を参考に .env を作成
# （リポジトリから手動でコピーするか、以下のコマンドで取得）
curl -o .env https://raw.githubusercontent.com/YOUR_USERNAME/github-analytics-laravel/main/deploy/prod/env.template

# .envファイルを編集
nano .env
```

#### 必須設定項目

```bash
# GitHub Container Registry設定
REGISTRY_URL=ghcr.io/YOUR_GITHUB_USERNAME/github-analytics-laravel
IMAGE_TAG=latest

# MySQL設定（強力なパスワードに変更！）
MYSQL_DATABASE=github_traffic_api
MYSQL_USER=github_traffic_user
MYSQL_PASSWORD=your_secure_password_here_CHANGE_THIS
MYSQL_ROOT_PASSWORD=your_secure_root_password_here_CHANGE_THIS

# Redis設定（パスワード推奨）
REDIS_PASSWORD=your_redis_password_here_CHANGE_THIS

# Laravel設定
APP_NAME="GitHub Analytics"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=  # 後で生成

# データベース接続（MySQL設定と同じ値）
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=github_traffic_api
DB_USERNAME=github_traffic_user
DB_PASSWORD=your_secure_password_here_CHANGE_THIS

# Redis接続
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password_here_CHANGE_THIS
REDIS_PORT=6379

# セッション・キャッシュ・キュー（Redisを使用）
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# GitHub API設定
GITHUB_TOKEN=your_github_personal_access_token
GITHUB_OWNER=your_github_username
GITHUB_REPO=your_repo_name
```

### 3. APP_KEY の生成

```bash
# 一時的にappコンテナを起動してAPP_KEYを生成
docker run --rm ghcr.io/YOUR_USERNAME/github-analytics-laravel/app:latest \
  php artisan key:generate --show

# 出力された値を .env の APP_KEY に設定
```

### 4. GitHub Container Registry へのログイン

```bash
# PATを使用してログイン
echo YOUR_PERSONAL_ACCESS_TOKEN | docker login ghcr.io -u YOUR_GITHUB_USERNAME --password-stdin
```

### 5. 手動での初回デプロイ（テスト）

```bash
cd ~/deploy/github-analytics

# docker-compose.yml を配置（リポジトリからコピー）
curl -o docker-compose.yml https://raw.githubusercontent.com/YOUR_USERNAME/github-analytics-laravel/main/deploy/prod/docker-compose.yml

# イメージをPull
docker compose pull

# コンテナ起動
docker compose up -d

# ログ確認
docker compose logs -f

# ステータス確認
docker compose ps

# マイグレーション実行（初回のみ手動実行が必要）
docker compose exec app php artisan migrate --force

# 管理者アカウント作成（初回のみ手動実行が必要）
docker compose exec app php artisan db:seed --class=AdminUserSeeder --force
```

**注意**: 初回の手動デプロイではマイグレーションとシーダーを手動実行する必要がありますが、以降の自動デプロイでは自動的に実行されます。

### 6. Nginx Proxy Manager でドメイン設定

Nginx Proxy Managerの管理画面で：

1. **Proxy Hosts** → **Add Proxy Host**
2. 以下を設定：
   - **Domain Names**: `your-domain.com`
   - **Scheme**: `http`
   - **Forward Hostname/IP**: `github-analytics-web`
   - **Forward Port**: `80`
   - **SSL**: Let's Encryptで証明書を取得

---

## 🔄 運用手順

### 自動デプロイ

`main` ブランチにpushすると自動的にデプロイされます：

```bash
git push origin main
```

GitHub Actionsが以下を自動実行：
1. Dockerイメージのビルド
2. GitHub Container Registryへpush
3. Self-hosted Runnerでデプロイスクリプト実行
4. コンテナの起動とヘルスチェック待機
5. データベースマイグレーション実行
6. AdminUserSeeder実行（管理者アカウント自動作成）
7. キャッシュ最適化

### 手動デプロイ

GitHubの画面から手動実行も可能：

```
リポジトリ → Actions → Deploy to Production
→ Run workflow
```

### ログ確認

```bash
cd ~/deploy/github-analytics

# 全サービスのログ
docker compose logs -f

# 特定サービスのログ
docker compose logs -f app
docker compose logs -f web
docker compose logs -f db
docker compose logs -f redis
docker compose logs -f scheduler
docker compose logs -f worker
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

### phpMyAdminの起動

データベース管理用のGUIツールとしてphpMyAdminを利用できます。

```bash
cd ~/deploy/github-analytics

# phpMyAdminを起動（プロファイル指定が必要）
docker compose --profile tools up -d phpmyadmin

# アクセス
# ブラウザで http://サーバーIP:8091 を開く

# 停止（不要時は停止を推奨）
docker compose --profile tools down phpmyadmin
```

**セキュリティ上の注意**:
- phpMyAdminは必要な時のみ起動してください
- 作業完了後は必ず停止することを推奨
- 本番環境では外部からのアクセスを制限してください

---

## 🐛 トラブルシューティング

### 1. イメージがPullできない

**症状**: `Error response from daemon: pull access denied`

**解決策**:
```bash
# 再ログイン
echo YOUR_PAT_TOKEN | docker login ghcr.io -u YOUR_USERNAME --password-stdin

# パッケージの公開設定を確認
# GitHub → Packages → パッケージ名 → Package settings → Change visibility
```

### 2. コンテナが起動しない

**症状**: コンテナがすぐに停止する

**解決策**:
```bash
# ログ確認
docker compose logs app

# .envファイルの確認
cat .env | grep -v "^#" | grep -v "^$"

# ヘルスチェック確認
docker compose ps
```

### 3. データベース接続エラー

**症状**: `SQLSTATE[HY000] [2002] Connection refused`

**解決策**:
```bash
# データベースコンテナのステータス確認
docker compose ps db

# データベースログ確認
docker compose logs db

# データベース接続テスト
docker compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

**症状**: `SQLSTATE[HY000] [1045] Access denied for user`

**解決策**:
```bash
# .envファイルのデータベース設定を確認
cat .env | grep -E "(MYSQL_DATABASE|DB_DATABASE|MYSQL_USER|DB_USERNAME|MYSQL_PASSWORD|DB_PASSWORD)"

# 重要: MYSQL_DATABASE と DB_DATABASE は同じ値でなければなりません
# 正しい設定例:
# MYSQL_DATABASE=github_traffic_api
# DB_DATABASE=github_traffic_api  ← 同じ値
# MYSQL_USER=github_traffic_user
# DB_USERNAME=github_traffic_user  ← 同じ値
# MYSQL_PASSWORD=your_password
# DB_PASSWORD=your_password  ← 同じ値

# 設定が異なる場合は修正して、古いボリュームを削除
docker compose down
docker volume rm github-analytics_db
docker compose up -d
```

### 4. Redis接続エラー

**症状**: `Connection refused [tcp://redis:6379]`

**解決策**:
```bash
# Redisコンテナ確認
docker compose ps redis

# Redis接続テスト
docker compose exec redis redis-cli ping

# パスワードが設定されている場合
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD ping
```

### 5. パーミッションエラー

**症状**: `Permission denied` エラー

**解決策**:
```bash
# storageディレクトリの権限修正
docker compose exec app chown -R www-data:www-data /var/www/html/storage
docker compose exec app chmod -R 775 /var/www/html/storage
```

### 6. 502 Bad Gateway

**症状**: Nginxで502エラー

**解決策**:
```bash
# PHP-FPMソケット確認
docker compose exec web ls -la /var/run/php-fpm/

# appコンテナ確認
docker compose ps app
docker compose logs app

# 再起動
docker compose restart app web
```

### 7. マイグレーションが実行されない / テーブルが作成されない

**症状**: デプロイログでは「マイグレーション成功」と表示されるが、実際にはテーブルが作成されていない。500エラーが発生する。

**原因**: コンテナ起動直後にマイグレーションを実行すると、アプリケーションの準備が完了していない場合があります。

**解決策**:
```bash
# データベースの状態を確認
docker compose exec db mysql -u root -p${DB_ROOT_PASSWORD} -e "USE github_traffic_db; SHOW TABLES;"

# テーブルが存在しない場合は手動でマイグレーション実行
docker compose exec app php artisan migrate --force

# AdminUserSeederも実行
docker compose exec app php artisan db:seed --class=AdminUserSeeder --force

# アプリケーションが正常に動作するか確認
docker compose logs app
```

**予防策**: デプロイスクリプト（deploy.sh）では、以下の改善が実装されています：
- コンテナのヘルスチェック状態を監視
- データベース接続確認後にマイグレーション実行
- エラー発生時の詳細ログ出力

次回以降の自動デプロイでは、この問題は発生しません。

---

## 📊 モニタリング

### リソース使用状況

```bash
# コンテナのリソース使用状況
docker stats

# ディスク使用量
docker system df
```

### 定期メンテナンス

```bash
# 未使用イメージの削除
docker image prune -a

# 未使用ボリュームの削除（注意！）
docker volume prune

# 未使用ネットワークの削除
docker network prune
```

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

4. **ログ監視**
   - 定期的にログを確認し、不審なアクセスがないかチェック

---

## 📞 サポート

問題が解決しない場合：

1. GitHub Issuesで報告
2. ログファイルを添付
3. 環境情報を記載（Docker version、OS、etc）

---

**Happy Deploying! 🚀**
