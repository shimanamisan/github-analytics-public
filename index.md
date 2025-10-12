はい、GitHubのフォロワーやフォローしているユーザーの情報を取得することは可能です！GitHub APIには専用のエンドポイントが用意されています。

## 利用可能なGitHub API

### 1. **フォロワー情報取得**
```
GET /users/{username}/followers
```

### 2. **フォロー中のユーザー情報取得**
```
GET /users/{username}/following
```

### 3. **フォロー状況の統計取得**
```
GET /users/{username}
```

## Laravel実装例

### **Artisanコマンドの拡張**## データベースマイグレーション## 必要な権限とトークン設定

### **Fine-grained Personal Access Token権限**
フォロワー情報取得には追加の権限が必要です：

```
Repository permissions: 不要（今回はユーザー情報のみ）

Account permissions:
✅ Profile: Read (ユーザープロフィール情報)
✅ Followers: Read (フォロワー情報) ※存在する場合
```

### **設定ファイル更新**
```php
// config/services.php
'github' => [
    'token' => env('GITHUB_TOKEN'),
    'owner' => env('GITHUB_OWNER'),
    'repo' => env('GITHUB_REPO'),
    'username' => env('GITHUB_USERNAME'), // 追加
],
```

```env
// .env
GITHUB_USERNAME=your-github-username
```

## スケジューラー設定

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // 既存のリポジトリ訪問数取得
    $schedule->command('github:fetch-views')
             ->dailyAt('02:00')
             ->appendOutputTo(storage_path('logs/github-fetch.log'));
    
    // フォロワー数取得（基本統計のみ）
    $schedule->command('github:fetch-followers')
             ->dailyAt('02:30')
             ->appendOutputTo(storage_path('logs/github-followers.log'));
    
    // 詳細フォロワー情報取得（週1回）
    $schedule->command('github:fetch-followers --detailed')
             ->weeklyOn(1, '03:00') // 毎週月曜日3時
             ->appendOutputTo(storage_path('logs/github-followers-detailed.log'));
}
```

## 使用方法

### **基本統計のみ取得**
```bash
php artisan github:fetch-followers --user=your-username
```

### **詳細フォロワー情報も取得**
```bash
php artisan github:fetch-followers --user=your-username --detailed
```

## 注意点とAPI制限

### **GitHub API制限**
- **Rate Limit**: 認証済みで5,000リクエスト/時間
- **フォロワー取得**: ページあたり最大100件
- **大量フォロワー**: 詳細取得は時間がかかる（1万フォロワー = 100リクエスト）

### **推奨運用方法**
1. **基本統計**: 毎日取得（フォロワー数、フォロー数のみ）
2. **詳細情報**: 週1回取得（個別フォロワーリスト）
3. **Rate Limiting**: リクエスト間に1秒の間隔を設置

## 統合ダッシュボード

既存の訪問数ダッシュボードにフォロワー情報を統合することで、GitHubアカウントの総合的な成長指標を追跡できます：

- **リポジトリ訪問数推移**
- **フォロワー数推移**
- **パブリックリポジトリ数推移**
- **成長率分析**

この実装により、リポジトリの人気度とアカウント全体の影響力を包括的に分析できるようになります。

## データベースバックアップ取得手順

本番環境からデータベースバックアップファイルを取得する方法です。

### バックアップファイルの保存場所

- **コンテナ内**: `/var/www/html/storage/backups/database/`
- **ホスト側**: Dockerボリューム `app-storage` にマウント
- **自動バックアップ**: 毎日午前2時に実行（30日間保持）

### 推奨の取得手順

#### 1. 本番サーバーにSSH接続
```bash
ssh ユーザー名@本番サーバー
```

#### 2. バックアップファイル一覧を確認
```bash
docker exec github-traffic-api-backend ls -lh /var/www/html/storage/backups/database/
```

出力例：
```
-rw-r--r-- 1 www-data www-data 1.2M Oct 12 02:00 backup_2025-10-12_02-00-00.sql.gz
-rw-r--r-- 1 www-data www-data 1.1M Oct 11 02:00 backup_2025-10-11_02-00-00.sql.gz
```

#### 3. 取得したいファイルをホームディレクトリにコピー
```bash
docker cp github-traffic-api-backend:/var/www/html/storage/backups/database/backup_2025-10-12_02-00-00.sql.gz ~/
```

#### 4. ローカルマシンに転送（ローカルマシンから実行）
```bash
scp ユーザー名@本番サーバー:~/backup_2025-10-12_02-00-00.sql.gz ./
```

### 代替方法：直接転送

本番サーバーから直接ローカルマシンに転送する場合：

```bash
# ローカルマシンから実行
ssh ユーザー名@本番サーバー "docker exec github-traffic-api-backend cat /var/www/html/storage/backups/database/backup_2025-10-12_02-00-00.sql.gz" > backup_2025-10-12_02-00-00.sql.gz
```

### 最新のバックアップを自動取得

```bash
# 本番サーバーで実行
LATEST_BACKUP=$(docker exec github-traffic-api-backend ls -t /var/www/html/storage/backups/database/ | head -1)
docker cp github-traffic-api-backend:/var/www/html/storage/backups/database/$LATEST_BACKUP ~/

# ローカルマシンに転送
scp ユーザー名@本番サーバー:~/$LATEST_BACKUP ./
```

### バックアップファイルのリストア

#### phpMyAdminでリストア
1. phpMyAdminにアクセス（http://localhost:8091）
2. 左サイドバーでデータベースを選択
3. 「インポート」タブをクリック
4. バックアップファイル（.sql.gz）を選択してインポート

#### コマンドラインでリストア
```bash
# .gz ファイルを直接リストア
gunzip < backup_2025-10-12_02-00-00.sql.gz | mysql -u ユーザー名 -p

# または Docker経由でリストア
gunzip < backup_2025-10-12_02-00-00.sql.gz | docker exec -i github-traffic-api-db mysql -u ユーザー名 -p パスワード
```

### バックアップの作成

手動でバックアップを作成する場合：

```bash
# 本番サーバーで実行
docker exec github-traffic-api-backend php artisan db:backup --format=gz
```

### 注意事項

- バックアップファイルは30日間保持され、それ以降は自動的に削除されます
- 重要なバックアップは別途保存することを推奨します
- バックアップファイルには機密情報が含まれるため、取り扱いには十分注意してください