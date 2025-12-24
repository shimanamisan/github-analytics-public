# Laravel Scheduler 開発環境セットアップ

## 概要

本番環境と同じく、開発環境でも**専用のスケジューラーコンテナ**を使用します。
従来のcron + supervisorの構成から、`schedule:work`を使った専用コンテナ方式に変更しました。

## アーキテクチャの変更

### Before（旧構成）
```
app コンテナ
├── Supervisor（プロセス管理）
  ├── PHP-FPM（Webリクエスト処理）
  ├── Cron（毎分 schedule:run を実行）
  └── check_artisan（起動時にキャッシュクリア）
```

### After（新構成）
```
app コンテナ
├── docker-entrypoint.sh（起動スクリプト）
  ├── パーミッション修正
  ├── Laravelキャッシュクリア
  └── PHP-FPM起動（フォアグラウンド）

scheduler コンテナ（新規追加）
└── schedule:work（常駐型スケジューラー）
```

**主な変更点：**
- **Supervisor削除**：PHP-FPMを直接起動（1コンテナ1プロセスの原則に準拠）
- **Cron削除**：専用スケジューラーコンテナで`schedule:work`を使用
- **check_artisan削除**：`docker-entrypoint.sh`に統合してシンプル化

## メリット

### 1. 本番環境との構成統一
- 開発環境と本番環境でスケジューラーの動作が完全に一致
- 本番環境特有の問題を開発段階で発見可能

### 2. マイクロサービス設計
- 1コンテナ1プロセスの原則に準拠
- 各コンテナが単一の責務を持つ

### 3. 障害の分離
- スケジューラーがクラッシュしてもWebアプリケーションは影響を受けない
- 独立した再起動・デバッグが可能

### 4. 開発の効率化
- スケジューラーのログが独立して確認可能
- デバッグコマンドで即座にタスクをテスト実行

## 使い方

### スケジューラーコンテナの起動

```bash
# 全コンテナを起動（schedulerコンテナも含む）
docker compose up -d

# schedulerコンテナのみ起動
docker compose up -d scheduler

# schedulerコンテナの再起動
docker compose restart scheduler
```

### ログの確認

```bash
# スケジューラーのログをリアルタイムで監視
docker compose logs -f scheduler

# 最新100行を表示
docker compose logs --tail=100 scheduler
```

### デバッグコマンド

#### 1. スケジュールされたタスクの一覧表示

```bash
docker compose exec app php artisan schedule:debug --list
```

**出力例：**
```
📅 Scheduled Tasks (3 total)

┌────┬─────────────────────────────────┬────────────┬─────────────────────┬──────────────┐
│ ID │ Command                         │ Schedule   │ Next Run            │ Timezone     │
├────┼─────────────────────────────────┼────────────┼─────────────────────┼──────────────┤
│ 1  │ github:fetch-views              │ 0 23 * * * │ 2025-10-12 23:00:00 │ Asia/Tokyo   │
│ 2  │ github:fetch-followers          │ 30 23 * * *│ 2025-10-12 23:30:00 │ Asia/Tokyo   │
│ 3  │ db:backup --format=gz           │ 0 2 * * *  │ 2025-10-13 02:00:00 │ Asia/Tokyo   │
└────┴─────────────────────────────────┴────────────┴─────────────────────┴──────────────┘

💡 Tips:
  • Run specific task: php artisan schedule:debug --task=<ID>
  • View all tasks: php artisan schedule:list
  • Test scheduler: php artisan schedule:run
```

#### 2. 特定のタスクを即座に実行（IDを指定）

```bash
# ID 1 のタスクを実行
docker compose exec app php artisan schedule:debug --task=1

# ID 2 のタスクを実行
docker compose exec app php artisan schedule:debug --task=2
```

#### 3. 特定のタスクを即座に実行（コマンド名で検索）

```bash
# コマンド名に "github" を含むタスクを実行
docker compose exec app php artisan schedule:debug --task=github

# コマンド名に "backup" を含むタスクを実行
docker compose exec app php artisan schedule:debug --task=backup
```

#### 4. Laravel標準のスケジュール確認コマンド

```bash
# スケジュールの詳細リストを表示
docker compose exec app php artisan schedule:list

# スケジュールを手動で1回実行
docker compose exec app php artisan schedule:run
```

## トラブルシューティング

### スケジューラーが動作しない場合

1. **コンテナの状態を確認**
   ```bash
   docker compose ps scheduler
   ```

2. **ログを確認**
   ```bash
   docker compose logs scheduler
   ```

3. **コンテナを再起動**
   ```bash
   docker compose restart scheduler
   ```

### スケジュールが実行されない場合

1. **スケジュールの定義を確認**
   ```bash
   docker compose exec app php artisan schedule:list
   ```

2. **手動で実行してエラーを確認**
   ```bash
   docker compose exec app php artisan schedule:debug --task=1
   ```

3. **タイムゾーンの確認**
   ```bash
   docker compose exec scheduler date
   # Asia/Tokyo になっているか確認
   ```

### データベース接続エラーが発生する場合

```bash
# schedulerコンテナがdb, redisコンテナの起動を待つように設定済み
# それでもエラーが出る場合は、appコンテナで接続確認
docker compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

## 開発時のベストプラクティス

### 1. スケジュールの追加・変更後

```bash
# スケジュールを確認
docker compose exec app php artisan schedule:list

# スケジューラーコンテナを再起動（変更を反映）
docker compose restart scheduler

# 即座にテスト実行
docker compose exec app php artisan schedule:debug --task=新しいタスクのID
```

### 2. デバッグ時

```bash
# --verbose オプションで詳細なログを出力
docker compose exec scheduler php artisan schedule:work --verbose

# または、appコンテナから手動実行
docker compose exec app php artisan schedule:run -vvv
```

### 3. 新しいスケジュールのテスト

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

// 開発環境のみ：1分ごとにテスト実行
if (app()->environment('local')) {
    Schedule::command('your:command')
        ->everyMinute()
        ->appendOutputTo(storage_path('logs/your-command.log'));
}
```

## 環境変数

schedulerコンテナでは以下の環境変数が設定されています：

| 環境変数 | 値 | 説明 |
|----------|-----|------|
| `APP_ENV` | `local` | 開発環境 |
| `APP_DEBUG` | `true` | デバッグモード有効 |
| `TZ` | `Asia/Tokyo` | タイムゾーン |
| `SCHEDULE_DEBUG` | `true` | スケジュールデバッグ用（カスタム） |

## 関連ファイル

- [.devcontainer/docker-compose.yml](.devcontainer/docker-compose.yml#L101-L120) - スケジューラーコンテナの定義
- [src/app/Console/Commands/ScheduleDebugCommand.php](../src/app/Console/Commands/ScheduleDebugCommand.php) - デバッグコマンド
- [src/routes/console.php](../src/routes/console.php) - スケジュール定義

## 参考：本番環境との違い

| 項目 | 開発環境 | 本番環境 |
|------|----------|----------|
| コマンド | `schedule:work --verbose` | `schedule:work` |
| APP_ENV | `local` | `production` |
| APP_DEBUG | `true` | `false` |
| ログレベル | 詳細（verbose） | 標準 |
| 再起動ポリシー | `unless-stopped` | `unless-stopped` |

## まとめ

- 開発環境でも本番環境と同じ専用スケジューラーコンテナを使用
- `schedule:work`による常駐型スケジューラー
- デバッグコマンド（`schedule:debug`）で手軽にテスト実行可能
- ログの独立管理で問題の切り分けが容易
