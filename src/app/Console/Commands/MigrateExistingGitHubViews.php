<?php

namespace App\Console\Commands;

use App\Models\GitHubRepository;
use App\Models\GitHubView;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateExistingGitHubViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:migrate-existing-views {--dry-run : 実際の更新は行わず、プレビューのみ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '既存のGitHub Viewsデータを新しいリポジトリ構造に移行します';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('既存のGitHub Viewsデータの移行を開始します...');
        
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('ドライランモード: 実際の更新は行いません');
        }

        // repository_idがnullの既存データを取得
        $orphanedViews = GitHubView::whereNull('repository_id')->get();
        
        if ($orphanedViews->isEmpty()) {
            $this->info('移行が必要なデータが見つかりませんでした。');
            return 0;
        }

        $this->info("移行対象: {$orphanedViews->count()}件");
        
        $migratedCount = 0;
        $errorCount = 0;
        
        foreach ($orphanedViews as $view) {
            try {
                // プロジェクト名からowner/repoを分離
                $projectParts = explode('/', $view->project);
                
                if (count($projectParts) !== 2) {
                    $this->warn("無効なプロジェクト形式: {$view->project}");
                    $errorCount++;
                    continue;
                }
                
                [$owner, $repo] = $projectParts;
                
                // 対応するリポジトリを検索または作成
                $repository = GitHubRepository::firstOrCreate(
                    [
                        'owner' => $owner,
                        'repo' => $repo,
                    ],
                    [
                        'name' => $view->project,
                        'description' => "自動移行されたリポジトリ: {$view->project}",
                        'is_active' => true,
                    ]
                );
                
                if (!$isDryRun) {
                    // GitHubViewのrepository_idを更新
                    $view->update(['repository_id' => $repository->id]);
                }
                
                $this->line("  ✓ {$view->project} → Repository ID: {$repository->id}");
                $migratedCount++;
                
            } catch (\Exception $e) {
                $this->error("  ✗ エラー (ID: {$view->id}): {$e->getMessage()}");
                $errorCount++;
            }
        }
        
        $this->info("移行完了: 成功 {$migratedCount}件, エラー {$errorCount}件");
        
        if ($isDryRun) {
            $this->warn('ドライランモードでした。実際に移行するには --dry-run オプションを外して再実行してください。');
        }
        
        return $errorCount > 0 ? 1 : 0;
    }
}
