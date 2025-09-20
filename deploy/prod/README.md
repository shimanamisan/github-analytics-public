# 本番環境デプロイガイド

## ディレクトリ構成

```
deploy/
├── prod/                    # 本番環境設定
│   ├── docker-compose.yml   # 本番用Compose
│   ├── docker-compose.import.yml  # DBインポート用
│   ├── env.template         # 環境変数テンプレート
│   └── README.md           # このファイル
└── docker/                 # 本番用Dockerfile
    ├── php/
    │   ├── Dockerfile
    │   └── php-fpm.conf
    ├── nginx/
    │   ├── Dockerfile
    │   └── nginx.conf
    └── mysql/
        ├── Dockerfile
        └── my.cnf
```

## デプロイ手順

### 1. 環境変数設定

```bash
cd deploy/prod
cp env.template .env
# .envファイルを編集して実際の値を設定
```

### 2. ネットワーク作成

```bash
# NPM用の外部ネットワークを作成（存在しない場合）
docker network create nginx-proxy-manager-network
```

### 3. 本番環境起動

```bash
# 本番環境を起動
docker compose up -d --build

# ログ確認
docker compose logs -f
```

### 4. データベースインポート（必要時）

```bash
# phpMyAdminを一時的に起動
docker compose -f docker-compose.yml -f docker-compose.import.yml up -d

# ブラウザで http://localhost:8091 にアクセス
# インポート完了後は停止
docker compose -f docker-compose.yml -f docker-compose.import.yml down
```

### 5. 停止・再起動

```bash
# 停止
docker compose down

# 再起動
docker compose up -d

# 完全削除（データも削除）
docker compose down -v
```

## セキュリティ注意事項

- 本番環境では強力なパスワードを使用
- 不要なポートは公開しない
- 定期的なセキュリティアップデート
- ログの監視とローテーション設定

## トラブルシューティング

### 502エラー
- PHP-FPMとNginxの通信確認
- Unixソケットファイルの権限確認

### データベース接続エラー
- 環境変数の確認
- ネットワーク接続の確認

### ログ確認
```bash
# 全サービスのログ
docker compose logs

# 特定サービスのログ
docker compose logs app
docker compose logs web
docker compose logs db
```
