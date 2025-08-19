# GitHub訪問数集計システム

このシステムは、Laravelを使用してGitHubリポジトリの訪問数データを自動的に取得・集計するシステムです。

## 機能

- **自動データ取得**: GitHub APIから毎日訪問数データを自動取得
- **データ集計**: 訪問数とユニーク訪問者数の統計情報
- **可視化**: Chart.jsを使用したグラフ表示
- **フィルタリング**: プロジェクト・日付範囲でのデータフィルタリング
- **ページネーション**: 大量データの効率的な表示

## セットアップ手順

### 1. 環境設定

`.env`ファイルに以下の設定を追加してください：

```env
# GitHub API設定
GITHUB_TOKEN=your_personal_access_token_here
GITHUB_OWNER=your_github_username
GITHUB_REPO=your_repository_name
```

### 2. GitHub Personal Access Tokenの取得

1. GitHubにログイン
2. Settings → Developer settings → Personal access tokens → Tokens (classic)
3. "Generate new token"をクリック
4. 以下の権限を選択：
   - `repo` (プライベートリポジトリの場合)
   - `public_repo` (パブリックリポジトリの場合)
5. トークンを生成し、`.env`ファイルに設定

### 3. データベースの準備

```bash
# マイグレーションを実行
php artisan migrate

# データベーステーブルが作成されていることを確認
php artisan tinker
>>> Schema::hasTable('github_views')
```

### 4. 初回データ取得のテスト

```bash
# コマンドが正しく動作するかテスト
php artisan github:fetch-views
```

## 使用方法

### Webインターフェース

ブラウザで以下のURLにアクセス：

- **メインページ**: `/github/views`
- **チャートデータ**: `/github/chart`
- **統計情報**: `/github/stats`
- **プロジェクト統計**: `/github/project-stats`

### コマンドライン

```bash
# 手動でデータを取得
php artisan github:fetch-views

# スケジュールされたタスクを実行
php artisan schedule:run
```

### スケジューラー設定

毎日午前2時に自動実行されるように設定されています。

**XserverでのCron設定例：**
```bash
# 毎日午前2時に実行
0 2 * * * /usr/bin/php /home/username/yourdomain.com/public_html/artisan schedule:run >> /dev/null 2>&1
```

## データ構造

### github_viewsテーブル

| カラム | 型 | 説明 |
|--------|----|----|
| id | INT | 主キー |
| project | VARCHAR | プロジェクト名 (owner/repo形式) |
| date | DATE | 訪問日 |
| count | INT | 総訪問数 |
| uniques | INT | ユニーク訪問者数 |
| created_at | TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | 更新日時 |

## トラブルシューティング

### よくある問題

1. **GitHub APIエラー**
   - トークンの権限を確認
   - リポジトリ名とオーナー名が正しいか確認

2. **データが取得できない**
   - リポジトリにトラフィックデータがあるか確認
   - GitHubの設定でトラフィック分析が有効になっているか確認

3. **スケジューラーが動作しない**
   - Cronジョブが正しく設定されているか確認
   - ログファイルを確認: `storage/logs/github-fetch.log`

### ログの確認

```bash
# スケジューラーログの確認
tail -f storage/logs/github-fetch.log

# Laravelログの確認
tail -f storage/logs/laravel.log
```

## カスタマイズ

### 新しいプロジェクトの追加

複数のリポジトリを監視したい場合は、`.env`ファイルで設定を変更するか、コマンドを修正してください。

### データ取得頻度の変更

`app/Console/Kernel.php`の`schedule`メソッドで実行頻度を変更できます：

```php
// 毎時間実行
$schedule->command('github:fetch-views')->hourly();

// 毎週実行
$schedule->command('github:fetch-views')->weekly();
```

## セキュリティ

- GitHubトークンは必ず`.env`ファイルで管理
- 本番環境では`APP_DEBUG=false`に設定
- 必要最小限の権限のみをトークンに付与

## ライセンス

このプロジェクトはMITライセンスの下で公開されています。
