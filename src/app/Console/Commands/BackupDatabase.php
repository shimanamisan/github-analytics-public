<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDO;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--format=sql : バックアップ形式 (sql, gz, zip)} {--tables= : 特定のテーブルのみバックアップ (カンマ区切り)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MySQLデータベースのバックアップを取得します';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('データベースバックアップを開始します...');

        try {
            // データベース設定を取得
            $connection = config('database.default');
            $dbConfig = config("database.connections.{$connection}");

            if (!in_array($dbConfig['driver'], ['mysql', 'mariadb'])) {
                $this->error('このコマンドはMySQL/MariaDBデータベースでのみ動作します。');
                return 1;
            }

            // バックアップディレクトリを作成
            $backupDir = storage_path('backups/database');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // ファイル名を生成
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $filepath = $backupDir . '/' . $filename;

            // PDOを使用してバックアップを実行
            $this->info("PDOを使用してデータベースバックアップを実行中...");
            
            $success = $this->createBackupWithPDO($dbConfig, $filepath);

            if (!$success) {
                $this->error('バックアップの実行に失敗しました');
                return 1;
            }

            // ファイルサイズを確認
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                $this->error('バックアップファイルが作成されていないか、空です。');
                return 1;
            }

            $fileSize = $this->formatFileSize(filesize($filepath));
            $this->info("バックアップが完了しました: {$filename} ({$fileSize})");

            // 圧縮オプションの処理
            $format = $this->option('format');
            if ($format === 'gz') {
                $this->compressGzip($filepath);
            } elseif ($format === 'zip') {
                $this->compressZip($filepath);
            }

            // 古いバックアップファイルの削除（30日以上古いもの）
            $this->cleanOldBackups($backupDir);

            $this->info('バックアップ処理が完了しました。');
            return 0;

        } catch (\Exception $e) {
            $this->error('バックアップ処理中にエラーが発生しました: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * PDOを使用してデータベースバックアップを作成
     */
    private function createBackupWithPDO(array $dbConfig, string $filepath): bool
    {
        try {
            // PDO接続を作成
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            // バックアップファイルを開く
            $file = fopen($filepath, 'w');
            if (!$file) {
                throw new \Exception('バックアップファイルを開けません');
            }

            // SQLヘッダーを書き込み
            fwrite($file, "-- MySQL Database Backup\n");
            fwrite($file, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
            fwrite($file, "-- Database: {$dbConfig['database']}\n");
            fwrite($file, "-- Host: {$dbConfig['host']}\n\n");
            fwrite($file, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($file, "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($file, "SET AUTOCOMMIT=0;\n");
            fwrite($file, "START TRANSACTION;\n\n");

            // 特定のテーブルのみバックアップするかどうかを確認
            $tablesOption = $this->option('tables');
            $specificTables = $tablesOption ? explode(',', $tablesOption) : null;

            // テーブル一覧を取得
            if ($specificTables) {
                $tables = $specificTables;
            } else {
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            $this->info("バックアップ対象テーブル数: " . count($tables));

            // 各テーブルをバックアップ
            foreach ($tables as $table) {
                $this->line("テーブル '{$table}' をバックアップ中...");
                
                // テーブル構造を取得
                $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
                
                fwrite($file, "-- Table structure for table `{$table}`\n");
                fwrite($file, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($file, $createTable['Create Table'] . ";\n\n");

                // テーブルデータを取得
                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                $rowCount = 0;

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($rowCount === 0) {
                        fwrite($file, "-- Dumping data for table `{$table}`\n");
                        fwrite($file, "INSERT INTO `{$table}` (");
                        fwrite($file, "`" . implode("`, `", array_keys($row)) . "`");
                        fwrite($file, ") VALUES\n");
                    }

                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));

                    $prefix = $rowCount === 0 ? '' : ',';
                    fwrite($file, $prefix . "(" . implode(", ", $values) . ")\n");
                    $rowCount++;
                }

                if ($rowCount > 0) {
                    fwrite($file, ";\n\n");
                } else {
                    fwrite($file, "-- No data for table `{$table}`\n\n");
                }
            }

            // SQLフッターを書き込み
            fwrite($file, "SET FOREIGN_KEY_CHECKS=1;\n");
            fwrite($file, "COMMIT;\n");

            fclose($file);

            return true;

        } catch (\Exception $e) {
            $this->error("PDOバックアップエラー: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gzip圧縮
     */
    private function compressGzip(string $filepath): void
    {
        $gzFilepath = $filepath . '.gz';
        $command = "gzip -c {$filepath} > {$gzFilepath}";
        
        exec($command);
        
        if (file_exists($gzFilepath)) {
            unlink($filepath); // 元のSQLファイルを削除
            $this->info("Gzip圧縮完了: " . basename($gzFilepath));
        }
    }

    /**
     * ZIP圧縮
     */
    private function compressZip(string $filepath): void
    {
        $zipFilepath = str_replace('.sql', '.zip', $filepath);
        $command = "zip -j {$zipFilepath} {$filepath}";
        
        exec($command);
        
        if (file_exists($zipFilepath)) {
            unlink($filepath); // 元のSQLファイルを削除
            $this->info("ZIP圧縮完了: " . basename($zipFilepath));
        }
    }

    /**
     * 古いバックアップファイルを削除
     */
    private function cleanOldBackups(string $backupDir): void
    {
        $files = glob($backupDir . '/*');
        $cutoffDate = Carbon::now()->subDays(30);

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = Carbon::createFromTimestamp(filemtime($file));
                if ($fileTime->lt($cutoffDate)) {
                    unlink($file);
                    $this->line("古いバックアップファイルを削除: " . basename($file));
                }
            }
        }
    }

    /**
     * ファイルサイズを人間が読みやすい形式に変換
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
