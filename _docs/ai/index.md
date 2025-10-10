# USER

```
以下の記事にDockerを使用した本番環境用の運用が記述されていました。
https://note.com/gradito/n/n6b196d424ae1

この記事の内容で、特にデプロイ関連で参考にできる設定や内容はありますか？

例えばLaravelのコードでGithub Actions内でフロントエンドをビルドしてそのままコードをrsyncコマンドでデプロイする仕方は理解できているのですが、docker-compose.ymlやDockerfileなどのコンテナの構成をデプロイ？もしくは、Github Actionsでコンテナをビルドして本番環境でビルドされたイメージをダウンロード、そのまま起動（Laravel側で変化があればマイグレーションも実行）することは可能でしょうか？
```


## 📦 GitHub Container Registry の使用方法

### **無料で利用可能です！**

GitHub Container Registry (ghcr.io) は以下の条件で**無料**で使用できます：

- **パブリックイメージ**: 完全無料、無制限
- **プライベートイメージ**: 
  - 無料枠: 500MB ストレージ + 1GB/月の転送量
  - GitHub Pro/Team/Enterprise: より大きな枠（2GB〜50GB）

あなたのプロジェクトなら無料枠で十分運用可能です。

### **必要な設定**

**1. Personal Access Token (PAT) の作成:**
```
GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
→ Generate new token
```

必要な権限：
- `write:packages` - パッケージのアップロード
- `read:packages` - パッケージの読み取り
- `delete:packages` - 古いイメージの削除（オプション）
- `repo` - プライベートリポジトリの場合

**2. リポジトリでPackagesを有効化:**
```
リポジトリ → Settings → General → Features
→ "Packages" にチェック（通常デフォルトで有効）
```

**3. 初回のみ手動でパッケージの可視性設定:**
最初のイメージpush後、以下で設定：
```
リポジトリ → Packages → パッケージ名をクリック 
→ Package settings → Visibility（Public/Private選択）
```

## 🏃 Self-hosted Runner について

### **はい、自宅サーバーへのインストールが必要です**

Self-hosted Runnerは、GitHub Actionsのジョブを自宅サーバーで実行するためのソフトウェアです。

**インストール手順：**

**1. GitHubでRunnerトークンを取得:**
```
リポジトリ → Settings → Actions → Runners 
→ "New self-hosted runner" をクリック
```

**2. 自宅サーバーでインストール:**
```bash
# 作業ディレクトリ作成
mkdir ~/actions-runner && cd ~/actions-runner

# 最新版をダウンロード（2024年12月時点）
curl -o actions-runner-linux-x64-2.321.0.tar.gz -L \
  https://github.com/actions/runner/releases/download/v2.321.0/actions-runner-linux-x64-2.321.0.tar.gz

# 展開
tar xzf ./actions-runner-linux-x64-2.321.0.tar.gz

# 設定（GitHubの画面に表示されるトークンを使用）
./config.sh --url https://github.com/YOUR_USERNAME/GitHub-Traffic-API-Laravel \
  --token YOUR_RUNNER_TOKEN_FROM_GITHUB

# サービスとして登録（自動起動設定）
sudo ./svc.sh install
sudo ./svc.sh start

# 状態確認
sudo ./svc.sh status
```

**3. Dockerグループへの追加（重要）:**
```bash
# runnerユーザーをdockerグループに追加
sudo usermod -aG docker $USER

# 再ログインまたは
newgrp docker
```

### **Self-hosted Runnerを使わない代替案**

もしSelf-hosted Runnerの設定が面倒な場合、以下の代替案があります：

**Webhook方式（推奨）:**
```yaml
# .github/workflows/deploy.yml
name: Trigger Deployment

on:
  push:
    branches: [main]

jobs:
  trigger-deploy:
    runs-on: ubuntu-latest  # GitHubのランナーを使用
    steps:
      - name: Trigger deployment webhook
        run: |
          curl -X POST https://your-home-server.local/webhook/deploy \
            -H "X-Github-Event: push" \
            -H "X-Hub-Signature-256: ${{ secrets.WEBHOOK_SECRET }}" \
            -d '{"ref": "${{ github.ref }}", "sha": "${{ github.sha }}"}'
```

## 🔄 Redisを含めた理由

### **Redisは必須ではありませんが、本番環境では強く推奨されます**

**現在の構成（Redisなし）の問題点：**

1. **セッション管理**: ファイルベースだとコンテナ再起動で全ユーザーがログアウト
2. **キャッシュ**: ファイルベースは遅い、メモリ効率が悪い
3. **キュー処理**: データベースキューは遅い、DBに負荷

**Redisを使うメリット：**

```php
// .env の設定例
CACHE_DRIVER=redis      # file → redis（高速化）
SESSION_DRIVER=redis    # file → redis（永続化）
QUEUE_CONNECTION=redis  # database → redis（高速化）
```

### **Redisなしで運用する場合の修正版**

もしRedisを使わない場合は、以下のように修正してください：

**deploy/prod/docker-compose.yml（Redisなし版）:**
```yaml
version: '3.8'

services:
  app:
    image: ${REGISTRY_URL}/app:${IMAGE_TAG:-latest}
    container_name: github-traffic-api-app
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      # Redisなしの設定
      - CACHE_DRIVER=file
      - SESSION_DRIVER=file
      - QUEUE_CONNECTION=database
    env_file:
      - .env
    volumes:
      - app-storage:/var/www/html/storage  # セッション・キャッシュ永続化
      - app-cache:/var/www/html/bootstrap/cache
      - php-fpm-socket:/var/run/php-fpm
    networks:
      - app-network
    depends_on:
      db:
        condition: service_healthy

  web:
    # 変更なし

  db:
    # 変更なし

  # Redisコンテナは削除

  # スケジューラー（Redisなしでも動作）
  scheduler:
    image: ${REGISTRY_URL}/app:${IMAGE_TAG:-latest}
    container_name: github-traffic-api-scheduler
    restart: unless-stopped
    command: php artisan schedule:work
    environment:
      - CACHE_DRIVER=file
      - QUEUE_CONNECTION=database
    env_file:
      - .env
    volumes:
      - app-storage:/var/www/html/storage  # 共有ストレージ重要
    networks:
      - app-network
    depends_on:
      - db

  # ワーカー（データベースキューを使用）
  worker:
    image: ${REGISTRY_URL}/app:${IMAGE_TAG:-latest}
    container_name: github-traffic-api-worker
    restart: unless-stopped
    command: php artisan queue:work database --sleep=3 --tries=3
    environment:
      - QUEUE_CONNECTION=database
    env_file:
      - .env
    volumes:
      - app-storage:/var/www/html/storage
    networks:
      - app-network
    depends_on:
      - db

volumes:
  app-storage:  # セッション・キャッシュの永続化に必須
  app-cache:
  db-data:
  php-fpm-socket:
```

### **段階的移行の提案**

1. **初期段階**：Redisなしで運用開始
2. **パフォーマンス問題が出たら**：Redisを追加

Redisの追加は後からでも簡単：
```bash
# Redisコンテナだけ追加起動
docker compose up -d redis

# .envを更新
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# アプリケーション再起動
docker compose restart app scheduler worker
```

## 📝 まとめ

**必要な作業の優先順位：**

1. **最優先**: GitHub PATトークン作成（5分）
2. **優先**: Self-hosted Runner設置（30分）
   - または、Webhook方式なら別途実装
3. **オプション**: Redis追加（必要に応じて）

**最小構成で始める場合：**
- GitHub Container Registry ✅（無料・簡単）
- Self-hosted Runner ✅（または Webhook）
- Redis ❌（後から追加可能）

これで、シンプルかつ実用的な自動デプロイが実現できます！