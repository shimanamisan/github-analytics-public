# Redis移行ガイド

## 📝 概要

このドキュメントでは、データベースベースのセッション/キャッシュからRedisへ移行する手順を説明します。

---

## ⚠️ 移行の影響

### データベースセッションからRedisセッションへ移行する際の注意点

1. **既存セッションの無効化**
   - Redis移行時、既存のデータベースセッションは無効になります
   - **全ユーザーが強制的にログアウトされます**

2. **移行タイミング**
   - ユーザーアクセスが少ない時間帯（深夜など）を推奨
   - 本番環境では事前に告知することを推奨

---

## 🚀 移行手順

### 開発環境での移行

開発環境では既にRedisが設定済みです。以下の手順で確認してください。

#### 1. Redisコンテナの起動確認

```bash
cd /path/to/project
docker compose ps redis
```

Redisが起動していない場合：

```bash
docker compose up -d redis
```

#### 2. Laravel設定の確認

`.env` ファイルを確認：

```bash
# Redis接続設定
REDIS_CLIENT=phpredis
REDIS_HOST=github-traffic-api-redis  # 開発環境のコンテナ名
REDIS_PASSWORD=null                   # 開発環境ではパスワードなし
REDIS_PORT=6379

# セッション・キャッシュ・キュー設定
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

#### 3. 接続テスト

```bash
# appコンテナに入る
docker compose exec app bash

# Redis接続テスト
php artisan tinker

>>> use Illuminate\Support\Facades\Redis;
>>> Redis::ping();
=> "+PONG"

>>> Cache::put('test', 'value', 60);
=> true

>>> Cache::get('test');
=> "value"

>>> exit
```

#### 4. 既存セッションのクリーンアップ（オプション）

データベースセッションテーブルが不要になった場合、削除できます：

```bash
# セッションテーブルのクリア
php artisan tinker
>>> DB::table('sessions')->truncate();
>>> exit
```

---

## 🏭 本番環境での移行

### 事前準備

1. **ユーザーへの告知**
   ```
   【重要なお知らせ】
   システムメンテナンスに伴い、〇月〇日 深夜2:00-2:30の間、
   全ユーザーが一時的にログアウトされます。
   ご不便をおかけしますが、ご理解とご協力をお願いいたします。
   ```

2. **バックアップ取得**
   ```bash
   # データベースバックアップ
   cd ~/deploy/github-traffic-api
   docker compose exec db mysqldump -u root -p github_traffic_api > backup_before_redis_$(date +%Y%m%d).sql
   ```

### 移行手順

#### 1. メンテナンスモード有効化

```bash
cd ~/deploy/github-traffic-api
docker compose exec app php artisan down --render="errors::503" --retry=60
```

#### 2. .env ファイルの更新

```bash
cd ~/deploy/github-traffic-api
nano .env
```

以下を確認・更新：

```bash
# Redis設定
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password_here  # 本番環境では必ずパスワード設定
REDIS_PORT=6379

# セッション・キャッシュ・キュー設定
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

#### 3. コンテナの再起動

```bash
# Redisコンテナを含めて再起動
docker compose up -d redis

# アプリケーションコンテナ再起動
docker compose restart app scheduler worker

# ヘルスチェック
docker compose ps
```

#### 4. Redis接続確認

```bash
# Redis動作確認
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD ping
# 出力: PONG

# Laravel経由での確認
docker compose exec app php artisan tinker
>>> use Illuminate\Support\Facades\Redis;
>>> Redis::ping();
=> "+PONG"
>>> exit
```

#### 5. キャッシュクリア

```bash
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

#### 6. メンテナンスモード解除

```bash
docker compose exec app php artisan up
```

#### 7. 動作確認

ブラウザでアクセスし、以下を確認：

- ✅ ログインできること
- ✅ セッションが維持されること
- ✅ ページ表示が正常なこと

#### 8. ログ監視

```bash
# 数分間ログを監視
docker compose logs -f app redis
```

---

## 🔍 動作確認

### セッションがRedisに保存されているか確認

```bash
# Redisに接続
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD

# セッションキーの確認
127.0.0.1:6379> KEYS *
1) "laravel_cache:..."
2) "laravel_session:..."

# セッション数の確認
127.0.0.1:6379> KEYS laravel_session:* | wc -l

# 終了
127.0.0.1:6379> exit
```

### キャッシュがRedisに保存されているか確認

```bash
# Laravelでキャッシュをセット
docker compose exec app php artisan tinker
>>> Cache::put('test_key', 'test_value', 60);
>>> Cache::get('test_key');
=> "test_value"
>>> exit

# Redisで直接確認
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD
127.0.0.1:6379> GET laravel_cache:test_key
127.0.0.1:6379> exit
```

---

## 🎯 パフォーマンス比較

### 移行前（データベースセッション）

- セッション読み取り: ~5-10ms
- セッション書き込み: ~10-20ms
- データベース負荷: 高

### 移行後（Redisセッション）

- セッション読み取り: ~0.5-1ms
- セッション書き込み: ~1-2ms
- データベース負荷: 低

**約10倍の高速化が期待できます！**

---

## 🐛 トラブルシューティング

### Redis接続エラー

**症状**: `Connection refused [tcp://redis:6379]`

**解決策**:

```bash
# Redisコンテナの状態確認
docker compose ps redis

# Redisが起動していない場合
docker compose up -d redis

# ログ確認
docker compose logs redis
```

### 認証エラー

**症状**: `NOAUTH Authentication required`

**解決策**:

```bash
# .envのREDIS_PASSWORDを確認
cat .env | grep REDIS_PASSWORD

# docker-compose.ymlのREDIS_PASSWORDと一致しているか確認
cat docker-compose.yml | grep REDIS_PASSWORD

# 一致していない場合は修正して再起動
docker compose restart app redis
```

### セッションが保存されない

**症状**: ログイン後すぐにログアウトされる

**解決策**:

```bash
# セッション設定確認
docker compose exec app php artisan config:show session

# キャッシュクリア
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear

# 再起動
docker compose restart app
```

---

## 🔄 ロールバック手順

Redisで問題が発生した場合、データベースセッションに戻すことができます。

### 1. メンテナンスモード有効化

```bash
docker compose exec app php artisan down
```

### 2. .env を元に戻す

```bash
nano .env

# 以下に変更
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### 3. アプリケーション再起動

```bash
docker compose restart app scheduler worker
docker compose exec app php artisan config:cache
```

### 4. メンテナンスモード解除

```bash
docker compose exec app php artisan up
```

---

## 📊 Redis監視

### メモリ使用状況

```bash
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD info memory
```

### 接続数

```bash
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD info clients
```

### キー数

```bash
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD DBSIZE
```

---

## 💡 ベストプラクティス

1. **本番環境では必ずRedisパスワードを設定**
   ```bash
   REDIS_PASSWORD=strong_random_password_here
   ```

2. **Redisデータの永続化**
   - RDB（スナップショット）: デフォルトで有効
   - AOF（Append Only File）: `docker-compose.yml`で `--appendonly yes` 設定済み

3. **定期的なメモリ監視**
   ```bash
   # メモリ使用量が多い場合はキャッシュをクリア
   docker compose exec app php artisan cache:clear
   ```

4. **Redisのバックアップ**
   ```bash
   # Redisデータのバックアップ
   docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD SAVE
   docker cp github-traffic-api-redis:/data/dump.rdb ./redis_backup_$(date +%Y%m%d).rdb
   ```

---

**Redis移行完了！ 🎉**
