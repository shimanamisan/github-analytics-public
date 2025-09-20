# DockerコンテナネットワークとNPM設定トラブルシューティング

## 概要
このドキュメントでは、Dockerコンテナのネットワーク所属確認方法と、Nginx Proxy Manager (NPM) を使用した際の502/503エラーの原因と解決方法について説明します。

## 1. コンテナのネットワーク所属確認方法

### 1.1 基本的な確認方法

#### すべてのコンテナとその所属ネットワークを一覧表示
```bash
docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Networks}}"
```

#### 特定のコンテナの詳細情報を確認
```bash
docker inspect <コンテナ名> | grep -A 10 "Networks"
```

#### 特定のネットワークに接続されているコンテナを確認
```bash
docker network inspect <ネットワーク名> | grep -A 5 -B 5 "<コンテナ名>"
```

### 1.2 確認結果の例
```
NAMES                           IMAGE                               NETWORKS
github-traffic-api-web          github-traffic-api-web:prod         nginx-proxy-manager-network
github-traffic-api-phpmyadmin   github-traffic-api-phpmyadmin:1.0   nginx-proxy-manager-network
github-traffic-api-backend      github-traffic-api-backend:prod     nginx-proxy-manager-network
github-traffic-api-db           github-traffic-api-db:1.0           nginx-proxy-manager-network
```

## 2. NPM設定での502/503エラーの原因と解決方法

### 2.1 phpMyAdminコンテナでの503エラー

#### 問題の症状
- NPMで`github-traffic-api-phpmyadmin`コンテナとポート8091を指定
- ドメイン`github-traffic.my-portfolio.mydns.jp`でアクセス
- 503エラーが返される

#### 原因
NPMの設定で**ポート設定が間違っている**

**間違った設定:**
```nginx
set $server         "github-traffic-api-phpmyadmin";
set $port           8091;  # ← これが間違い
```

**正しい設定:**
```nginx
set $server         "github-traffic-api-phpmyadmin";
set $port           80;    # ← コンテナ内部のポート80を指定
```

#### 解決方法
NPMの管理画面で以下のように設定を変更：
- **Forward Hostname/IP**: `github-traffic-api-phpmyadmin`
- **Forward Port**: `80` （8091ではなく80）
- **Domain Names**: `github-traffic.my-portfolio.mydns.jp`

#### 理由
- phpMyAdminコンテナは内部でポート80で動作
- ホスト側ではポート8091にマッピング（`0.0.0.0:8091->80/tcp`）
- NPMは同じDockerネットワーク内にあるため、コンテナ名で直接アクセスする際は**内部ポート（80）**を指定する必要がある

### 2.2 Webアプリケーションコンテナでの502エラー

#### 問題の症状
- `github-traffic-api-web`コンテナでNPM設定
- ポート80を指定
- 502 Bad Gatewayエラーが返される

#### 原因
**PHP-FPMとNginxの設定の不整合**

**PHP-FPM側（backend）:**
```ini
listen = 127.0.0.1:9000  # TCPポート9000でリッスン
```

**Nginx側（web）:**
```nginx
fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;  # Unixソケットを期待
```

#### 解決方法

##### 方法1: PHP-FPMをUnixソケットに変更（推奨）

`github-traffic-api-backend`のPHP-FPM設定を変更：

```ini
listen = /var/run/php-fpm/php-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
```

##### 方法2: NginxをTCPに変更

`github-traffic-api-web`のNginx設定を変更：

```nginx
fastcgi_pass github-traffic-api-backend:9000;
```

#### 推奨される修正方法
**方法1（Unixソケット）** を推奨する理由：
- パフォーマンスが良い
- セキュリティが高い
- 同じDockerネットワーク内での通信に適している

## 3. 開発環境と本番環境の違い

### 3.1 開発環境で正常に動作する理由

#### PHP-FPM設定の違い

**開発環境（正常動作）:**
```ini
[www]
listen = /var/run/php-fpm/php-fpm.sock  # Unixソケット
listen.owner = www-data
listen.group = www-data
listen.mode = 0666
```

**本番環境（502エラー）:**
```ini
listen = 127.0.0.1:9000  # TCPポート
```

#### Docker設定の違い

**開発環境:**
- カスタムPHP-FPM設定ファイル（`zzz-www.conf`）を使用
- Unixソケットでリッスンするように設定
- `php-fpm-socket`ボリュームを両方のコンテナで共有

**本番環境:**
- デフォルトのPHP-FPM設定を使用
- TCPポート9000でリッスン
- `php-fpm-socket`ボリュームを両方のコンテナで共有

### 3.2 本番環境の修正方法

本番環境のPHPコンテナのDockerfileに以下を追加：

```dockerfile
COPY ./php-fpm.d/zzz-www.conf /usr/local/etc/php-fpm.d/zzz-www.conf
```

`zzz-www.conf`ファイルの内容：

```ini
[www]
listen = /var/run/php-fpm/php-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0666
```

## 4. トラブルシューティング手順

### 4.1 コンテナの状態確認
```bash
# コンテナの状態確認
docker ps | grep <コンテナ名>

# コンテナのログ確認
docker logs <コンテナ名> --tail 20

# ヘルスチェック状況確認
docker inspect <コンテナ名> | grep -A 10 -B 5 "Health"
```

### 4.2 ネットワーク接続性確認
```bash
# ローカルホスト経由でのアクセステスト
curl -I http://localhost:<ポート番号>

# コンテナ内部IP経由でのアクセステスト
curl -I http://<コンテナ内部IP>:<ポート番号>

# コンテナ内からのアクセステスト
docker exec <コンテナ名> curl -I http://localhost:<ポート番号>
```

### 4.3 NPMログの確認
```bash
# NPMコンテナのログ確認
docker logs nginx-proxy-manager-prod --tail 20

# NPMのエラーログ確認
docker exec nginx-proxy-manager-prod cat /data/logs/proxy-host-<ID>_error.log | tail -10
```

### 4.4 設定ファイルの確認
```bash
# NPMの設定ファイル確認
docker exec nginx-proxy-manager-prod cat /data/nginx/proxy_host/<ID>.conf

# Nginx設定ファイル確認
docker exec <webコンテナ名> cat /etc/nginx/conf.d/default.conf

# PHP-FPM設定確認
docker exec <backendコンテナ名> cat /usr/local/etc/php-fpm.d/www.conf | grep listen
```

## 5. まとめ

### 5.1 重要なポイント
1. **NPM設定時は内部ポートを使用** - ホスト側のマッピングポートではなく、コンテナ内部のポートを指定
2. **PHP-FPMとNginxの通信方式を統一** - UnixソケットまたはTCPのどちらかで統一
3. **開発環境と本番環境の設定を一致させる** - 同じ動作をするように設定を統一

### 5.2 よくあるエラーと原因
- **503 Service Unavailable**: NPMのポート設定が間違っている
- **502 Bad Gateway**: PHP-FPMとNginxの通信設定が不整合
- **Connection refused**: コンテナ間のネットワーク接続問題

### 5.3 予防策
1. 開発環境と本番環境で同じ設定ファイルを使用
2. Docker Composeでボリューム共有を適切に設定
3. ヘルスチェックを有効にしてコンテナの状態を監視
4. ログを定期的に確認して問題を早期発見

---

**作成日**: 2025年9月20日  
**対象**: Docker, Nginx Proxy Manager, PHP-FPM, Laravel
